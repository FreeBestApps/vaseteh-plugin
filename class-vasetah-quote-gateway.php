<?php
if (!defined('ABSPATH')) exit;

add_filter('woocommerce_payment_gateways', 'vasetah_add_quote_payment_gateway');
function vasetah_add_quote_payment_gateway($gateways) {
    $gateways[] = 'Vasetah_WC_Quote_Gateway';
    return $gateways;
}

class Vasetah_WC_Quote_Gateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'vasetah_quote_gateway';
        $this->method_title       = __('ثبت به عنوان پیش‌فاکتور (واسطه)', 'vasetah');
        $this->method_description = __('این درگاه مجازی به مشتریان اجازه می‌دهد سفارش خود را به عنوان درخواست پیش‌فاکتور ثبت کنند. پرداخت در این مرحله انجام نمی‌شود.', 'vasetah');
        $this->has_fields         = false;
        
        $this->init_form_fields();
        $this->init_settings();

        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->instructions = $this->get_option('instructions');
        
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => ['title' => __('فعال/غیرفعال', 'vasetah'), 'type' => 'checkbox', 'label' => __('فعال‌سازی درگاه پیش‌فاکتور', 'vasetah'), 'default' => 'yes'],
            'title' => ['title' => __('عنوان', 'vasetah'), 'type' => 'text', 'description' => __('عنوانی که کاربر در صفحه تسویه حساب می‌بیند.', 'vasetah'), 'default' => __('ثبت درخواست پیش‌فاکتور', 'vasetah'), 'desc_tip' => true],
            'description' => ['title' => __('توضیحات', 'vasetah'), 'type' => 'textarea', 'description' => __('توضیحاتی که زیر عنوان درگاه نمایش داده می‌شود.', 'vasetah'), 'default' => 'سفارش شما پس از بررسی توسط تیم فروش نهایی خواهد شد.', 'desc_tip' => true],
            'instructions' => ['title' => __('دستورالعمل‌ها', 'vasetah'), 'type' => 'textarea', 'description' => __('دستورالعمل‌هایی که در صفحه تشکر و ایمیل به کاربر نمایش داده می‌شود.', 'vasetah'), 'default' => 'پیش‌فاکتور شما با موفقیت ثبت شد. به زودی با شما تماس خواهیم گرفت.', 'desc_tip' => true],
        ];
    }
    
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Do not mark as "payment complete" for a quote request.
        // This was incorrect as no payment is being made. The order status
        // will be updated to "wc-vasetah-quote" by the Vasetah_Quote_Handler class.
        // $order->payment_complete(); 

        // Note: Stock is reduced here for the quote. This is a business decision.
        // If you want to reduce stock only after quote confirmation, this line should be moved.
        $order->reduce_order_stock();

        WC()->cart->empty_cart();

        return ['result' => 'success', 'redirect' => $this->get_return_url($order)];
    }

    public function thankyou_page() {
        if ($this->instructions) {
            echo wpautop(wptexturize($this->instructions));
        }
    }

    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($this->instructions && !$sent_to_admin && $this->id === $order->get_payment_method()) {
            echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
        }
    }
}
