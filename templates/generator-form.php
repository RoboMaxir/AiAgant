<?php
// templates/generator-form.php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="ai-content-generator" class="ai-generator-container">
    <div class="ai-generator-header">
        <h2><?php echo esc_html($atts['title']); ?></h2>
        <div class="theme-toggle">
            <button id="theme-toggle" class="theme-toggle-btn">
                <span class="sun-icon">☀️</span>
                <span class="moon-icon">🌙</span>
            </button>
        </div>
    </div>
    
    <form id="ai-content-form" class="ai-form">
        <div class="form-group">
            <label for="ai-prompt"><?php _e('چه محتوایی می‌خواهید تولید کنید؟', 'ai-content-generator'); ?></label>
            <textarea id="ai-prompt" name="prompt" rows="4" placeholder="<?php _e('مثال: درباره فواید ورزش کردن...', 'ai-content-generator'); ?>" required></textarea>
        </div>
        
        <div class="form-row">
            <div class="form-group half">
                <label for="content-type"><?php _e('نوع محتوا', 'ai-content-generator'); ?></label>
                <select id="content-type" name="content_type" required>
                    <option value=""><?php _e('انتخاب کنید', 'ai-content-generator'); ?></option>
                    <option value="article"><?php _e('مقاله', 'ai-content-generator'); ?></option>
                    <option value="social_media"><?php _e('شبکه اجتماعی', 'ai-content-generator'); ?></option>
                    <option value="email"><?php _e('ایمیل', 'ai-content-generator'); ?></option>
                    <option value="story"><?php _e('داستان', 'ai-content-generator'); ?></option>
                    <option value="product_description"><?php _e('توضیحات محصول', 'ai-content-generator'); ?></option>
                    <option value="blog_post"><?php _e('پست وبلاگ', 'ai-content-generator'); ?></option>
                </select>
            </div>
            
            <div class="form-group half">
                <label for="content-length"><?php _e('طول محتوا', 'ai-content-generator'); ?></label>
                <select id="content-length" name="content_length" required>
                    <option value=""><?php _e('انتخاب کنید', 'ai-content-generator'); ?></option>
                    <option value="short"><?php _e('کوتاه', 'ai-content-generator'); ?></option>
                    <option value="medium"><?php _e('متوسط', 'ai-content-generator'); ?></option>
                    <option value="long"><?php _e('بلند', 'ai-content-generator'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" id="generate-btn" class="generate-btn">
                <span class="btn-text"><?php _e('تولید محتوا', 'ai-content-generator'); ?></span>
                <span class="btn-loading" style="display: none;">
                    <span class="spinner"></span>
                    <?php _e('در حال تولید...', 'ai-content-generator'); ?>
                </span>
            </button>
        </div>
    </form>
    
    <div id="ai-result" class="ai-result" style="display: none;">
        <div class="result-header">
            <h3><?php _e('محتوای تولید شده', 'ai-content-generator'); ?></h3>
            <div class="result-actions">
                <button id="copy-btn" class="action-btn copy-btn" title="<?php _e('کپی', 'ai-content-generator'); ?>">📋</button>
                <button id="download-btn" class="action-btn download-btn" title="<?php _e('دانلود', 'ai-content-generator'); ?>">💾</button>
            </div>
        </div>
        <div id="ai-content" class="ai-content"></div>
    </div>
    
    <div id="ai-error" class="ai-error" style="display: none;">
        <div class="error-icon">⚠️</div>
        <div id="error-message" class="error-message"></div>
    </div>
</div>

<div id="copy-notification" class="copy-notification" style="display: none;">
    <?php _e('محتوا کپی شد!', 'ai-content-generator'); ?>
</div>