<?php
if (!defined('ABSPATH')) exit;

class Vasetah_Admin_Settings {

    private $options;

    public function __construct() {
        $this->options = get_option('vasetah_settings', []);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'vasetah-settings') === false) return;
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('woocommerce_admin_styles');
        
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_script('wp-color-picker');
        
        wp_add_inline_script('wc-enhanced-select', 'jQuery(function($){ $(document.body).trigger("wc-enhanced-select-init"); });');
        wp_add_inline_script('wp-color-picker', 'jQuery(function($){ $(".color-picker-field").wpColorPicker(); });');

        // JS for shortcode copy button
        wp_add_inline_script('jquery', '
            jQuery(document).on("click", ".vasetah-copy-shortcode", function(e) {
                e.preventDefault();
                var shortcode = jQuery(this).prev("code").text();
                var tempInput = jQuery("<input>");
                jQuery("body").append(tempInput);
                tempInput.val(shortcode).select();
                document.execCommand("copy");
                tempInput.remove();
                jQuery(this).text("' . esc_js(__('کپی شد!', 'vasetah')) . '");
                setTimeout(function() {
                    jQuery(".vasetah-copy-shortcode").text("' . esc_js(__('کپی کردن', 'vasetah')) . '");
                }, 1500);
            });
        ');
    }

    public function add_admin_menu() {
        add_menu_page(__('پیکربندی واسطه', 'vasetah'), __('واسطه', 'vasetah'), 'manage_woocommerce', 'vasetah-settings', array($this, 'settings_page_html'), 'dashicons-store', 56);
    }
    
    public function settings_page_html() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'modules';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=vasetah-settings&tab=modules" class="nav-tab <?php echo $active_tab == 'modules' ? 'nav-tab-active' : ''; ?>"><?php _e('ماژول‌ها', 'vasetah'); ?></a>
                <a href="?page=vasetah-settings&tab=quote_rules" class="nav-tab <?php echo $active_tab == 'quote_rules' ? 'nav-tab-active' : ''; ?>"><?php _e('پیش‌فاکتور: قوانین', 'vasetah'); ?></a>
                <a href="?page=vasetah-settings&tab=whatsapp" class="nav-tab <?php echo $active_tab == 'whatsapp' ? 'nav-tab-active' : ''; ?>"><?php _e('پشتیبانی واتساپ', 'vasetah'); ?></a>
                <a href="?page=vasetah-settings&tab=price_tracker" class="nav-tab <?php echo $active_tab == 'price_tracker' ? 'nav-tab-active' : ''; ?>"><?php _e('ردیاب قیمت', 'vasetah'); ?></a>
            </nav>
            <form action="options.php" method="post">
                <?php
                settings_fields('vasetah_settings_group');
                switch($active_tab) {
                    case 'quote_rules': do_settings_sections('vasetah_settings_quote_rules'); break;
                    case 'whatsapp': do_settings_sections('vasetah_settings_whatsapp'); break;
                    case 'price_tracker': do_settings_sections('vasetah_settings_price_tracker'); break;
                    case 'modules': default: do_settings_sections('vasetah_settings_modules'); break;
                }
                submit_button(__('ذخیره تنظیمات', 'vasetah'));
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('vasetah_settings_group', 'vasetah_settings', array($this, 'sanitize_settings'));

        // Tab: Modules
        add_settings_section('vasetah_modules_section', __('فعال‌سازی ماژول‌ها', 'vasetah'), null, 'vasetah_settings_modules');
        add_settings_field('quote_system', __('سیستم پیش‌فاکتور', 'vasetah'), array($this, 'render_checkbox'), 'vasetah_settings_modules', 'vasetah_modules_section', ['id' => 'modules[quote_system]']);
        add_settings_field('price_tracker', __('ردیاب قیمت', 'vasetah'), array($this, 'render_checkbox'), 'vasetah_settings_modules', 'vasetah_modules_section', ['id' => 'modules[price_tracker]']);
        add_settings_field('whatsapp_support', __('پشتیبانی واتساپ', 'vasetah'), array($this, 'render_checkbox'), 'vasetah_settings_modules', 'vasetah_modules_section', ['id' => 'modules[whatsapp_support]']);
        
        // Tab: Quote Rules
        add_settings_section('vasetah_quote_rules_section', __('قوانین اعمال پیش‌فاکتور', 'vasetah'), null, 'vasetah_settings_quote_rules');
        add_settings_field('quote_mode', __('حالت پیش‌فاکتور', 'vasetah'), array($this, 'render_quote_mode_select'), 'vasetah_settings_quote_rules', 'vasetah_quote_rules_section', ['id' => 'quote_rules[mode]']);
        add_settings_field('quote_products', __('محصولات پیش‌فاکتور', 'vasetah'), array($this, 'render_product_select'), 'vasetah_settings_quote_rules', 'vasetah_quote_rules_section', ['id' => 'quote_rules[products]']);
        add_settings_field('quote_categories', __('دسته‌بندی‌های پیش‌فاکتور', 'vasetah'), array($this, 'render_category_select'), 'vasetah_settings_quote_rules', 'vasetah_quote_rules_section', ['id' => 'quote_rules[categories]']);
        add_settings_field('quote_excluded_products', __('محصولات استثنا', 'vasetah'), array($this, 'render_product_select'), 'vasetah_settings_quote_rules', 'vasetah_quote_rules_section', ['id' => 'quote_rules[excluded_products]']);
        add_settings_field('checkout_message', __('پیام صفحه تسویه حساب', 'vasetah'), array($this, 'render_textarea'), 'vasetah_settings_quote_rules', 'vasetah_quote_rules_section', ['id' => 'quote_rules[checkout_message]']);
        
        // Tab: WhatsApp
        add_settings_section('vasetah_whatsapp_section', __('تنظیمات دکمه واتساپ', 'vasetah'), null, 'vasetah_settings_whatsapp');
        add_settings_field('phone_number', __('شماره واتساپ', 'vasetah'), array($this, 'render_text_input'), 'vasetah_settings_whatsapp', 'vasetah_whatsapp_section', ['id' => 'whatsapp[phone_number]']);
        add_settings_field('button_text', __('متن دکمه', 'vasetah'), array($this, 'render_text_input'), 'vasetah_settings_whatsapp', 'vasetah_whatsapp_section', ['id' => 'whatsapp[button_text]']);
        add_settings_field('button_color', __('رنگ دکمه', 'vasetah'), array($this, 'render_color_picker'), 'vasetah_settings_whatsapp', 'vasetah_whatsapp_section', ['id' => 'whatsapp[button_color]']);
        add_settings_field('button_position', __('موقعیت دکمه', 'vasetah'), array($this, 'render_whatsapp_position_select'), 'vasetah_settings_whatsapp', 'vasetah_whatsapp_section', ['id' => 'whatsapp[button_position]']);
        add_settings_field('button_priority', __('اولویت نمایش دکمه', 'vasetah'), array($this, 'render_number_input'), 'vasetah_settings_whatsapp', 'vasetah_whatsapp_section', ['id' => 'whatsapp[button_priority]', 'default' => 35]);
        add_settings_field('message_template', __('قالب پیام', 'vasetah'), array($this, 'render_textarea'), 'vasetah_settings_whatsapp', 'vasetah_whatsapp_section', ['id' => 'whatsapp[message_template]', 'rows' => 4, 'description' => __('از {product_title} برای نام محصول و {product_link} برای لینک محصول استفاده کنید.', 'vasetah')]);
        
        // Tab: Price Tracker
        add_settings_section('vasetah_price_tracker_section', __('تنظیمات ردیاب قیمت', 'vasetah'), array($this, 'render_price_tracker_section_header'), 'vasetah_settings_price_tracker');
        add_settings_field('price_tracker_display_mode', __('حالت نمایش تاریخ', 'vasetah'), array($this, 'render_price_tracker_mode_select'), 'vasetah_settings_price_tracker', 'vasetah_price_tracker_section', ['id' => 'price_tracker[display_mode]']);
    }

    // Helper functions to get field names and values correctly
    private function get_option_value($id_string) { sscanf($id_string, '%[^[][%[^]]]', $group, $field); return $this->options[$group][$field] ?? null; }
    private function get_field_name($id_string, $is_multi = false) { sscanf($id_string, '%[^[][%[^]]]', $group, $field); $name = "vasetah_settings[{$group}][{$field}]"; if ($is_multi) $name .= '[]'; return $name; }

    // Render functions for different field types
    public function render_checkbox($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']); echo '<input type="checkbox" name="' . esc_attr($field_name) . '" value="1" ' . checked('1', $value, false) . '/>'; }
    public function render_text_input($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']); echo '<input type="text" class="regular-text" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '"/>'; }
    public function render_number_input($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']) ?? ($args['default'] ?? ''); echo '<input type="number" class="regular-text" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '"/>'; }
    public function render_textarea($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']); $rows = $args['rows'] ?? 5; echo '<textarea rows="' . esc_attr($rows) . '" style="width: 50%;" class="large-text" name="' . esc_attr($field_name) . '">' . esc_textarea($value) . '</textarea>'; if (!empty($args['description'])) echo '<p class="description">' . esc_html($args['description']) . '</p>'; }
    public function render_color_picker($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']); echo '<input type="text" class="color-picker-field" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '"/>'; }
    public function render_quote_mode_select($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']) ?: 'none'; echo '<select name="' . esc_attr($field_name) . '"><option value="none" ' . selected($value, 'none', false) . '>غیرفعال</option><option value="specific_products" ' . selected($value, 'specific_products', false) . '>فقط محصولات مشخص</option><option value="specific_categories" ' . selected($value, 'specific_categories', false) . '>فقط دسته‌بندی‌های مشخص</option><option value="all_except_excluded" ' . selected($value, 'all_except_excluded', false) . '>همه بجز استثنائات</option></select>'; }
    public function render_product_select($args) { $field_name = $this->get_field_name($args['id'], true); $saved_ids = (array) ($this->get_option_value($args['id']) ?: []); echo '<select class="wc-product-search" multiple="multiple" style="width: 50%;" name="' . esc_attr($field_name) . '" data-placeholder="' . esc_attr__( 'یک محصول را جستجو کنید…', 'woocommerce' ) . '" data-action="woocommerce_json_search_products_and_variations">'; foreach ( $saved_ids as $product_id ) { $product = wc_get_product( $product_id ); if ( is_object( $product ) ) { echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>'; } } echo '</select>'; }
    public function render_category_select($args) { $field_name = $this->get_field_name($args['id'], true); $saved_ids = (array) ($this->get_option_value($args['id']) ?: []); $categories = get_terms('product_cat', ['hide_empty' => false]); echo '<select multiple="multiple" name="' . esc_attr($field_name) . '" class="wc-enhanced-select" style="width: 50%;">'; foreach ($categories as $category) { echo '<option value="' . esc_attr($category->term_id) . '"' . selected(in_array($category->term_id, $saved_ids), true, false) . '>' . esc_html($category->name) . '</option>'; } echo '</select>'; }
    
    public function render_price_tracker_section_header() { 
        echo '<p>' . __('برای نمایش تاریخ در صفحه محصولات، از شورت‌کد زیر استفاده کنید:', 'vasetah') . '</p>';
        echo '<p><code>[last_price_update]</code> <a href="#" class="button button-secondary vasetah-copy-shortcode">' . __('کپی کردن', 'vasetah') . '</a></p>';
    }
    public function render_price_tracker_mode_select($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']) ?: 'actual_date'; echo '<select name="' . esc_attr($field_name) . '"><option value="actual_date" ' . selected($value, 'actual_date', false) . '>نمایش تاریخ آخرین بروزرسانی واقعی</option><option value="current_date" ' . selected($value, 'current_date', false) . '>نمایش تاریخ روز جاری</option></select><p class="description">' . esc_html__('انتخاب کنید که شورت‌کد کدام تاریخ را نمایش دهد.', 'vasetah') . '</p>'; }
    
    public function render_whatsapp_position_select($args) {
        $field_name = $this->get_field_name($args['id']);
        $value = $this->get_option_value($args['id']) ?: 'woocommerce_single_product_summary';
        $options = [
            'woocommerce_before_single_product_summary' => 'قبل از خلاصه محصول',
            'woocommerce_single_product_summary' => 'در خلاصه محصول (پیش‌فرض)',
            'woocommerce_before_add_to_cart_form' => 'قبل از فرم افزودن به سبد',
            'woocommerce_before_add_to_cart_button' => 'قبل از دکمه افزودن به سبد',
            'woocommerce_after_add_to_cart_button' => 'بعد از دکمه افزودن به سبد',
            'woocommerce_after_add_to_cart_form' => 'بعد از فرم افزودن به سبد',
            'woocommerce_after_single_product_summary' => 'بعد از خلاصه محصول',
        ];
        echo '<select name="' . esc_attr($field_name) . '">';
        foreach($options as $hook => $label) {
            echo '<option value="' . esc_attr($hook) . '"' . selected($value, $hook, false) . '>' . esc_html__($label, 'vasetah') . '</option>';
        }
        echo '</select>';
    }

    // Master sanitize function to handle all tabs correctly
    public function sanitize_settings($input) {
        $output = get_option('vasetah_settings', []);
        if (empty($input)) $input = [];

        // It's safer to build a new output array rather than replacing recursively
        $new_output = [];
        $tabs_keys = ['modules', 'quote_rules', 'whatsapp', 'price_tracker'];
        foreach($tabs_keys as $key) {
            $new_output[$key] = $output[$key] ?? [];
        }

        // Sanitize modules
        $new_output['modules']['quote_system'] = !empty($input['modules']['quote_system']) ? '1' : '0';
        $new_output['modules']['price_tracker'] = !empty($input['modules']['price_tracker']) ? '1' : '0';
        $new_output['modules']['whatsapp_support'] = !empty($input['modules']['whatsapp_support']) ? '1' : '0';

        // Sanitize quote rules
        if (isset($input['quote_rules'])) {
            $new_output['quote_rules']['mode'] = sanitize_text_field($input['quote_rules']['mode'] ?? 'none');
            $new_output['quote_rules']['products'] = isset($input['quote_rules']['products']) ? array_map('intval', (array)$input['quote_rules']['products']) : [];
            $new_output['quote_rules']['categories'] = isset($input['quote_rules']['categories']) ? array_map('intval', (array)$input['quote_rules']['categories']) : [];
            $new_output['quote_rules']['excluded_products'] = isset($input['quote_rules']['excluded_products']) ? array_map('intval', (array)$input['quote_rules']['excluded_products']) : [];
            $new_output['quote_rules']['checkout_message'] = isset($input['quote_rules']['checkout_message']) ? wp_kses_post($input['quote_rules']['checkout_message']) : '';
        }

        // Sanitize WhatsApp
        if (isset($input['whatsapp'])) {
            $new_output['whatsapp']['phone_number'] = sanitize_text_field($input['whatsapp']['phone_number'] ?? '');
            $new_output['whatsapp']['button_text'] = sanitize_text_field($input['whatsapp']['button_text'] ?? '');
            $new_output['whatsapp']['button_color'] = sanitize_hex_color($input['whatsapp']['button_color'] ?? '');
            $new_output['whatsapp']['button_position'] = sanitize_text_field($input['whatsapp']['button_position'] ?? 'woocommerce_single_product_summary');
            $new_output['whatsapp']['button_priority'] = isset($input['whatsapp']['button_priority']) ? absint($input['whatsapp']['button_priority']) : 35;
            $new_output['whatsapp']['message_template'] = isset($input['whatsapp']['message_template']) ? sanitize_textarea_field($input['whatsapp']['message_template']) : '';
        }

        // Sanitize price tracker
        if (isset($input['price_tracker'])) {
            $new_output['price_tracker']['display_mode'] = sanitize_text_field($input['price_tracker']['display_mode'] ?? 'actual_date');
        }

        return $new_output;
    }
}
