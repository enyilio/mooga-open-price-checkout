# Mooga Open Price Checkout

A WooCommerce plugin that enables a one-page open pricing checkout experience — customers enter their own amount directly in the subtotal field, and the order total updates in real time.

---

## Features

- **Auto cart injection** — Automatically adds a designated product to the cart when the customer visits the cart or checkout page
- **Editable subtotal** — Replaces the product subtotal cell with a number input field at checkout
- **Real-time total update** — Order total recalculates automatically as the customer types (debounced at 800ms)
- **Session-based price** — Custom amount is stored in the WooCommerce session and applied consistently across cart calculation and order creation
- **Order line item override** — Ensures the order is recorded with the customer's entered amount, not the product's base price
- **Checkout validation** — Prevents order submission if no amount has been entered

---

## Requirements

- WordPress 5.0+
- WooCommerce 5.0+
- PHP 7.4+

---

## Installation

1. Upload the `mooga-open-price-checkout` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Set the product ID in the plugin file (`ZENITH_CC_PRODUCT_ID`) to match your designated product

---

## Configuration

Open `mooga-open-price-checkout.php` and update the constant:

```php
define( 'ZENITH_CC_PRODUCT_ID', 40474 ); // Replace with your product ID
```

The product should be a simple WooCommerce product. Its base price is irrelevant — the customer's entered amount will override it at both display and order creation.

---

## How It Works

1. Customer visits the cart or checkout page
2. The designated product is automatically added to the cart if not already present
3. On the checkout page, the subtotal cell becomes an editable input field
4. Customer types an amount — after 800ms the order total updates automatically
5. Customer completes the checkout form and places the order
6. The order is recorded with the customer's entered amount

---

## Changelog

### 1.4.0
- Removed confirm button; total now updates automatically on input (debounced)

### 1.3.0
- Added `woocommerce_checkout_create_order_line_item` hook to ensure correct amount is saved to order

### 1.2.0
- Added confirm button alongside the subtotal input field

### 1.1.0
- Replaced subtotal display with editable input field on checkout page

### 1.0.0
- Initial release

---

## Author

[MooSpace](https://moospace.tw)
