<?php
// templates/settings-page.php
if (!defined('ABSPATH')) {
    exit;
}

// بررسی و ذخیره تنظیمات
if (isset($_POST['submit'])) {
    check_admin_referer('ai_content_generator_settings');
    
    update_option('ai_content_generator_api_key', sanitize_text_field($_POST['api_key']));
    update_option('ai_content_generator_model', sanitize_text_field($_POST['model']));
    update_option('ai_content_generator_max_tokens', intval($_POST['max_tokens']));
    update_option('ai_content_generator_temperature', floatval($_POST['temperature']));
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'ai-content-generator') . '</p></div>';
}

// دریافت تنظیمات فعلی
$api_key = get_option('ai_content_generator_api_key', '');
$model = get_option('ai_content_generator_model', 'gpt-4o-mini');
$max_tokens = get_option('ai_content_generator_max_tokens', 1000);
$temperature = get_option('ai_content_generator_temperature', 0.7);
?>

<div class="wrap ai-admin-page">
    <h1><?php _e('AI Content Generator - Settings', 'ai-content-generator'); ?></h1>
    
    <div class="ai-settings-container">
        <form method="post" action="">
            <?php wp_nonce_field('ai_content_generator_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_key"><?php _e('OpenAI API Key', 'ai-content-generator'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                        <button type="button" id="toggle-api-key" class="button"><?php _e('Show/Hide', 'ai-content-generator'); ?></button>
                        <p class="description">
                            <?php _e('Enter your OpenAI API key. You can get it from', 'ai-content-generator'); ?> 
                            <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Dashboard</a>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="model"><?php _e('AI Model', 'ai-content-generator'); ?></label>
                    </th>
                    <td>
                        <select id="model" name="model" class="regular-text">
                            <option value="gpt-4o" <?php selected($model, 'gpt-4o'); ?>>GPT-4o</option>
                            <option value="gpt-4o-mini" <?php selected($model, 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                            <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>>GPT-4</option>
                            <option value="gpt-4-turbo" <?php selected($model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                            <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                        </select>
                        <p class="description"><?php _e('Choose the AI model for content generation', 'ai-content-generator'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_tokens"><?php _e('Max Tokens', 'ai-content-generator'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_tokens" name="max_tokens" value="<?php echo esc_attr($max_tokens); ?>" min="50" max="4000" class="small-text" />
                        <p class="description"><?php _e('Maximum number of tokens to generate (50-4000)', 'ai-content-generator'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="temperature"><?php _e('Temperature', 'ai-content-generator'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="temperature" name="temperature" value="<?php echo esc_attr($temperature); ?>" min="0" max="1" step="0.1" class="small-text" />
                        <p class="description"><?php _e('Creativity level (0.0 = more focused, 1.0 = more creative)', 'ai-content-generator'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        
        <div class="ai-test-connection">
            <h2><?php _e('Test Connection', 'ai-content-generator'); ?></h2>
            <button id="test-connection" class="button button-secondary"><?php _e('Test API Connection', 'ai-content-generator'); ?></button>
            <div id="connection-result"></div>
        </div>
        
        <div class="ai-usage-info">
            <h2><?php _e('Usage Information', 'ai-content-generator'); ?></h2>
            <div class="usage-stats">
                <div class="stat-item">
                    <span class="stat-label"><?php _e('Total Generations:', 'ai-content-generator'); ?></span>
                    <span class="stat-value" id="total-generations">
                        <?php
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'ai_content_history';
                        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                        echo esc_html($count);
                        ?>
                    </span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php _e('This Month:', 'ai-content-generator'); ?></span>
                    <span class="stat-value">
                        <?php
                        $month_count = $wpdb->get_var($wpdb->prepare("
                            SELECT COUNT(*) FROM $table_name 
                            WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                            AND YEAR(created_at) = YEAR(CURRENT_DATE())
                        "));
                        echo esc_html($month_count);
                        ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>