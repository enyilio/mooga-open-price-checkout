<?php
/**
 * Plugin Name: Mooga Open Price Checkout
 * Description: One-page checkout with open pricing. Automatically adds a designated product to the cart and allows customers to enter their own amount in the subtotal field at checkout. The order total updates in real time as the customer types.
 * Version: 1.4.0
 * Author: MooSpace
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ZENITH_CC_VERSION',    '1.4.0' );
define( 'ZENITH_CC_PRODUCT_ID', 40474 );

// ─── 1. 自動將指定商品加入購物車 ──────────────────────────────────────────
add_action( 'template_redirect', function() {
    if ( ! is_cart() && ! is_checkout() ) return;
    if ( ! WC()->cart ) return;

    $found = false;
    foreach ( WC()->cart->get_cart() as $item ) {
        if ( $item['product_id'] == ZENITH_CC_PRODUCT_ID ) {
            $found = true;
            break;
        }
    }
    if ( ! $found ) {
        WC()->cart->add_to_cart( ZENITH_CC_PRODUCT_ID, 1 );
    }
} );

// ─── 2. 將結帳頁小計欄替換為輸入欄位 ─────────────────────────────────────
add_filter( 'woocommerce_cart_item_subtotal', function( $subtotal, $cart_item, $cart_item_key ) {
    if ( ! is_checkout() ) return $subtotal;
    if ( $cart_item['product_id'] != ZENITH_CC_PRODUCT_ID ) return $subtotal;

    $saved = WC()->session ? floatval( WC()->session->get( 'zenith_custom_amount', 0 ) ) : 0;
    $value = $saved > 0 ? $saved : '';

    return '<input type="number"
               id="zenith_price_input"
               value="' . esc_attr( $value ) . '"
               min="1"
               step="1"
               placeholder="輸入金額"
               style="width:130px; padding:5px 8px; text-align:right; font-size:1em; border:1px solid #ccc; border-radius:3px;" />';
}, 10, 3 );

// ─── 3. AJAX：儲存金額到 session ──────────────────────────────────────────
add_action( 'wp_ajax_zenith_set_custom_amount',        'zenith_set_custom_amount_cb' );
add_action( 'wp_ajax_nopriv_zenith_set_custom_amount', 'zenith_set_custom_amount_cb' );

function zenith_set_custom_amount_cb() {
    $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
    if ( $amount > 0 ) {
        WC()->session->set( 'zenith_custom_amount', $amount );
        WC()->cart->calculate_totals();
        wp_send_json_success( [ 'amount' => $amount ] );
    } else {
        wp_send_json_error( [ 'message' => '金額無效' ] );
    }
}

// ─── 4. 套用自訂金額到購物車價格（顯示用）────────────────────────────────
add_action( 'woocommerce_before_calculate_totals', function( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    $amount = WC()->session ? floatval( WC()->session->get( 'zenith_custom_amount', 0 ) ) : 0;
    if ( ! $amount ) return;

    foreach ( $cart->get_cart() as $item ) {
        if ( $item['product_id'] == ZENITH_CC_PRODUCT_ID ) {
            $item['data']->set_price( $amount );
        }
    }
}, 20 );

// ─── 5. 套用自訂金額到訂單行項目（訂單寫入時）────────────────────────────
add_action( 'woocommerce_checkout_create_order_line_item', function( $item, $cart_item_key, $values, $order ) {
    if ( $values['product_id'] != ZENITH_CC_PRODUCT_ID ) return;

    $amount = WC()->session ? floatval( WC()->session->get( 'zenith_custom_amount', 0 ) ) : 0;
    if ( ! $amount ) return;

    $item->set_subtotal( $amount );
    $item->set_total( $amount );
}, 10, 4 );

// ─── 6. 結帳送出時驗證 ────────────────────────────────────────────────────
add_action( 'woocommerce_checkout_process', function() {
    $saved = WC()->session ? floatval( WC()->session->get( 'zenith_custom_amount', 0 ) ) : 0;
    if ( ! $saved ) {
        wc_add_notice( '請先在小計欄位輸入金額再結帳。', 'error' );
    }
} );

// ─── 7. 前端 JS：輸入後自動更新總計（debounce 800ms）─────────────────────
add_action( 'wp_footer', function() {
    if ( ! is_checkout() ) return;
    ?>
    <script>
    jQuery( function( $ ) {
        var debounceTimer = null;

        function bindInput() {
            $( '#zenith_price_input' ).off( '.zenith' ).on( 'input.zenith', function() {
                var amount = parseFloat( $( this ).val() );
                if ( ! amount || amount <= 0 ) return;

                clearTimeout( debounceTimer );
                debounceTimer = setTimeout( function() {
                    applyAmount( amount );
                }, 800 );
            } );
        }

        function applyAmount( amount ) {
            $.post( '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                action: 'zenith_set_custom_amount',
                amount: amount
            }, function( res ) {
                if ( res.success ) {
                    $( 'body' ).trigger( 'update_checkout' );
                }
            } );
        }

        $( document.body ).on( 'updated_checkout', bindInput );
        bindInput();
    } );
    </script>
    <?php
}, 20 );
