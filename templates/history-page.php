<?php
// templates/history-page.php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'ai_content_history';

// صفحه‌بندی
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// دریافت داده‌ها
$total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$history = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
    $per_page, $offset
));

$total_pages = ceil($total / $per_page);
?>

<div class="wrap ai-admin-page">
    <h1><?php _e('AI Content Generator - History', 'ai-content-generator'); ?></h1>
    
    <div class="ai-history-container">
        <div class="history-stats">
            <div class="stat-box">
                <h3><?php _e('Total Generations', 'ai-content-generator'); ?></h3>
                <span class="stat-number"><?php echo esc_html($total); ?></span>
            </div>
            <div class="stat-box">
                <h3><?php _e('This Month', 'ai-content-generator'); ?></h3>
                <span class="stat-number">
                    <?php
                    $month_count = $wpdb->get_var("
                        SELECT COUNT(*) FROM $table_name 
                        WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                        AND YEAR(created_at) = YEAR(CURRENT_DATE())
                    ");
                    echo esc_html($month_count);
                    ?>
                </span>
            </div>
            <div class="stat-box">
                <h3><?php _e('Today', 'ai-content-generator'); ?></h3>
                <span class="stat-number">
                    <?php
                    $today_count = $wpdb->get_var("
                        SELECT COUNT(*) FROM $table_name 
                        WHERE DATE(created_at) = CURRENT_DATE()
                    ");
                    echo esc_html($today_count);
                    ?>
                </span>
            </div>
        </div>
        
        <?php if (empty($history)): ?>
            <div class="no-history">
                <div class="no-history-icon">📝</div>
                <h3><?php _e('No content generated yet', 'ai-content-generator'); ?></h3>
                <p><?php _e('Start generating content to see your history here.', 'ai-content-generator'); ?></p>
            </div>
        <?php else: ?>
            <div class="history-table-container">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'ai-content-generator'); ?></th>
                            <th><?php _e('Prompt', 'ai-content-generator'); ?></th>
                            <th><?php _e('Type', 'ai-content-generator'); ?></th>
                            <th><?php _e('Length', 'ai-content-generator'); ?></th>
                            <th><?php _e('Model', 'ai-content-generator'); ?></th>
                            <th><?php _e('Actions', 'ai-content-generator'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $item): ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at))); ?></td>
                                <td>
                                    <div class="prompt-preview">
                                        <?php echo esc_html(wp_trim_words($item->prompt, 8)); ?>
                                    </div>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <span class="content-length-badge <?php echo esc_attr($item->content_length); ?>">
                                        <?php
                                        $lengths = array(
                                            'short' => __('Short', 'ai-content-generator'),
                                            'medium' => __('Medium', 'ai-content-generator'),
                                            'long' => __('Long', 'ai-content-generator')
                                        );
                                        echo esc_html($lengths[$item->content_length] ?? $item->content_length);
                                        ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($item->model); ?></td>
                                <td>
                                    <button class="button button-small view-content" data-id="<?php echo esc_attr($item->id); ?>">
                                        <?php _e('View', 'ai-content-generator'); ?>
                                    </button>
                                    <button class="button button-small copy-content" data-content="<?php echo esc_attr($item->generated_content); ?>">
                                        <?php _e('Copy', 'ai-content-generator'); ?>
                                    </button>
                                    <button class="button button-small delete-item" data-id="<?php echo esc_attr($item->id); ?>">
                                        <?php _e('Delete', 'ai-content-generator'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- صفحه‌بندی -->
            <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $page_links = paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                        echo $page_links;
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- مودال نمایش محتوا -->
<div id="content-modal" class="ai-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php _e('Generated Content', 'ai-content-generator'); ?></h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div id="modal-content-text"></div>
        </div>
        <div class="modal-footer">
            <button id="modal-copy-btn" class="button button-primary"><?php _e('Copy Content', 'ai-content-generator'); ?></button>
            <button class="button close-modal"><?php _e('Close', 'ai-content-generator'); ?></button>
        </div>
    </div>
</div>