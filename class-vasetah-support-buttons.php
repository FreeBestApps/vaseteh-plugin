<?php
if (!defined('ABSPATH')) exit;

class Vasetah_Support_Button {

    private $options;

    public function __construct($options) {
        $this->options = $options['support_button'] ?? [];
        add_shortcode('vaseteh_support_button', array($this, 'render_shortcode'));
    }

    private function is_currently_online() {
        $mode = $this->options['online_mode'] ?? 'disabled';
        if ($mode === 'always') return true;
        if ($mode === 'disabled') return false;

        $start_time_str = $this->options['work_hours_start'] ?? '09:00';
        $end_time_str = $this->options['work_hours_end'] ?? '18:00';
        
        try {
            $timezone = new DateTimeZone(wp_timezone_string());
            $start_time = new DateTime($start_time_str, $timezone);
            $end_time = new DateTime($end_time_str, $timezone);
            $current_time = new DateTime('now', $timezone);
            return ($current_time >= $start_time && $current_time < $end_time);
        } catch (Exception $e) {
            return false;
        }
    }

    private function get_font_style_css($style_key) {
        $css = '';
        if ($style_key === 'bold') $css = 'font-weight: 700;';
        if ($style_key === 'italic') $css = 'font-style: italic;';
        return $css;
    }
    
    private function get_icon_svg($icon_key) {
        $svgs = [
            'whatsapp' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M16.6 14c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.6.8-.8 1-.1.2-.3.2-.5.1-.7-.3-1.4-.7-2-1.2-.5-.5-1-1.1-1.4-1.7-.1-.2 0-.4.1-.5.1-.1.2-.3.4-.4.1-.1.2-.2.2-.3.1-.1.1-.3 0-.4-.1-.1-1.5-2.2-1.7-2.7-.2-.5-.4-.4-.5-.4h-.5c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 1.9 0 1.2.8 2.2 1 2.4.1.2 1.5 2.3 3.7 3.2.5.2.9.4 1.2.5.5.2 1 .1 1.4-.1.4-.2.6-.4.8-.8.2-.3.2-.6.1-.8l-.2-.3zM12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8z"/></svg>',
            'telegram' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M22 2L2 9l7 3 2 7 5-4 5 7V2z"/></svg>',
            'chat'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 4H6v-2h8v2zm4-4H6V7h12v2z"/></svg>',
            'phone'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M6.6 10.8c1.5 2.8 3.9 5.3 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.2.2 2.4.6 3.6.1.3 0 .7-.2 1l-2.2 2.2z"/></svg>',
        ];
        return $svgs[$icon_key] ?? '';
    }

    public function render_shortcode($atts) {
        if (empty($this->options['url'])) return '';

        $url = esc_url($this->options['url']);
        $icon_key = $this->options['icon'] ?? 'whatsapp';
        $line1 = esc_html($this->options['text_line1'] ?? '');
        $line2 = esc_html($this->options['text_line2'] ?? '');
        $line1_style = $this->get_font_style_css($this->options['text_line1_style'] ?? 'normal');
        $line2_style = $this->get_font_style_css($this->options['text_line2_style'] ?? 'normal');
        $bg_color = esc_attr($this->options['bg_color'] ?? '#25D366');
        $text_color = esc_attr($this->options['text_color'] ?? '#FFFFFF');
        $border_radius = absint($this->options['border_radius'] ?? 8);
        $is_online = $this->is_currently_online();
        
        $style = "background-color: {$bg_color}; color: {$text_color}; border-radius: {$border_radius}px; padding: 10px 15px; display: inline-flex; align-items: center; text-decoration: none; position: relative; line-height: 1.3;";
        
        ob_start();
        ?>
        <a href="<?php echo $url; ?>" target="_blank" class="vaseteh-support-button" style="<?php echo $style; ?>">
            <?php if ($is_online) : ?>
                <span class="vaseteh-online-dot" style="width: 10px; height: 10px; background-color: #28a745; border-radius: 50%; position: absolute; top: -5px; right: -5px; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.3);"></span>
            <?php endif; ?>
            
            <div class="vaseteh-button-icon" style="margin-left: 8px; line-height: 0;">
                <?php echo str_replace('<svg', '<svg fill="' . $text_color . '"', $this->get_icon_svg($icon_key)); ?>
            </div>

            <div class="vaseteh-button-texts">
                <?php if ($line1) : ?>
                    <span class="vaseteh-line-1" style="display: block; font-size: 14px; <?php echo $line1_style; ?>"><?php echo $line1; ?></span>
                <?php endif; ?>
                 <?php if ($line2) : ?>
                    <span class="vaseteh-line-2" style="display: block; font-size: 12px; opacity: 0.9; <?php echo $line2_style; ?>"><?php echo $line2; ?></span>
                <?php endif; ?>
            </div>
        </a>
        <?php
        return ob_get_clean();
    }
}
