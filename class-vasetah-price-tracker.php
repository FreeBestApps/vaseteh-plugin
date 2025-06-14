<?php
if (!defined('ABSPATH')) exit;

class Vasetah_Price_Tracker {

    private $options;
    private $price_tracker_options;

    public function __construct($options) {
        $this->options = $options;
        $this->price_tracker_options = $this->options['price_tracker'] ?? [];

        add_action('save_post', array($this, 'save_last_price_update_date'), 10, 3);
        add_action('woocommerce_rest_insert_product_object', array($this, 'save_last_price_update_via_rest_api'), 10, 2);
        add_shortcode('last_price_update', array($this, 'display_last_price_update_shortcode'));
        add_shortcode('vasetah_last_price_update', array($this, 'display_last_price_update_shortcode'));
    }

    public function save_last_price_update_date($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if ($post->post_type !== 'product') return;
        $product = wc_get_product($post_id);
        if (!$product) return;
        $current_price = $product->get_price();
        $last_saved_price = get_post_meta($post_id, '_last_saved_price', true);
        if ($current_price !== '' && $current_price !== $last_saved_price) {
            update_post_meta($post_id, '_last_price_update', current_time('mysql'));
            update_post_meta($post_id, '_last_saved_price', $current_price);
        }
    }

    public function save_last_price_update_via_rest_api($product, $request) {
        $product_id = $product->get_id();
        $current_price = $product->get_price();
        $last_saved_price = get_post_meta($product_id, '_last_saved_price', true);
        if ($current_price !== '' && $current_price !== $last_saved_price) {
            update_post_meta($product_id, '_last_price_update', current_time('mysql'));
            update_post_meta($product_id, '_last_saved_price', $current_price);
        }
    }

    public function display_last_price_update_shortcode() {
        global $product;
        if (!is_a($product, 'WC_Product')) return '';

        $display_mode = $this->price_tracker_options['display_mode'] ?? 'actual_date';
        $date_to_show = '';

        if ($display_mode === 'current_date') {
            // حالت نمایش تاریخ روز جاری
            $date_to_show = current_time('mysql');
        } else {
            // حالت نمایش تاریخ واقعی بروزرسانی
            $product_id = $product->get_id();
            $last_update = get_post_meta($product_id, '_last_price_update', true);
            if ($last_update) {
                $date_to_show = $last_update;
            } else {
                $post_obj = get_post($product_id);
                $date_to_show = $post_obj ? $post_obj->post_date : '';
            }
        }

        if (empty($date_to_show)) return '';

        $formatted_date = date_i18n('Y/m/d', strtotime($date_to_show));
        return '<div class="last-price-update">آخرین بروزرسانی قیمت: ' . esc_html($formatted_date) . '</div>';
    }
}