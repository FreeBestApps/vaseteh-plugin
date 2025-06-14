<?php
/*
Plugin Name: واسطه (Vasetah)
Plugin URI:  https://t.me/miuein
Description: افزونه جامغه فروش بر مبنای واسطه گری
Version:     2.0.0
Author:      معین کاظمی
Author URI:  https://moein-kazemi.ir
License:     GPL-2.0+
Text Domain: vasetah
Requires at least: 6.0
Requires PHP: 7.4
WC requires at least: 8.0
*/

if (!defined('ABSPATH')) exit;

final class Vasetah_Plugin_Manager {

    private static $instance = null;
    private $options = [];

    private function __construct() {
        $this->options = get_option('vasetah_settings', []);
        
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_not_active_notice'));
            return;
        }
        
        load_plugin_textdomain('vasetah', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        $this->load_modules();
    }

    public function load_modules() {
        $plugin_path = plugin_dir_path(__FILE__);
        
        require_once $plugin_path . 'includes/class-vasetah-admin-settings.php';
        new Vasetah_Admin_Settings();

        if (!empty($this->options['modules']['quote_system'])) {
            require_once $plugin_path . 'includes/class-vasetah-quote-handler.php';
            require_once $plugin_path . 'includes/class-vasetah-quote-gateway.php';
            new Vasetah_Quote_Handler($this->options);
        }

        if (!empty($this->options['modules']['price_tracker'])) {
         require_once $plugin_path . 'includes/class-vasetah-price-tracker.php';
         new Vasetah_Price_Tracker($this->options);
      }

        if (!empty($this->options['modules']['support_buttons'])) {
            require_once $plugin_path . 'includes/class-vasetah-support-buttons.php';
            new Vasetah_Support_Buttons($this->options);
        }
    }

    public function woocommerce_not_active_notice() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>افزونه واسطه:</strong> برای کار کردن این افزونه، باید ووکامرس نصب و فعال باشد.';
        echo '</p></div>';
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

Vasetah_Plugin_Manager::get_instance();
