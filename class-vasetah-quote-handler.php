<?php
if (!defined('ABSPATH')) exit;

class Vasetah_Quote_Handler {

    private $options;
    private $quote_rules;
    private static $cart_contains_quote_product = null; // Cache the result

    public function __construct($options) {
        $this->options = $options;
        $this->quote_rules = $this->options['quote_rules'] ?? [];
        
        // Register custom order status
        add_action('init', array($this, 'register_quote_order_status'));
        add_filter('wc_order_statuses', array($this, 'add_quote_order_status_to_list'));

        // Product page hooks
        add_filter('woocommerce_is_purchasable', array($this, 'is_purchasable'), 10, 2);
        add_filter('woocommerce_product_add_to_cart_text', array($this, 'change_add_to_cart_text'), 10, 2);
        add_filter('woocommerce_product_single_add_to_cart_text', array($this, 'change_add_to_cart_text'), 10, 2);

        // Checkout process hooks
        add_filter('woocommerce_available_payment_gateways', array($this, 'filter_gateways'), PHP_INT_MAX);
        add_filter('woocommerce_checkout_posted_data', array($this, 'force_quote_gateway'));
        add_action('woocommerce_checkout_process', array($this, 'validate_gateway'));
        add_filter('woocommerce_order_button_text', array($this, 'change_place_order_button_text'));
        add_action('woocommerce_before_checkout_form', array($this, 'display_checkout_message'), 5);

        // Order creation hooks
        add_action('woocommerce_checkout_create_order', array($this, 'save_order_meta'), 10, 2);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'set_order_status'), 10, 1);
    }

    private function is_quote_product($product_id) {
        $mode = $this->quote_rules['mode'] ?? 'none';
        if ($mode === 'none') return false;

        $excluded_products = $this->quote_rules['excluded_products'] ?? [];
        if (in_array($product_id, $excluded_products)) return false;

        if ($mode === 'specific_products') {
            $quote_products = $this->quote_rules['products'] ?? [];
            return in_array($product_id, $quote_products);
        }
        if ($mode === 'specific_categories') {
            $quote_categories = $this->quote_rules['categories'] ?? [];
            return has_term($quote_categories, 'product_cat', $product_id);
        }
        if ($mode === 'all_except_excluded') {
            return true;
        }
        return false;
    }

    private function cart_has_quote_product() {
        // Optimization: Check the cart only once per request and cache the result.
        if (self::$cart_contains_quote_product !== null) {
            return self::$cart_contains_quote_product;
        }

        if (!WC()->cart) {
            self::$cart_contains_quote_product = false;
            return false;
        }

        foreach (WC()->cart->get_cart() as $cart_item) {
            if ($this->is_quote_product($cart_item['product_id'])) {
                self::$cart_contains_quote_product = true;
                return true;
            }
        }
        
        self::$cart_contains_quote_product = false;
        return false;
    }
    
    public function is_purchasable($purchasable, $product) {
        if ($this->is_quote_product($product->get_id())) return true;
        return $purchasable;
    }

    public function change_add_to_cart_text($text, $product) {
        if ($this->is_quote_product($product->get_id())) {
            return __('ثبت درخواست پیش‌فاکتور', 'vasetah');
        }
        return $text;
    }

    public function filter_gateways($gateways) {
        $quote_gateway_id = 'vasetah_quote_gateway';
        if ($this->cart_has_quote_product()) {
            if (isset($gateways[$quote_gateway_id])) {
                return [$quote_gateway_id => $gateways[$quote_gateway_id]];
            }
            return [];
        } else {
            unset($gateways[$quote_gateway_id]);
        }
        return $gateways;
    }

    public function force_quote_gateway($data) {
        if ($this->cart_has_quote_product()) {
            $data['payment_method'] = 'vasetah_quote_gateway';
        }
        return $data;
    }

    public function validate_gateway() {
        if ($this->cart_has_quote_product() && WC()->session->get('chosen_payment_method') !== 'vasetah_quote_gateway') {
            wc_add_notice(__('برای این سفارش فقط امکان ثبت پیش‌فاکتور وجود دارد.', 'vasetah'), 'error');
        }
    }

    public function change_place_order_button_text($text) {
        if ($this->cart_has_quote_product()) {
            return __('ثبت درخواست پیش‌فاکتور', 'vasetah');
        }
        return $text;
    }
    
    public function display_checkout_message() {
        if ($this->cart_has_quote_product()) {
            $message = $this->quote_rules['checkout_message'] ?? '';
            if ($message) {
                wc_print_notice($message, 'notice');
            }
        }
    }

    public function save_order_meta($order, $data) {
        if ($this->cart_has_quote_product()) {
            $order->update_meta_data('_is_vasetah_quote_order', 'yes');
        }
    }

    public function set_order_status($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_meta('_is_vasetah_quote_order') === 'yes') {
            $order->update_status('wc-vasetah-quote', __('سفارش به صورت پیش‌فاکتور ثبت شد.', 'vasetah'));
        }
    }

    public function register_quote_order_status() {
        register_post_status('wc-vasetah-quote', [
            'label' => _x('در انتظار تایید پیش‌فاکتور', 'Order status', 'vasetah'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('در انتظار تایید <span class="count">(%s)</span>', 'در انتظار تایید <span class="count">(%s)</span>', 'vasetah')
        ]);
    }

    public function add_quote_order_status_to_list($order_statuses) {
        $new_statuses = [];
        foreach ($order_statuses as $key => $status) {
            $new_statuses[$key] = $status;
            if ('wc-on-hold' === $key) {
                $new_statuses['wc-vasetah-quote'] = _x('در انتظار تایید پیش‌فاکتور', 'Order status', 'vasetah');
            }
        }
        return $new_statuses;
    }
}
