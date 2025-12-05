<?php

 /**
 * Plugin Name: AI Elementor
 * Plugin URI: https://Robo-max.ir
 * Description: ابزار قدرتمند تولید محتوا با هوش مصنوعی برای المنتور - شبیه Elementor AI
 * Version: 2.0.0
 * Author: Robo-Max
 * Text Domain: AI Elementor
 * Domain Path: /languages
 * Plugin URI: https://Robo-max.ir
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Elementor tested up to: 3.18
 * Elementor Pro tested up to: 3.18
 */
 
// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثوابت
define('ELEMENTOR_AI_CONTENT_VERSION', '1.2.0');
define('ELEMENTOR_AI_CONTENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ELEMENTOR_AI_CONTENT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ELEMENTOR_AI_CONTENT_MINIMUM_ELEMENTOR_VERSION', '3.0.0');
define('ELEMENTOR_AI_CONTENT_MINIMUM_PHP_VERSION', '7.4');

/**
 * کلاس اصلی افزونه - نسخه بدون خطا
 */
final class Elementor_AI_Content_Generator {
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private function __construct() {
        // بررسی نیازمندی‌ها در ابتدا
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // بررسی نیازمندی‌ها
        if (!$this->is_compatible()) {
            return;
        }
        
        // بارگذاری زبان
        add_action('init', array($this, 'i18n'));
        
        // بارگذاری فایل‌ها فقط اگر نیازمندی‌ها برقرار باشد
        $this->includes();
        
        // راه‌اندازی هوک‌ها
        $this->init_hooks();
    }
    
    public function i18n() {
        load_plugin_textdomain('elementor-ai-content', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function init_hooks() {
        // منوی مدیریت
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // ثبت تنظیمات
        add_action('admin_init', array($this, 'register_settings'));
        
        // اسکریپت‌ها
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Elementor hooks فقط اگر Elementor فعال باشد
        if (did_action('elementor/loaded')) {
            add_action('elementor/init', array($this, 'init_elementor'));
        }
        
        // Hook برای فعال‌سازی
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    public function is_compatible() {
        // بررسی PHP
        if (version_compare(PHP_VERSION, ELEMENTOR_AI_CONTENT_MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_php_version'));
            return false;
        }
        
        // بررسی WordPress
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_wordpress_version'));
            return false;
        }
        
        return true;
    }
    
    public function includes() {
        // بارگذاری فایل‌ها با بررسی وجود
        $files = array(
            'includes/class-ai-api-handler.php',
            'includes/class-content-templates.php',
        );
        
        foreach ($files as $file) {
            $file_path = ELEMENTOR_AI_CONTENT_PLUGIN_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log('Elementor AI Content Generator: File not found - ' . $file);
            }
        }
    }
    
    public function init_elementor() {
        // بررسی نسخه Elementor
        if (!version_compare(ELEMENTOR_VERSION, ELEMENTOR_AI_CONTENT_MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', array($this, 'admin_notice_minimum_elementor_version'));
            return;
        }
        
        // اضافه کردن category
        add_action('elementor/elements_categories_registered', array($this, 'add_elementor_widget_categories'));
        
        // ثبت ویجت‌ها
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
        
        // اضافه کردن کنترل‌های AI
        add_action('elementor/element/after_section_end', array($this, 'add_ai_controls_to_widgets'), 10, 3);
        
        // اسکریپت‌های ویرایشگر
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'editor_enqueue_scripts'));
    }
    
    public function add_elementor_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'ai-content',
            array(
                'title' => __('AI Content', 'elementor-ai-content'),
                'icon' => 'fa fa-robot',
            )
        );
    }
    
    public function register_widgets($widgets_manager) {
        // بارگذاری و ثبت ویجت‌ها با بررسی خطا
        $widget_files = array(
            'ai-text-widget.php' => 'Elementor_AI_Text_Widget',
        );
        
        foreach ($widget_files as $file => $class) {
            $widget_path = ELEMENTOR_AI_CONTENT_PLUGIN_PATH . 'includes/elementor-widgets/' . $file;
            if (file_exists($widget_path)) {
                require_once $widget_path;
                if (class_exists($class)) {
                    $widgets_manager->register(new $class());
                }
            }
        }
    }
    
    public function add_ai_controls_to_widgets($element, $section_id, $args) {
        // فقط برای ویجت‌های مشخص
        $supported_widgets = array('text-editor', 'heading', 'button');
        
        if (!in_array($element->get_name(), $supported_widgets) || $section_id !== 'section_editor') {
            return;
        }
        
        $element->start_controls_section(
            'section_ai_content',
            array(
                'label' => __('AI Content Generator', 'elementor-ai-content'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $element->add_control(
            'ai_prompt',
            array(
                'label' => __('AI Prompt', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('Describe what content you want to generate...', 'elementor-ai-content'),
            )
        );
        
        $element->add_control(
            'ai_content_type',
            array(
                'label' => __('Content Type', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'paragraph' => __('Paragraph', 'elementor-ai-content'),
                    'heading' => __('Heading', 'elementor-ai-content'),
                    'button_text' => __('Button Text', 'elementor-ai-content'),
                ),
                'default' => 'paragraph',
            )
        );
        
        $element->add_control(
            'generate_ai_content',
            array(
                'label' => __('Generate Content', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::BUTTON,
                'text' => __('Generate with AI', 'elementor-ai-content'),
                'event' => 'elementorAI:generate',
            )
        );
        
        $element->end_controls_section();
    }
    
    public function activate() {
        // ایجاد جدول دیتابیس
        $this->create_database_table();
    }
    
    private function create_database_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'elementor_ai_content_history';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            element_id varchar(255) NOT NULL,
            widget_type varchar(100) NOT NULL,
            prompt text NOT NULL,
            content_type varchar(50) NOT NULL,
            generated_content longtext NOT NULL,
            settings longtext NOT NULL,
            model varchar(50) NOT NULL,
            user_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY element_id (element_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Elementor AI Content', 'elementor-ai-content'),
            __('Elementor AI', 'elementor-ai-content'),
            'manage_options',
            'elementor-ai-content',
            array($this, 'admin_page'),
            'dashicons-elementor',
            30
        );
        
        add_submenu_page(
            'elementor-ai-content',
            __('Settings', 'elementor-ai-content'),
            __('Settings', 'elementor-ai-content'),
            'manage_options',
            'elementor-ai-settings',
            array($this, 'settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('elementor_ai_content_settings', 'elementor_ai_openai_key');
        register_setting('elementor_ai_content_settings', 'elementor_ai_model');
        register_setting('elementor_ai_content_settings', 'elementor_ai_max_tokens');
        register_setting('elementor_ai_content_settings', 'elementor_ai_temperature');
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'elementor-ai') === false) {
            return;
        }
        
        wp_enqueue_style(
            'elementor-ai-admin',
            ELEMENTOR_AI_CONTENT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            ELEMENTOR_AI_CONTENT_VERSION
        );
        
        wp_enqueue_script(
            'elementor-ai-admin',
            ELEMENTOR_AI_CONTENT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            ELEMENTOR_AI_CONTENT_VERSION,
            true
        );
        
        wp_localize_script('elementor-ai-admin', 'elementorAI', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('elementor_ai_nonce'),
            'rest_url' => rest_url('elementor-ai/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
        ));
    }
    
    public function editor_enqueue_scripts() {
        wp_enqueue_style(
            'elementor-ai-editor',
            ELEMENTOR_AI_CONTENT_PLUGIN_URL . 'assets/css/editor.css',
            array(),
            ELEMENTOR_AI_CONTENT_VERSION
        );
        
        wp_enqueue_script(
            'elementor-ai-editor',
            ELEMENTOR_AI_CONTENT_PLUGIN_URL . 'assets/js/editor.js',
            array('jquery'),
            ELEMENTOR_AI_CONTENT_VERSION,
            true
        );
        
        wp_localize_script('elementor-ai-editor', 'elementorAI', array(
            'rest_url' => rest_url('elementor-ai/v1/'),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'strings' => array(
                'generating' => __('Generating content...', 'elementor-ai-content'),
                'error' => __('Error generating content', 'elementor-ai-content'),
                'success' => __('Content generated successfully', 'elementor-ai-content'),
            )
        ));
    }
    
    public function register_rest_routes() {
        register_rest_route('elementor-ai/v1', '/generate', array(
            'methods' => 'POST',
            'callback' => array($this, 'generate_content_api'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }
    
    public function check_permissions() {
        return current_user_can('edit_posts');
    }
    
    public function generate_content_api($request) {
        // بررسی وجود کلاس API Handler
        if (!class_exists('AI_API_Handler')) {
            return new WP_Error('missing_class', 'AI API Handler class not found');
        }
        
        try {
            $api_handler = new AI_API_Handler();
            return $api_handler->generate_content($request);
        } catch (Exception $e) {
            return new WP_Error('api_error', $e->getMessage());
        }
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="notice notice-info">
                <p><?php _e('Welcome to Elementor AI Content Generator!', 'elementor-ai-content'); ?></p>
            </div>
            <p><?php _e('Configure your settings and start generating AI content.', 'elementor-ai-content'); ?></p>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('elementor_ai_settings');
            
            update_option('elementor_ai_openai_key', sanitize_text_field($_POST['api_key'] ?? ''));
            update_option('elementor_ai_model', sanitize_text_field($_POST['model'] ?? 'gpt-4o-mini'));
            update_option('elementor_ai_max_tokens', intval($_POST['max_tokens'] ?? 1000));
            update_option('elementor_ai_temperature', floatval($_POST['temperature'] ?? 0.7));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'elementor-ai-content') . '</p></div>';
        }
        
        $api_key = get_option('elementor_ai_openai_key', '');
        $model = get_option('elementor_ai_model', 'gpt-4o-mini');
        $max_tokens = get_option('elementor_ai_max_tokens', 1000);
        $temperature = get_option('elementor_ai_temperature', 0.7);
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('elementor_ai_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_key"><?php _e('OpenAI API Key', 'elementor-ai-content'); ?></label></th>
                        <td>
                            <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter your OpenAI API key', 'elementor-ai-content'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="model"><?php _e('AI Model', 'elementor-ai-content'); ?></label></th>
                        <td>
                            <select id="model" name="model">
                                <option value="gpt-4o" <?php selected($model, 'gpt-4o'); ?>>GPT-4o</option>
                                <option value="gpt-4o-mini" <?php selected($model, 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                                <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>>GPT-4</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_tokens"><?php _e('Max Tokens', 'elementor-ai-content'); ?></label></th>
                        <td><input type="number" id="max_tokens" name="max_tokens" value="<?php echo esc_attr($max_tokens); ?>" min="50" max="4000" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="temperature"><?php _e('Temperature', 'elementor-ai-content'); ?></label></th>
                        <td><input type="number" id="temperature" name="temperature" value="<?php echo esc_attr($temperature); ?>" min="0" max="1" step="0.1" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    // پیام‌های خطا
    public function admin_notice_minimum_php_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-ai-content'),
            '<strong>' . esc_html__('Elementor AI Content Generator', 'elementor-ai-content') . '</strong>',
            '<strong>' . esc_html__('PHP', 'elementor-ai-content') . '</strong>',
            ELEMENTOR_AI_CONTENT_MINIMUM_PHP_VERSION
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    public function admin_notice_minimum_wordpress_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-ai-content'),
            '<strong>' . esc_html__('Elementor AI Content Generator', 'elementor-ai-content') . '</strong>',
            '<strong>' . esc_html__('WordPress', 'elementor-ai-content') . '</strong>',
            '5.0'
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    public function admin_notice_minimum_elementor_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'elementor-ai-content'),
            '<strong>' . esc_html__('Elementor AI Content Generator', 'elementor-ai-content') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'elementor-ai-content') . '</strong>',
            ELEMENTOR_AI_CONTENT_MINIMUM_ELEMENTOR_VERSION
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
}

// راه‌اندازی افزونه
function elementor_ai_content_generator_init() {
    Elementor_AI_Content_Generator::instance();
}
add_action('plugins_loaded', 'elementor_ai_content_generator_init');