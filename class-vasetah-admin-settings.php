<?php
if (!defined('ABSPATH')) exit;

class Vasetah_Admin_Settings {

    private $options;

    public function __construct() {
        $this->options = get_option('vaseteh_settings', []);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos(<span class="math-inline">hook, 'vaseteh\-settings'\) \=\=\= false\) return;
wp\_enqueue\_style\('wp\-color\-picker'\);
wp\_enqueue\_style\('woocommerce\_admin\_styles'\);
wp\_enqueue\_script\('wc\-enhanced\-select'\);
wp\_enqueue\_script\('wp\-color\-picker'\);
wp\_add\_inline\_script\('wc\-enhanced\-select', 'jQuery\(function\(</span>){ <span class="math-inline">\(document\.body\)\.trigger\("wc\-enhanced\-select\-init"\); \}\);'\);
wp\_add\_inline\_script\('wp\-color\-picker', 'jQuery\(function\(</span>){ $(".color-picker-field").wpColorPicker(); });');

        wp_enqueue_script(
            'vaseteh-admin-preview',
            plugin_dir_url(__FILE__) . '../assets/js/admin-preview.js',
            ['jquery', 'wp-color-picker'],
            '2.1.0',
            true
        );
    }

    public function add_admin_menu() {
        add_menu_page(__('پیکربندی واسطه', 'vaseteh'), __('واسطه', 'vaseteh'), 'manage_woocommerce', 'vaseteh-settings', array($this, 'settings_page_html'), 'dashicons-store', 56);
    }
    
    public function settings_page_html() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'modules';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <nav class="nav-tab-wrapper">
                <a href="?page=vaseteh-settings&tab=modules" class="nav-tab <?php echo $active_tab == 'modules' ? 'nav-tab-active' : ''; ?>"><?php _e('ماژول‌ها', 'vaseteh'); ?></a>
                <a href="?page=vaseteh-settings&tab=quote_rules" class="nav-tab <?php echo $active_tab == 'quote_rules' ? 'nav-tab-active' : ''; ?>"><?php _e('پیش‌فاکتور: قوانین', 'vaseteh'); ?></a>
                <a href="?page=vaseteh-settings&tab=support_button" class="nav-tab <?php echo $active_tab == 'support_button' ? 'nav-tab-active' : ''; ?>"><?php _e('دکمه پشتیبانی', 'vaseteh'); ?></a>
                <a href="?page=vaseteh-settings&tab=price_tracker" class="nav-tab <?php echo $active_tab == 'price_tracker' ? 'nav-tab-active' : ''; ?>"><?php _e('ردیاب قیمت', 'vaseteh'); ?></a>
            </nav>
            <form action="options.php" method="post" id="vaseteh-settings-form">
                <?php
                settings_fields('vaseteh_settings_group');
                switch($active_tab) {
                    case 'quote_rules': do_settings_sections('vaseteh_settings_quote_rules'); break;
                    case 'support_button': do_settings_sections('vaseteh_settings_support_button'); break;
                    case 'price_tracker': do_settings_sections('vaseteh_settings_price_tracker'); break;
                    case 'modules': default: do_settings_sections('vaseteh_settings_modules'); break;
                }
                submit_button(__('ذخیره تنظیمات', 'vaseteh'));
                ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('vaseteh_settings_group', 'vaseteh_settings', array($this, 'sanitize_settings'));

        // Tab: Modules
        add_settings_section('vaseteh_modules_section', __('فعال‌سازی ماژول‌ها', 'vaseteh'), null, 'vaseteh_settings_modules');
        add_settings_field('quote_system', __('سیستم پیش‌فاکتور', 'vaseteh'), array($this, 'render_checkbox'), 'vaseteh_settings_modules', 'vaseteh_modules_section', ['id' => 'modules[quote_system]']);
        add_settings_field('price_tracker', __('ردیاب قیمت', 'vaseteh'), array($this, 'render_checkbox'), 'vaseteh_settings_modules', 'vaseteh_modules_section', ['id' => 'modules[price_tracker]']);
        add_settings_field('whatsapp_support', __('دکمه پشتیبانی', 'vaseteh'), array($this, 'render_checkbox'), 'vaseteh_settings_modules', 'vaseteh_modules_section', ['id' => 'modules[whatsapp_support]']);
        
        // Tab: Quote Rules
        add_settings_section('vaseteh_quote_rules_section', __('قوانین اعمال پیش‌فاکتور', 'vaseteh'), null, 'vaseteh_settings_quote_rules');
        add_settings_field('quote_mode', __('حالت پیش‌فاکتور', 'vaseteh'), array($this, 'render_quote_mode_select'), 'vaseteh_settings_quote_rules', 'vaseteh_quote_rules_section', ['id' => 'quote_rules[mode]']);
        add_settings_field('quote_products', __('محصولات پیش‌فاکتور', 'vaseteh'), array($this, 'render_product_select'), 'vaseteh_settings_quote_rules', 'vaseteh_quote_rules_section', ['id' => 'quote_rules[products]']);
        add_settings_field('quote_categories', __('دسته‌بندی‌های پیش‌فاکتور', 'vaseteh'), array($this, 'render_category_select'), 'vaseteh_settings_quote_rules', 'vaseteh_quote_rules_section', ['id' => 'quote_rules[categories]']);
        add_settings_field('quote_excluded_products', __('محصولات استثنا', 'vaseteh'), array($this, 'render_product_select'), 'vaseteh_settings_quote_rules', 'vaseteh_quote_rules_section', ['id' => 'quote_rules[excluded_products]']);
        add_settings_field('checkout_message', __('پیام صفحه تسویه حساب', 'vaseteh'), array($this, 'render_textarea'), 'vaseteh_settings_quote_rules', 'vaseteh_quote_rules_section', ['id' => 'quote_rules[checkout_message]']);
        
        // Tab: Support Button
        add_settings_section('vaseteh_support_button_section', __('تنظیمات دکمه پشتیبانی', 'vaseteh'), array($this, 'render_support_button_header'), 'vaseteh_settings_support_button');
        add_settings_field('support_url', __('لینک مقصد دکمه', 'vaseteh'), array($this, 'render_text_input'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[url]', 'desc' => 'لینک کامل مقصد را وارد کنید. مثال: https://wa.me/989120000000 یا https://t.me/your_id']);
        add_settings_field('support_icon', __('آیکون دکمه', 'vaseteh'), array($this, 'render_icon_select'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[icon]']);
        add_settings_field('support_text_line1', __('متن خط اول', 'vaseteh'), array($this, 'render_text_input'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[text_line1]']);
        add_settings_field('support_text_line1_style', __('استایل خط اول', 'vaseteh'), array($this, 'render_font_style_select'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[text_line1_style]']);
        add_settings_field('support_text_line2', __('متن خط دوم', 'vaseteh'), array($this, 'render_text_input'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[text_line2]']);
        add_settings_field('support_text_line2_style', __('استایل خط دوم', 'vaseteh'), array($this, 'render_font_style_select'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[text_line2_style]']);
        add_settings_field('support_button_color', __('رنگ پس‌زمینه دکمه', 'vaseteh'), array($this, 'render_color_picker'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[bg_color]']);
        add_settings_field('support_text_color', __('رنگ متن دکمه', 'vaseteh'), array($this, 'render_color_picker'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[text_color]', 'default' => '#FFFFFF']);
        add_settings_field('support_border_radius', __('گردی گوشه‌ها (پیکسل)', 'vaseteh'), array($this, 'render_number_input'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[border_radius]', 'default' => '8']);
        add_settings_field('support_online_mode', __('وضعیت آنلاین', 'vaseteh'), array($this, 'render_online_mode_select'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[online_mode]', 'desc' => 'با انتخاب حالت زمانبندی شده، چراغ وضعیت فقط در ساعات کاری مشخص شده روشن خواهد بود.']);
        add_settings_field('support_work_hours_start', __('ساعت شروع کار', 'vaseteh'), array($this, 'render_text_input'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[work_hours_start]', 'placeholder' => 'مثال: 09:00']);
        add_settings_field('support_work_hours_end', __('ساعت پایان کار', 'vaseteh'), array($this, 'render_text_input'), 'vaseteh_settings_support_button', 'vaseteh_support_button_section', ['id' => 'support_button[work_hours_end]', 'placeholder' => 'مثال: 18:00']);

        // Tab: Price Tracker
        add_settings_section('vaseteh_price_tracker_section', __('تنظیمات ردیاب قیمت', 'vaseteh'), array($this, 'render_price_tracker_section_header'), 'vaseteh_settings_price_tracker');
        add_settings_field('price_tracker_display_mode', __('حالت نمایش تاریخ', 'vaseteh'), array($this, 'render_price_tracker_mode_select'), 'vaseteh_settings_price_tracker', 'vaseteh_price_tracker_section', ['id' => 'price_tracker[display_mode]']);
    }

    private function get_option_value($id_string) { sscanf($id_string, '%[^[][%[^]]]', $group, $field); return $this->options[$group][$field] ?? null; }
    private function get_field_name($id_string, $is_multi = false) { sscanf($id_string, '%[^[][%[^]]]', $group, $field); $name = "vaseteh_settings[{$group}][{$field}]"; if ($is_multi) $name .= '[]'; return $name; }

    public function render_checkbox($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']); echo '<input type="checkbox" name="' . esc_attr($field_name) . '" value="1" ' . checked('1', $value, false) . '/>'; }
    public function render_textarea($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']); echo '<textarea rows="5" style="width: 50%;" class="large-text" name="' . esc_attr($field_name) . '">' . esc_textarea($value) . '</textarea>'; }
    public function render_color_picker($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']); echo '<input type="text" class="color-picker-field" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '"/>'; }
    public function render_quote_mode_select($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']) ?: 'none'; echo '<select name="' . esc_attr($field_name) . '"><option value="none" ' . selected($value, 'none', false) . '>غیرفعال</option><option value="specific_products" ' . selected($value, 'specific_products', false) . '>فقط محصولات مشخص</option><option value="specific_categories" ' . selected($value, 'specific_categories', false) . '>فقط دسته‌بندی‌های مشخص</option><option value="all_except_excluded" ' . selected($value, 'all_except_excluded', false) . '>همه بجز استثنائات</option></select>'; }
    public function render_product_select($args) { $field_name = $this->get_field_name($args['id'], true); $saved_ids = (array) ($this->get_option_value($args['id']) ?: []); echo '<select class="wc-product-search" multiple="multiple" style="width: 50%;" name="' . esc_attr($field_name) . '" data-placeholder="' . esc_attr__( 'یک محصول را جستجو کنید…', 'woocommerce' ) . '" data-action="woocommerce_json_search_products_and_variations">'; foreach ( $saved_ids as $product_id ) { $product = wc_get_product( $product_id ); if ( is_object( $product ) ) { echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product->get_formatted_name() ) . '</option>'; } } echo '</select>'; }
    public function render_category_select($args) { $field_name = $this->get_field_name($args['id'], true); $saved_ids = (array) ($this->get_option_value($args['id']) ?: []); $categories = get_terms('product_cat', ['hide_empty' => false]); echo '<select multiple="multiple" name="' . esc_attr($field_name) . '" class="wc-enhanced-select" style="width: 50%;">'; foreach ($categories as $category) { echo '<option value="' . esc_attr($category->term_id) . '"' . selected(in_array($category->term_id, $saved_ids), true, false) . '>' . esc_html($category->name) . '</option>'; } echo '</select>'; }
    public function render_price_tracker_section_header() { echo '<p>' . __('برای نمایش تاریخ در صفحه محصولات، از شورت‌کد زیر استفاده کنید:', 'vaseteh') . '</p>' . '<p><code>[last_price_update]</code></p>'; }
    public function render_price_tracker_mode_select($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']) ?: 'actual_date'; echo '<select name="' . esc_attr($field_name) . '"><option value="actual_date" ' . selected($value, 'actual_date', false) . '>نمایش تاریخ آخرین بروزرسانی واقعی</option><option value="current_date" ' . selected($value, 'current_date', false) . '>نمایش تاریخ روز جاری</option></select><p class="description">' . esc_html__('انتخاب کنید که شورت‌کد کدام تاریخ را نمایش دهد.', 'vaseteh') . '</p>'; }

    public function render_text_input($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']); $placeholder = $args['placeholder'] ?? ''; echo '<input type="text" class="regular-text" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($placeholder) . '"/>'; if (!empty($args['desc'])) { echo '<p class="description">' . wp_kses_post($args['desc']) . '</p>'; } }
    public function render_number_input($args) { $field_name = $this->get_field_name($args['id']); $value = $this->get_option_value($args['id']) ?? ($args['default'] ?? ''); echo '<input type="number" class="regular-text" name="' . esc_attr($field_name) . '" value="' . esc_attr($value) . '"/>'; if (!empty($args['desc'])) { echo '<p class="description">' . esc_html($args['desc']) . '</p>'; } }
    
    public function render_support_button_header() {
        echo '<p>' . __('برای نمایش دکمه پشتیبانی در هر جای سایت، از شورت‌کد زیر استفاده کنید:', 'vaseteh') . '</p>' . '<p><code>[vaseteh_support_button]</code></p>';
        echo '<h4 style="margin-top: 2em;">' . __('پیش‌نمایش زنده', 'vaseteh') . '</h4>';
        echo '<div id="support-button-preview-wrapper" style="padding: 20px; background: #f0f0f1; border-radius: 5px; margin-top: 15px; width: fit-content;">' .
             '<a id="support-button-preview" href="#" onclick="event.preventDefault();" target="_blank" style="display: inline-flex; align-items: center; padding: 10px 15px; text-decoration: none; position: relative; color: white;">' .
                 '<span id="preview-online-dot" style="width: 10px; height: 10px; background-color: #28a745; border-radius: 50%; position: absolute; top: -5px; right: -5px; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.3);"></span>' .
                 '<div id="preview-icon" style="margin-left: 8px; line-height: 0;"></div>' .
                 '<div id="preview-texts">' .
                     '<span id="preview-line1" style="display: block; font-size: 14px; line-height: 1.2;"></span>' .
                     '<span id="preview-line2" style="display: block; font-size: 12px; line-height: 1.2;"></span>' .
                 '</div>' .
             '</a>' .
        '</div>';
    }

    public function render_icon_select($args) {
        $field_name = $this->get_field_name($args['id']);
        $value = $this->get_option_value($args['id']) ?: 'whatsapp';
        $options = ['whatsapp' => 'واتساپ', 'telegram' => 'تلگرام', 'chat' => 'گفتگوی عمومی', 'phone' => 'تماس تلفنی'];
        echo '<select name="' . esc_attr($field_name) . '" id="support_icon_select">';
        foreach ($options as $key => $label) { echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>'; }
        echo '</select>';
    }

    public function render_font_style_select($args) {
        $field_name = $this->get_field_name($args['id']);
        $value = $this->get_option_value($args['id']) ?: 'normal';
        $options = ['normal' => 'عادی', 'bold' => 'ضخیم (Bold)', 'italic' => 'کج (Italic)'];
        echo '<select name="' . esc_attr($field_name) . '">';
        foreach ($options as $key => $label) { echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>'; }
        echo '</select>';
    }

    public function render_online_mode_select($args) {
        $field_name = $this->get_field_name($args['id']);
        $value = $this->get_option_value($args['id']) ?: 'always';
        $options = ['always' => 'همیشه آنلاین', 'scheduled' => 'زمانبندی شده', 'disabled' => 'غیرفعال'];
        echo '<select name="' . esc_attr($field_name) . '">';
        foreach ($options as $key => $label) { echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>'; }
        echo '</select>'; if (!empty($args['desc'])) { echo '<p class="description">' . esc_html($args['desc']) . '</p>'; }
    }

    public function sanitize_settings($input) {
        $output = get_option('vaseteh_settings', []);
        if (empty($input)) $input = [];
        $active_tab = 'modules';
        if (isset($_POST['_wp_http_referer'])) {
            wp_parse_str(parse_url(wp_unslash($_POST['_wp_http_referer']), PHP_URL_QUERY), $query_params);
            if (!empty($query_params['tab'])) $active_tab = $query_params['tab'];
        }
        $output = array_replace_recursive($output, $input);

        if ($active_tab === 'modules') {
            $output['modules']['quote_system'] = !empty($input['modules']['quote_system']) ? '1' : '0';
            $output['modules']['price_tracker'] = !empty($input['modules']['price_tracker']) ? '1' : '0';
            $output['modules']['whatsapp_support'] = !empty($input['modules']['whatsapp_support']) ? '1' : '0';
        } 
        elseif ($active_tab === 'quote_rules') {
            $output['quote_rules']['mode'] = sanitize_text_field($input['quote_rules']['mode'] ?? 'none');
            $output['quote_rules']['products'] = isset($input['quote_rules']['products']) ? array_map('intval', (array)$input['quote_rules']['products']) : [];
            $output['quote_rules']['categories'] = isset($input['quote_rules']['categories']) ? array_map('intval', (array)$input['quote_rules']['categories']) : [];
            $output['quote_rules']['excluded_products'] = isset($input['quote_rules']['excluded_products']) ? array_map('intval', (array)$input['quote_rules']['excluded_products']) : [];
            $output['quote_rules']['checkout_message'] = isset($input['quote_rules']['checkout_message']) ? wp_kses_post($input['quote_rules']['checkout_message']) : '';
        }
        elseif ($active_tab === 'support_button') {
            $sb = $input['support_button'] ?? [];
            $output['support_button']['url'] = esc_url_raw($sb['url'] ?? '');
            $output['support_button']['icon'] = sanitize_key($sb['icon'] ?? 'whatsapp');
            $output['support_button']['text_line1'] = sanitize_text_field($sb['text_line1'] ?? '');
            $output['support_button']['text_line1_style'] = sanitize_key($sb['text_line1_style'] ?? 'normal');
