<?php
// templates/admin-page.php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'ai_content_history';

// آمار کلی
$total_generations = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$today_generations = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = CURRENT_DATE()");
$month_generations = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");

// آخرین تولیدات
$recent_generations = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 5");

// بررسی تنظیمات API
$api_key = get_option('ai_content_generator_api_key');
$is_configured = !empty($api_key);
?>

<div class="wrap ai-admin-page">
    <h1><?php _e('AI Content Generator', 'ai-content-generator'); ?></h1>
    
    <div class="ai-dashboard">
        <!-- کارت‌های آماری -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3><?php echo esc_html($total_generations); ?></h3>
                    <p><?php _e('Total Generations', 'ai-content-generator'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-info">
                    <h3><?php echo esc_html($today_generations); ?></h3>
                    <p><?php _e('Today', 'ai-content-generator'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-info">
                    <h3><?php echo esc_html($month_generations); ?></h3>
                    <p><?php _e('This Month', 'ai-content-generator'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><?php echo $is_configured ? '✅' : '❌'; ?></div>
                <div class="stat-info">
                    <h3><?php echo $is_configured ? __('Ready', 'ai-content-generator') : __('Setup Required', 'ai-content-generator'); ?></h3>
                    <p><?php _e('API Status', 'ai-content-generator'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- پیام تنظیمات -->
        <?php if (!$is_configured): ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('Setup Required!', 'ai-content-generator'); ?></strong> 
                    <?php _e('Please configure your OpenAI API key in', 'ai-content-generator'); ?> 
                    <a href="<?php echo admin_url('admin.php?page=ai-content-generator-settings'); ?>"><?php _e('Settings', 'ai-content-generator'); ?></a>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- ابزارهای سریع -->
        <div class="quick-tools">
            <h2><?php _e('Quick Tools', 'ai-content-generator'); ?></h2>
            <div class="tools-grid">
                <div class="tool-card">
                    <h3><?php _e('Generate Content', 'ai-content-generator'); ?></h3>
                    <p><?php _e('Use the shortcode [ai_generator] to add the content generator to any page or post.', 'ai-content-generator'); ?></p>
                    <button class="button button-primary copy-shortcode" data-shortcode="[ai_generator]">
                        <?php _e('Copy Shortcode', 'ai-content-generator'); ?>
                    </button>
                </div>
                
                <div class="tool-card">
                    <h3><?php _e('View History', 'ai-content-generator'); ?></h3>
                    <p><?php _e('See all previously generated content and manage your history.', 'ai-content-generator'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=ai-content-generator-history'); ?>" class="button button-secondary">
                        <?php _e('View History', 'ai-content-generator'); ?>
                    </a>
                </div>
                
                <div class="tool-card">
                    <h3><?php _e('Settings', 'ai-content-generator'); ?></h3>
                    <p><?php _e('Configure API settings, models, and generation parameters.', 'ai-content-generator'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=ai-content-generator-settings'); ?>" class="button button-secondary">
                        <?php _e('Open Settings', 'ai-content-generator'); ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- آخرین تولیدات -->
        <?php if (!empty($recent_generations)): ?>
            <div class="recent-generations">
                <h2><?php _e('Recent Generations', 'ai-content-generator'); ?></h2>
                <div class="recent-list">
                    <?php foreach ($recent_generations as $item): ?>
                        <div class="recent-item">
                            <div class="item-info">
                                <div class="item-prompt">
                                    <strong><?php echo esc_html(wp_trim_words($item->prompt, 10)); ?></strong>
                                </div>
                                <div class="item-meta">
                                    <span class="content-type-badge <?php echo esc_attr($item->content_type); ?>">
                                        <?php
                                        $types = array(
                                            'article' => __('Article', 'ai-content-generator'),
                                            'social_media' => __('Social Media', 'ai-content-generator'),
                                            'email' => __('Email', 'ai-content-generator'),
                                            'story' => __('Story', 'ai-content-generator'),
                                            'product_description' => __('Product', 'ai-content-generator'),
                                            'blog_post' => __('Blog Post', 'ai-content-generator')
                                        );
                                        echo esc_html($types[$item->content_type] ?? $item->content_type);
                                        ?>
                                    </span>
                                    <span class="item-date">
                                        <?php echo esc_html(human_time_diff(strtotime($item->created_at), current_time('timestamp'))); ?> 
                                        <?php _e('ago', 'ai-content-generator'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="item-actions">
                                <button class="button button-small view-content" data-id="<?php echo esc_attr($item->id); ?>">
                                    <?php _e('View', 'ai-content-generator'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="view-all-link">
                    <a href="<?php echo admin_url('admin.php?page=ai-content-generator-history'); ?>">
                        <?php _e('View All History →', 'ai-content-generator'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- اطلاعات کاربردی -->
        <div class="usage-tips">
            <h2><?php _e('Usage Tips', 'ai-content-generator'); ?></h2>
            <div class="tips-grid">
                <div class="tip-item">
                    <h4><?php _e('🎯 Clear Prompts', 'ai-content-generator'); ?></h4>
                    <p><?php _e('Write specific and clear prompts for better results.', 'ai-content-generator'); ?></p>
                </div>
                <div class="tip-item">
                    <h4><?php _e('📝 Content Types', 'ai-content-generator'); ?></h4>
                    <p><?php _e('Select the appropriate content type to get tailored results.', 'ai-content-generator'); ?></p>
                </div>
                <div class="tip-item">
                    <h4><?php _e('⚙️ Model Selection', 'ai-content-generator'); ?></h4>
                    <p><?php _e('Use GPT-4 for higher quality, GPT-3.5 for faster generation.', 'ai-content-generator'); ?></p>
                </div>
                <div class="tip-item">
                    <h4><?php _e('💾 Save History', 'ai-content-generator'); ?></h4>
                    <p><?php _e('All generated content is automatically saved for future reference.', 'ai-content-generator'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نوتیفیکیشن کپی -->
<div id="copy-notification" class="copy-notification" style="display: none;">
    <?php _e('Shortcode copied!', 'ai-content-generator'); ?>
</div>