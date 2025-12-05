// assets/js/frontend.js
(function($) {
    'use strict';

    // متغیرهای سراسری
    let isGenerating = false;
    let currentTheme = localStorage.getItem('ai-theme') || 'light';

    // راه‌اندازی اولیه
    $(document).ready(function() {
        initializeTheme();
        bindEvents();
    });

    // راه‌اندازی تم
    function initializeTheme() {
        document.documentElement.setAttribute('data-theme', currentTheme);
        updateThemeToggleIcon();
    }

    // به‌روزرسانی آیکون تغییر تم
    function updateThemeToggleIcon() {
        const $toggle = $('#theme-toggle');
        if (currentTheme === 'dark') {
            $toggle.find('.sun-icon').show();
            $toggle.find('.moon-icon').hide();
        } else {
            $toggle.find('.sun-icon').hide();
            $toggle.find('.moon-icon').show();
        }
    }

    // اتصال رویدادها
    function bindEvents() {
        // تغییر تم
        $(document).on('click', '#theme-toggle', toggleTheme);
        
        // ارسال فرم
        $(document).on('submit', '#ai-content-form', handleFormSubmit);
        
        // کپی محتوا
        $(document).on('click', '#copy-btn', copyContent);
        
        // دانلود محتوا
        $(document).on('click', '#download-btn', downloadContent);
    }

    // تغییر تم
    function toggleTheme() {
        currentTheme = currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', currentTheme);
        localStorage.setItem('ai-theme', currentTheme);
        updateThemeToggleIcon();
        
        // انیمیشن تغییر تم
        $('body').addClass('theme-transitioning');
        setTimeout(() => {
            $('body').removeClass('theme-transitioning');
        }, 300);
    }

    // مدیریت ارسال فرم
    function handleFormSubmit(e) {
        e.preventDefault();
        
        if (isGenerating) {
            return false;
        }

        const formData = {
            prompt: $('#ai-prompt').val().trim(),
            content_type: $('#content-type').val(),
            content_length: $('#content-length').val()
        };

        // اعتبارسنجی
        if (!validateFormData(formData)) {
            return false;
        }

        generateContent(formData);
    }

    // اعتبارسنجی داده‌های فرم
    function validateFormData(data) {
        if (!data.prompt) {
            showError('لطفاً متن درخواستی خود را وارد کنید.');
            $('#ai-prompt').focus();
            return false;
        }

        if (data.prompt.length < 10) {
            showError('متن درخواستی باید حداقل ۱۰ کاراکتر باشد.');
            $('#ai-prompt').focus();
            return false;
        }

        if (!data.content_type) {
            showError('لطفاً نوع محتوا را انتخاب کنید.');
            $('#content-type').focus();
            return false;
        }

        if (!data.content_length) {
            showError('لطفاً طول محتوا را انتخاب کنید.');
            $('#content-length').focus();
            return false;
        }

        return true;
    }

    // تولید محتوا
    function generateContent(data) {
        isGenerating = true;
        
        // نمایش حالت بارگذاری
        showLoading();
        hideError();
        hideResult();

        // ارسال درخواست AJAX
        $.ajax({
            url: aiContentGenerator.rest_url + 'generate',
            type: 'POST',
            data: JSON.stringify(data),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiContentGenerator.rest_nonce);
            },
            timeout: 120000, // ۲ دقیقه timeout
            success: function(response) {
                if (response.success && response.content) {
                    showResult(response.content);
                    trackGeneration('success');
                } else {
                    showError('خطا در دریافت پاسخ از سرور');
                    trackGeneration('error', 'Invalid response');
                }
            },
            error: function(xhr) {
                let errorMessage = 'خطا در اتصال به سرور';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 400) {
                    errorMessage = 'درخواست نامعتبر. لطفاً تنظیمات را بررسی کنید.';
                } else if (xhr.status === 403) {
                    errorMessage = 'عدم مجوز دسترسی';
                } else if (xhr.status === 500) {
                    errorMessage = 'خطای سرور. لطفاً مجدداً تلاش کنید.';
                } else if (xhr.status === 0) {
                    errorMessage = 'خطا در اتصال به اینترنت';
                }
                
                showError(errorMessage);
                trackGeneration('error', errorMessage);
            },
            complete: function() {
                hideLoading();
                isGenerating = false;
            }
        });
    }

    // نمایش حالت بارگذاری
    function showLoading() {
        const $btn = $('#generate-btn');
        const $btnText = $btn.find('.btn-text');
        const $btnLoading = $btn.find('.btn-loading');
        
        $btn.prop('disabled', true);
        $btnText.hide();
        $btnLoading.show();
        
        // انیمیشن پالس برای فرم
        $('#ai-content-form').addClass('generating');
    }

    // مخفی کردن حالت بارگذاری
    function hideLoading() {
        const $btn = $('#generate-btn');
        const $btnText = $btn.find('.btn-text');
        const $btnLoading = $btn.find('.btn-loading');
        
        $btn.prop('disabled', false);
        $btnText.show();
        $btnLoading.hide();
        
        $('#ai-content-form').removeClass('generating');
    }

    // نمایش نتیجه
    function showResult(content) {
        const $result = $('#ai-result');
        const $content = $('#ai-content');
        
        $content.html(content);
        $result.show();
        
        // اسکرول به نتیجه
        $result[0].scrollIntoView({ 
            behavior: 'smooth',
            block: 'start'
        });
        
        // انیمیشن ظهور
        $result.hide().fadeIn(500);
        
        // افزودن انیمیشن تایپ
        typewriterEffect($content, content);
    }

    // مخفی کردن نتیجه
    function hideResult() {
        $('#ai-result').hide();
    }

    // نمایش خطا
    function showError(message) {
        const $error = $('#ai-error');
        const $message = $('#error-message');
        
        $message.text(message);
        $error.show();
        
        // اسکرول به خطا
        $error[0].scrollIntoView({ 
            behavior: 'smooth',
            block: 'center'
        });
        
        // مخفی کردن خودکار بعد از ۵ثانیه
        setTimeout(() => {
            hideError();
        }, 5000);
    }

    // مخفی کردن خطا
    function hideError() {
        $('#ai-error').hide();
    }

    // کپی محتوا
    function copyContent() {
        const content = $('#ai-content').text();
        
        if (!content) {
            showError('محتوایی برای کپی وجود ندارد');
            return;
        }

        // استفاده از Clipboard API جدید
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(content).then(() => {
                showCopyNotification();
                trackAction('copy', 'clipboard_api');
            }).catch(() => {
                fallbackCopy(content);
            });
        } else {
            fallbackCopy(content);
        }
    }

    // روش جایگزین برای کپی
    function fallbackCopy(content) {
        const textArea = document.createElement('textarea');
        textArea.value = content;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopyNotification();
            trackAction('copy', 'fallback');
        } catch (err) {
            showError('خطا در کپی محتوا');
            trackAction('copy_error', err.message);
        }
        
        document.body.removeChild(textArea);
    }

    // نمایش اعلان کپی
    function showCopyNotification() {
        const $notification = $('#copy-notification');
        $notification.show();
        
        setTimeout(() => {
            $notification.fadeOut();
        }, 2000);
    }

    // دانلود محتوا
    function downloadContent() {
        const content = $('#ai-content').text();
        const prompt = $('#ai-prompt').val();
        
        if (!content) {
            showError('محتوایی برای دانلود وجود ندارد');
            return;
        }

        const filename = generateFilename(prompt);
        const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
        const url = window.URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        trackAction('download', filename);
    }

    // ایجاد نام فایل
    function generateFilename(prompt) {
        const date = new Date();
        const dateStr = date.toISOString().slice(0, 10);
        const timeStr = date.toTimeString().slice(0, 5).replace(':', '');
        
        let filename = prompt.slice(0, 30).replace(/[^\w\s]/g, '').trim();
        filename = filename.replace(/\s+/g, '_');
        
        if (!filename) {
            filename = 'ai_content';
        }
        
        return `${filename}_${dateStr}_${timeStr}.txt`;
    }

    // افکت تایپ‌رایتر
    function typewriterEffect($element, text) {
        if (!text) return;
        
        $element.empty();
        let i = 0;
        const speed = 10; // سرعت تایپ (میلی‌ثانیه)
        
        function typeChar() {
            if (i < text.length) {
                $element.text($element.text() + text.charAt(i));
                i++;
                setTimeout(typeChar, speed);
            }
        }
        
        // شروع تایپ با تاخیر کوتاه
        setTimeout(typeChar, 200);
    }

    // ردیابی تولید محتوا
    function trackGeneration(status, error = null) {
        const data = {
            status: status,
            timestamp: new Date().getTime(),
            content_type: $('#content-type').val(),
            content_length: $('#content-length').val(),
            prompt_length: $('#ai-prompt').val().length
        };
        
        if (error) {
            data.error = error;
        }
        
        // ارسال به Google Analytics اگر موجود باشد
        if (typeof gtag !== 'undefined') {
            gtag('event', 'ai_content_generation', {
                event_category: 'AI Generator',
                event_label: status,
                custom_map: data
            });
        }
        
        // ذخیره در localStorage برای آمار محلی
        saveLocalStats(data);
    }

    // ردیابی اکشن‌ها
    function trackAction(action, details = null) {
        const data = {
            action: action,
            timestamp: new Date().getTime()
        };
        
        if (details) {
            data.details = details;
        }
        
        if (typeof gtag !== 'undefined') {
            gtag('event', 'ai_generator_action', {
                event_category: 'AI Generator',
                event_label: action,
                custom_map: data
            });
        }
    }

    // ذخیره آمار محلی
    function saveLocalStats(data) {
        let stats = JSON.parse(localStorage.getItem('ai_generator_stats') || '[]');
        stats.push(data);
        
        // نگه‌داری آخرین ۱۰۰ رکورد
        if (stats.length > 100) {
            stats = stats.slice(-100);
        }
        
        localStorage.setItem('ai_generator_stats', JSON.stringify(stats));
    }

    // بهبود تجربه کاربری با کلیدهای میانبر
    $(document).keydown(function(e) {
        // Ctrl/Cmd + Enter برای تولید سریع
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
            e.preventDefault();
            if (!isGenerating && $('#ai-prompt').val().trim()) {
                $('#ai-content-form').submit();
            }
        }
        
        // Escape برای لغو تولید (اگر امکان‌پذیر باشد)
        if (e.keyCode === 27 && isGenerating) {
            // در صورت نیاز می‌توان درخواست را لغو کرد
            // xhr.abort();
        }
    });

    // بهبود قابلیت دسترسی
    function enhanceAccessibility() {
        // اضافه کردن ARIA labels
        $('#ai-prompt').attr('aria-describedby', 'prompt-help');
        $('#content-type').attr('aria-describedby', 'type-help');
        $('#content-length').attr('aria-describedby', 'length-help');
        
        // اضافه کردن role ها
        $('#ai-result').attr('role', 'region').attr('aria-label', 'محتوای تولید شده');
        $('#ai-error').attr('role', 'alert').attr('aria-live', 'polite');
        
        // مدیریت فوکوس
        $(document).on('keydown', function(e) {
            if (e.keyCode === 9) { // Tab key
                // بهبود navigation با Tab
                handleTabNavigation(e);
            }
        });
    }

    // مدیریت navigation با Tab
    function handleTabNavigation(e) {
        const focusableElements = [
            '#ai-prompt',
            '#content-type', 
            '#content-length',
            '#generate-btn',
            '#copy-btn',
            '#download-btn',
            '#theme-toggle'
        ];
        
        const currentIndex = focusableElements.indexOf(document.activeElement.id);
        
        if (e.shiftKey) {
            // Shift + Tab (برگشت)
            if (currentIndex <= 0) {
                e.preventDefault();
                $(focusableElements[focusableElements.length - 1]).focus();
            }
        } else {
            // Tab (جلو)
            if (currentIndex >= focusableElements.length - 1) {
                e.preventDefault();
                $(focusableElements[0]).focus();
            }
        }
    }

    // اضافه کردن placeholder های پویا
    function addDynamicPlaceholders() {
        const placeholders = [
            'درباره فواید ورزش کردن صبحگاهی...',
            'روش‌های افزایش بهره‌وری در محل کار...',
            'تاثیر تکنولوژی بر زندگی روزمره...',
            'راهنمای سفر به شمال ایران...',
            'نکات مهم در انتخاب رشته تحصیلی...',
            'روش‌های مراقبت از گیاهان خانگی...'
        ];
        
        let currentPlaceholder = 0;
        
        setInterval(() => {
            $('#ai-prompt').attr('placeholder', placeholders[currentPlaceholder]);
            currentPlaceholder = (currentPlaceholder + 1) % placeholders.length;
        }, 3000);
    }

    // بارگذاری تنظیمات کاربر از localStorage
    function loadUserPreferences() {
        const prefs = JSON.parse(localStorage.getItem('ai_generator_prefs') || '{}');
        
        if (prefs.content_type) {
            $('#content-type').val(prefs.content_type);
        }
        
        if (prefs.content_length) {
            $('#content-length').val(prefs.content_length);
        }
    }

    // ذخیره تنظیمات کاربر
    function saveUserPreferences() {
        const prefs = {
            content_type: $('#content-type').val(),
            content_length: $('#content-length').val(),
            last_updated: new Date().getTime()
        };
        
        localStorage.setItem('ai_generator_prefs', JSON.stringify(prefs));
    }

    // رویداد تغییر تنظیمات
    $(document).on('change', '#content-type, #content-length', saveUserPreferences);

    // راه‌اندازی کامل
    $(document).ready(function() {
        enhanceAccessibility();
        addDynamicPlaceholders();
        loadUserPreferences();
        
        // نمایش پیام خوش‌آمدگویی برای اولین بازدید
        if (!localStorage.getItem('ai_generator_visited')) {
            setTimeout(() => {
                showWelcomeMessage();
                localStorage.setItem('ai_generator_visited', 'true');
            }, 1000);
        }
    });

    // پیام خوش‌آمدگویی
    function showWelcomeMessage() {
        const $welcome = $('<div class="welcome-message">')
            .html(`
                <h3>🎉 به تولیدکننده محتوای هوشمند خوش آمدید!</h3>
                <p>با استفاده از قدرت هوش مصنوعی، محتوای باکیفیت تولید کنید.</p>
                <p><strong>نکته:</strong> از کلید میانبر <kbd>Ctrl + Enter</kbd> برای تولید سریع استفاده کنید.</p>
                <button class="welcome-close">متوجه شدم</button>
            `)
            .css({
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                background: 'white',
                padding: '2rem',
                borderRadius: '12px',
                boxShadow: '0 20px 25px -5px rgba(0, 0, 0, 0.3)',
                zIndex: 1000,
                maxWidth: '400px',
                textAlign: 'center'
            });
        
        $('body').append($welcome);
        
        // بستن پیام
        $('.welcome-close').on('click', function() {
            $welcome.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }

    // مدیریت خطاهای JavaScript
    window.onerror = function(msg, url, lineNo, columnNo, error) {
        trackAction('javascript_error', {
            message: msg,
            url: url,
            line: lineNo,
            column: columnNo,
            error: error ? error.toString() : null
        });
        return false;
    };

})(jQuery);