<?php
if (!defined('ABSPATH')) exit;

class Vasetah_WhatsApp_Support {

    private $options;

    public function __construct($options) {
        $this->options = $options['whatsapp'] ?? [];
        if (empty($this->options['phone_number'])) return;

        $hook = $this->options['button_position'] ?? 'woocommerce_single_product_summary';
        $priority = $this->options['button_priority'] ?? 35;

        add_action($hook, array($this, 'render_whatsapp_button'), $priority);
    }

    public function render_whatsapp_button() {
        global $product;
        if (!is_a($product, 'WC_Product')) return;
        
        $phone_number = esc_attr($this->options['phone_number']);
        $button_text = esc_html($this->options['button_text'] ?? 'پشتیبانی در واتساپ');
        $button_color = esc_attr($this->options['button_color'] ?? '#25D366');
        
        $template = $this->options['message_template'] ?? "سلام، در مورد محصول '{product_title}' سوال داشتم. \n {product_link}";
        $message = str_replace(
            ['{product_title}', '{product_link}'],
            [$product->get_name(), get_permalink($product->get_id())],
            $template
        );
        $encoded_message = rawurlencode($message);

        $whatsapp_url = "https://wa.me/{$phone_number}?text={$encoded_message}";
        
        $style = "background-color: {$button_color}; color: #fff; padding: 10px 15px; border-radius: 5px; text-decoration: none; display: inline-block; text-align: center; margin-top: 10px;";

        echo '<a href="' . esc_url($whatsapp_url) . '" target="_blank" class="vasetah-whatsapp-button" style="' . $style . '">' . $button_text . '</a>';
    }
}