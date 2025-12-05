<?php
// includes/class-ai-api-handler.php

if (!defined('ABSPATH')) {
    exit;
}

class AI_API_Handler {
    
    private $api_key;
    private $model;
    private $max_tokens;
    private $temperature;
    
    public function __construct() {
        $this->api_key = get_option('elementor_ai_openai_key', '');
        $this->model = get_option('elementor_ai_model', 'gpt-4o-mini');
        $this->max_tokens = get_option('elementor_ai_max_tokens', 1000);
        $this->temperature = get_option('elementor_ai_temperature', 0.7);
    }
    
    public function generate_content($request) {
        if (!$this->api_key) {
            return new WP_Error('no_api_key', __('OpenAI API key not configured', 'elementor-ai-content'));
        }
        
        $prompt = $request->get_param('prompt');
        $content_type = $request->get_param('content_type');
        $length = $request->get_param('length') ?: 'medium';
        $tone = $request->get_param('tone') ?: 'professional';
        $language = $request->get_param('language') ?: 'persian';
        $widget_type = $request->get_param('widget_type') ?: 'text';
        
        // ساخت پرامپت بهینه
        $optimized_prompt = $this->build_optimized_prompt(
            $prompt, 
            $content_type, 
            $length, 
            $tone, 
            $language, 
            $widget_type
        );
        
        // فراخوانی OpenAI API
        $response = $this->call_openai_api($optimized_prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // پردازش پاسخ برای استفاده در Elementor
        $processed_content = $this->process_response_for_elementor($response, $content_type, $widget_type);
        
        return new WP_REST_Response([
            'success' => true,
            'content' => $processed_content,
            'raw_content' => $response,
            'meta' => [
                'content_type' => $content_type,
                'widget_type' => $widget_type,
                'model' => $this->model,
                'tokens_used' => $this->estimate_tokens($response)
            ]
        ]);
    }
    
    private function build_optimized_prompt($prompt, $content_type, $length, $tone, $language, $widget_type) {
        // قالب‌های مخصوص انواع محتوا
        $content_templates = [
            'paragraph' => 'یک پاراگراف %s بنویس درباره: %s',
            'heading' => 'یک عنوان %s و جذاب بنویس برای: %s',
            'list' => 'یک لیست %s از نکات مهم درباره %s بنویس',
            'button_text' => 'متن دکمه %s و تشویق‌کننده برای %s بنویس',
            'call_to_action' => 'یک فراخوان عمل %s و مؤثر برای %s بنویس',
            'product_description' => 'توضیحات محصول %s و جذاب برای %s بنویس',
            'social_post' => 'یک پست %s برای شبکه‌های اجتماعی درباره %s بنویس'
        ];
        
        // تنظیمات طول
        $length_settings = [
            'short' => 'کوتاه (حداکثر 50 کلمه)',
            'medium' => 'متوسط (50-150 کلمه)', 
            'long' => 'مفصل (150-300 کلمه)'
        ];
        
        // تنظیمات لحن
        $tone_settings = [
            'professional' => 'حرفه‌ای و رسمی',
            'casual' => 'غیررسمی و صمیمی',
            'friendly' => 'دوستانه و گرم',
            'authoritative' => 'مقتدر و قاطع',
            'persuasive' => 'متقاعدکننده و تشویق‌آمیز',
            'humorous' => 'طنزآمیز و سرگرم‌کننده'
        ];
        
        // زبان‌ها
        $language_instructions = [
            'persian' => 'به زبان فارسی',
            'english' => 'in English language',
            'arabic' => 'باللغة العربية'
        ];
        
        $template = isset($content_templates[$content_type]) 
            ? $content_templates[$content_type] 
            : $content_templates['paragraph'];
        
        $length_instruction = isset($length_settings[$length]) 
            ? $length_settings[$length] 
            : $length_settings['medium'];
        
        $tone_instruction = isset($tone_settings[$tone]) 
            ? $tone_settings[$tone] 
            : $tone_settings['professional'];
        
        $language_instruction = isset($language_instructions[$language])
            ? $language_instructions[$language]
            : $language_instructions['persian'];
        
        // ساخت پرامپت نهایی
        $final_prompt = sprintf($template, $length_instruction, $prompt);
        $final_prompt .= "\n\nلحن: " . $tone_instruction;
        $final_prompt .= "\nزبان: " . $language_instruction;
        
        // افزودن دستورات خاص ویجت
        $widget_instructions = $this->get_widget_specific_instructions($widget_type, $content_type);
        if ($widget_instructions) {
            $final_prompt .= "\n\n" . $widget_instructions;
        }
        
        return $final_prompt;
    }
    
    private function get_widget_specific_instructions($widget_type, $content_type) {
        $instructions = [
            'heading' => [
                'heading' => 'عنوان باید خلاصه، قوی و جذاب باشد. بدون نقطه در انتها.',
                'default' => 'یک عنوان جذاب و کوتاه تولید کن.'
            ],
            'text-editor' => [
                'paragraph' => 'متن باید خوانا، ساختاریافته و دارای پاراگراف‌بندی مناسب باشد.',
                'list' => 'لیست را با bullet points یا شماره‌گذاری مناسب ارائه کن.'
            ],
            'button' => [
                'button_text' => 'متن دکمه باید کوتاه (حداکثر 3-5 کلمه) و عمل‌گرا باشد.',
                'call_to_action' => 'متن باید تشویق‌کننده و واضح باشد.'
            ],
            'icon-list' => [
                'list' => 'هر آیتم لیست باید مستقل و مفید باشد. حداکثر 10 آیتم.'
            ]
        ];
        
        if (isset($instructions[$widget_type][$content_type])) {
            return $instructions[$widget_type][$content_type];
        }
        
        if (isset($instructions[$widget_type]['default'])) {
            return $instructions[$widget_type]['default'];
        }
        
        return '';
    }
    
    private function call_openai_api($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'شما یک نویسنده حرفه‌ای و خبره تولید محتوا هستید که برای طراحان وب و المنتور کار می‌کنید. محتوای شما باید دقیق، جذاب و مناسب استفاده در وب‌سایت باشد.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => intval($this->max_tokens),
            'temperature' => floatval($this->temperature),
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        ];
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
        ];
        
        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => json_encode($data),
            'timeout' => 60,
            'data_format' => 'body'
        ]);
        
        if (is_wp_error($response)) {
            return new WP_Error('api_error', __('Error calling OpenAI API: ', 'elementor-ai-content') . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) 
                ? $data['error']['message'] 
                : __('Unknown API error', 'elementor-ai-content');
                
            return new WP_Error('openai_error', $error_message);
        }
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', __('Invalid response from OpenAI', 'elementor-ai-content'));
        }
        
        return $data['choices'][0]['message']['content'];
    }
    
    private function process_response_for_elementor($content, $content_type, $widget_type) {
        $content = trim($content);
        
        switch ($content_type) {
            case 'heading':
                // حذف نقطه از انتهای عنوان
                $content = rtrim($content, '.');
                // حذف علامت نقل قول اضافی
                $content = trim($content, '"\'');
                break;
                
            case 'list':
                // تبدیل لیست به فرمت HTML
                if ($widget_type === 'icon-list') {
                    $content = $this->format_for_icon_list($content);
                } else {
                    $content = $this->format_list_html($content);
                }
                break;
                
            case 'button_text':
                // حذف علامات اضافی از متن دکمه
                $content = trim($content, '"\'');
                $content = ucfirst($content);
                break;
                
            case 'paragraph':
                // افزودن فاصله بین پاراگراف‌ها
                $content = $this->format_paragraphs($content);
                break;
        }
        
        return $content;
    }
    
    private function format_for_icon_list($content) {
        // تبدیل لیست متنی به آرایه برای icon list Elementor
        $lines = explode("\n", $content);
        $formatted_items = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // حذف bullet points
            $line = preg_replace('/^[-*•]\s*/', '', $line);
            $line = preg_replace('/^\d+[\.)]\s*/', '', $line);
            
            if (!empty($line)) {
                $formatted_items[] = [
                    'text' => $line,
                    'icon' => ['value' => 'fas fa-check', 'library' => 'fa-solid']
                ];
            }
        }
        
        return $formatted_items;
    }
    
    private function format_list_html($content) {
        $lines = explode("\n", $content);
        $html = '<ul>';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // حذف bullet points موجود
            $line = preg_replace('/^[-*•]\s*/', '', $line);
            $line = preg_replace('/^\d+[\.)]\s*/', '', $line);
            
            if (!empty($line)) {
                $html .= '<li>' . esc_html($line) . '</li>';
            }
        }
        
        $html .= '</ul>';
        return $html;
    }
    
    private function format_paragraphs($content) {
        // تقسیم محتوا به پاراگراف‌ها
        $paragraphs = preg_split('/\n\s*\n/', $content);
        $formatted = [];
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph)) {
                $formatted[] = '<p>' . nl2br(esc_html($paragraph)) . '</p>';
            }
        }
        
        return implode("\n", $formatted);
    }
    
    private function estimate_tokens($content) {
        // تخمین تعداد توکن‌ها (روش ساده)
        return ceil(str_word_count($content) * 1.3);
    }
    
    public function test_api_connection() {
        if (!$this->api_key) {
            return new WP_Error('no_api_key', __('API key not provided', 'elementor-ai-content'));
        }
        
        $test_prompt = 'Generate a simple test message in Persian that says "API connection successful"';
        
        $response = $this->call_openai_api($test_prompt);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        return [
            'success' => true,
            'message' => __('API connection successful!', 'elementor-ai-content'),
            'test_response' => $response
        ];
    }
    
    public function get_available_models() {
        return [
            'gpt-4o' => [
                'name' => 'GPT-4o',
                'description' => __('Most advanced model, best quality', 'elementor-ai-content'),
                'max_tokens' => 4096,
                'cost_per_token' => 0.005
            ],
            'gpt-4o-mini' => [
                'name' => 'GPT-4o Mini', 
                'description' => __('Balanced performance and cost', 'elementor-ai-content'),
                'max_tokens' => 4096,
                'cost_per_token' => 0.0015
            ],
            'gpt-4' => [
                'name' => 'GPT-4',
                'description' => __('High quality, slower', 'elementor-ai-content'),
                'max_tokens' => 8192,
                'cost_per_token' => 0.03
            ],
            'gpt-4-turbo' => [
                'name' => 'GPT-4 Turbo',
                'description' => __('Fast and efficient', 'elementor-ai-content'),
                'max_tokens' => 4096,
                'cost_per_token' => 0.01
            ],
            'gpt-3.5-turbo' => [
                'name' => 'GPT-3.5 Turbo',
                'description' => __('Fast and economical', 'elementor-ai-content'),
                'max_tokens' => 4096,
                'cost_per_token' => 0.0005
            ]
        ];
    }
    
    public function generate_variations($original_content, $count = 3) {
        $variations = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $prompt = "بر اساس این محتوا، یک نسخه متفاوت و جایگزین بنویس:\n\n" . $original_content . "\n\nنسخه جدید باید مفهوم مشابه اما کلمات و ساختار متفاوت داشته باشد.";
            
            $response = $this->call_openai_api($prompt);
            
            if (!is_wp_error($response)) {
                $variations[] = $response;
            }
        }
        
        return $variations;
    }
    
    public function improve_content($content, $improvement_type = 'general') {
        $improvement_prompts = [
            'general' => 'این متن را بهبود بده و جذاب‌تر کن',
            'seo' => 'این متن را برای SEO بهینه‌سازی کن',
            'readability' => 'این متن را خواناتر و ساده‌تر کن', 
            'engagement' => 'این متن را جذاب‌تر و تعاملی‌تر کن',
            'professional' => 'این متن را حرفه‌ای‌تر و رسمی‌تر کن'
        ];
        
        $prompt = $improvement_prompts[$improvement_type] . ":\n\n" . $content;
        
        return $this->call_openai_api($prompt);
    }
}