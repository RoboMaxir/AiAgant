<?php
// includes/elementor-widgets/ai-text-widget.php

if (!defined('ABSPATH')) {
    exit;
}

class Elementor_AI_Text_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'ai-text';
    }
    
    public function get_title() {
        return __('AI Text Generator', 'elementor-ai-content');
    }
    
    public function get_icon() {
        return 'eicon-text-area';
    }
    
    public function get_categories() {
        return ['ai-content'];
    }
    
    public function get_keywords() {
        return ['ai', 'text', 'content', 'generator', 'openai'];
    }
    
    protected function register_controls() {
        
        // بخش محتوا
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'elementor-ai-content'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'text_content',
            [
                'label' => __('Text', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::WYSIWYG,
                'default' => __('Click Generate with AI to create content...', 'elementor-ai-content'),
                'placeholder' => __('Your generated content will appear here', 'elementor-ai-content'),
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // بخش AI Generator
        $this->start_controls_section(
            'ai_generator_section',
            [
                'label' => __('AI Content Generator', 'elementor-ai-content'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'ai_prompt',
            [
                'label' => __('Describe the content you want', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('e.g., Write about the benefits of digital marketing for small businesses', 'elementor-ai-content'),
                'description' => __('Describe what kind of text content you want to generate', 'elementor-ai-content'),
                'ai' => [
                    'active' => false,
                ],
            ]
        );
        
        $this->add_control(
            'ai_content_type',
            [
                'label' => __('Content Type', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'paragraph' => __('Paragraph', 'elementor-ai-content'),
                    'article' => __('Article', 'elementor-ai-content'),
                    'blog_post' => __('Blog Post', 'elementor-ai-content'),
                    'product_description' => __('Product Description', 'elementor-ai-content'),
                    'about_us' => __('About Us', 'elementor-ai-content'),
                    'services' => __('Services Description', 'elementor-ai-content'),
                    'testimonial' => __('Testimonial', 'elementor-ai-content'),
                    'faq' => __('FAQ Answer', 'elementor-ai-content'),
                ],
                'default' => 'paragraph',
            ]
        );
        
        $this->add_control(
            'ai_length',
            [
                'label' => __('Content Length', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'short' => __('Short (50-100 words)', 'elementor-ai-content'),
                    'medium' => __('Medium (100-250 words)', 'elementor-ai-content'),
                    'long' => __('Long (250-500 words)', 'elementor-ai-content'),
                    'extra_long' => __('Extra Long (500+ words)', 'elementor-ai-content'),
                ],
                'default' => 'medium',
            ]
        );
        
        $this->add_control(
            'ai_tone',
            [
                'label' => __('Tone of Voice', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'professional' => __('Professional', 'elementor-ai-content'),
                    'casual' => __('Casual & Friendly', 'elementor-ai-content'),
                    'authoritative' => __('Authoritative', 'elementor-ai-content'),
                    'persuasive' => __('Persuasive', 'elementor-ai-content'),
                    'informative' => __('Informative', 'elementor-ai-content'),
                    'engaging' => __('Engaging & Fun', 'elementor-ai-content'),
                    'formal' => __('Formal', 'elementor-ai-content'),
                    'conversational' => __('Conversational', 'elementor-ai-content'),
                ],
                'default' => 'professional',
            ]
        );
        
        $this->add_control(
            'ai_language',
            [
                'label' => __('Language', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'persian' => __('Persian/Farsi', 'elementor-ai-content'),
                    'english' => __('English', 'elementor-ai-content'),
                    'arabic' => __('Arabic', 'elementor-ai-content'),
                ],
                'default' => 'persian',
            ]
        );
        
        $this->add_control(
            'ai_include_keywords',
            [
                'label' => __('Keywords to Include', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'placeholder' => __('Enter keywords separated by commas', 'elementor-ai-content'),
                'description' => __('Optional: Keywords that should be included in the generated content', 'elementor-ai-content'),
                'rows' => 3,
            ]
        );
        
        $this->add_control(
            'generate_button',
            [
                'type' => \Elementor\Controls_Manager::BUTTON,
                'label' => __('Generate Content', 'elementor-ai-content'),
                'text' => __('Generate with AI', 'elementor-ai-content'),
                'event' => 'elementorAI:generateText',
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'ai_loading',
            [
                'type' => \Elementor\Controls_Manager::HIDDEN,
                'default' => 'false',
            ]
        );
        
        $this->add_control(
            'variations_section',
            [
                'label' => __('Content Variations', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'generate_variations',
            [
                'type' => \Elementor\Controls_Manager::BUTTON,
                'label' => __('Generate Variations', 'elementor-ai-content'),
                'text' => __('Create 3 Variations', 'elementor-ai-content'),
                'event' => 'elementorAI:generateVariations',
            ]
        );
        
        $this->add_control(
            'improve_content',
            [
                'type' => \Elementor\Controls_Manager::BUTTON,
                'label' => __('Improve Content', 'elementor-ai-content'),
                'text' => __('Improve Existing', 'elementor-ai-content'),
                'event' => 'elementorAI:improveContent',
            ]
        );
        
        $this->end_controls_section();
        
        // بخش استایل متن
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Text Style', 'elementor-ai-content'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_responsive_control(
            'text_align',
            [
                'label' => __('Alignment', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'elementor-ai-content'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'elementor-ai-content'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'elementor-ai-content'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => __('Justified', 'elementor-ai-content'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .ai-text-content' => 'text-align: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'text_typography',
                'selector' => '{{WRAPPER}} .ai-text-content',
            ]
        );
        
        $this->add_control(
            'text_color',
            [
                'label' => __('Text Color', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .ai-text-content' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'text_shadow',
                'selector' => '{{WRAPPER}} .ai-text-content',
            ]
        );
        
        $this->add_responsive_control(
            'text_padding',
            [
                'label' => __('Padding', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .ai-text-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // بخش استایل AI Loading
        $this->start_controls_section(
            'ai_loading_style_section',
            [
                'label' => __('AI Loading Style', 'elementor-ai-content'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'loading_color',
            [
                'label' => __('Loading Color', 'elementor-ai-content'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#6366f1',
                'selectors' => [
                    '{{WRAPPER}} .ai-loading-spinner' => 'border-color: {{VALUE}} transparent {{VALUE}} transparent;',
                    '{{WRAPPER}} .ai-loading-text' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $this->add_render_attribute(
            'text_content',
            [
                'class' => 'ai-text-content',
                'data-widget-type' => 'ai-text',
                'data-element-id' => $this->get_id(),
            ]
        );
        
        ?>
        <div class="elementor-ai-text-widget">
            <?php if (\Elementor\Plugin::$instance->editor->is_edit_mode()): ?>
                <div class="ai-widget-controls" style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d;">
                    <strong><?php _e('AI Text Widget', 'elementor-ai-content'); ?></strong> - 
                    <?php _e('Configure AI settings in the left panel and click "Generate with AI"', 'elementor-ai-content'); ?>
                </div>
            <?php endif; ?>
            
            <div class="ai-loading-container" style="display: none;">
                <div class="ai-loading-spinner"></div>
                <div class="ai-loading-text"><?php _e('AI is generating your content...', 'elementor-ai-content'); ?></div>
            </div>
            
            <div <?php echo $this->get_render_attribute_string('text_content'); ?>>
                <?php echo $this->parse_text_editor($settings['text_content']); ?>
            </div>
            
            <?php if (\Elementor\Plugin::$instance->editor->is_edit_mode()): ?>
                <script>
                // اضافه کردن event listeners برای دکمه‌های AI
                jQuery(document).ready(function($) {
                    // مخفی کردن loading در ابتدا
                    $('.ai-loading-container').hide();
                    
                    // Event handler برای تولید محتوا
                    $(document).on('elementorAI:generateText', function(e, data) {
                        const widgetElement = $('.elementor-element-<?php echo $this->get_id(); ?>');
                        generateAIContent(widgetElement, '<?php echo $this->get_id(); ?>');
                    });
                    
                    // Event handler برای تولید تنوع‌ها
                    $(document).on('elementorAI:generateVariations', function(e, data) {
                        const widgetElement = $('.elementor-element-<?php echo $this->get_id(); ?>');
                        generateContentVariations(widgetElement, '<?php echo $this->get_id(); ?>');
                    });
                    
                    // Event handler برای بهبود محتوا
                    $(document).on('elementorAI:improveContent', function(e, data) {
                        const widgetElement = $('.elementor-element-<?php echo $this->get_id(); ?>');
                        improveExistingContent(widgetElement, '<?php echo $this->get_id(); ?>');
                    });
                });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }
    
    protected function content_template() {
        ?>
        <#
        view.addRenderAttribute( 'text_content', {
            'class': 'ai-text-content',
            'data-widget-type': 'ai-text',
            'data-element-id': view.model.get('id')
        });
        #>
        
        <div class="elementor-ai-text-widget">
            <div class="ai-widget-controls" style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #6c757d;">
                <strong><?php _e('AI Text Widget', 'elementor-ai-content'); ?></strong> - 
                <?php _e('Configure AI settings and generate content', 'elementor-ai-content'); ?>
            </div>
            
            <div class="ai-loading-container" style="display: none;">
                <div class="ai-loading-spinner"></div>
                <div class="ai-loading-text"><?php _e('AI is generating your content...', 'elementor-ai-content'); ?></div>
            </div>
            
            <div {{{ view.getRenderAttributeString( 'text_content' ) }}}>
                {{{ settings.text_content }}}
            </div>
        </div>
        <?php
    }
}