// assets/js/admin.js
(function($) {
    'use strict';

    $(document).ready(function() {
        initializeAdmin();
        bindEvents();
    });

    // راه‌اندازی اولیه صفحه مدیریت
    function initializeAdmin() {
        // بارگذاری آمار
        loadStats();
        
        // تنظیم tooltips
        setupTooltips();
        
        // بررسی وضعیت API
        checkApiStatus();
    }

    // اتصال رویدادها
    function bindEvents() {
        // تست اتصال API
        $(document).on('click', '#test-connection', testApiConnection);
        
        // نمایش/مخفی کردن کلید API
        $(document).on('click', '#toggle-api-key', toggleApiKeyVisibility);
        
        // کپی shortcode
        $(document).on('click', '.copy-shortcode', copyShortcode);
        
        // مشاهده محتوا در مودال
        $(document).on('click', '.view-content', viewContentModal);
        
        // کپی محتوا از تاریخچه
        $(document).on('click', '.copy-content', copyHistoryContent);
        
        // حذف آیتم تاریخچه
        $(document).on('click', '.delete-item', deleteHistoryItem);
        
        // بستن مودال
        $(document).on('click', '.close-modal', closeModal);
        $(document).on('click', '#content-modal', function(e) {
            if (e.target === this) closeModal();
        });
        
        // کپی از مودال
        $(document).on('click', '#modal-copy-btn', copyModalContent);
        
        // فیلتر جدول تاریخچه
        $(document).on('input', '#history-search', filterHistory);
        
        // تغییر فیلتر نوع محتوا
        $(document).on('change', '#content-type-filter', filterByContentType);
        
        // صادرات تاریخچه
        $(document).on('click', '#export-history', exportHistory);
    }

    // تست اتصال API
    function testApiConnection() {
        const $btn = $('#test-connection');
        const $result = $('#connection-result');
        const apiKey = $('#api_key').val();
        
        if (!apiKey) {
            showConnectionResult('error', 'لطفاً ابتدا کلید API را وارد کنید.');
            return;
        }
        
        $btn.prop('disabled', true).text('در حال تست...');
        $result.removeClass('success error').text('');
        
        // تست ساده با API
        $.ajax({
            url: aiContentGenerator.rest_url + 'test-api',
            type: 'POST',
            data: JSON.stringify({ api_key: apiKey }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiContentGenerator.rest_nonce);
            },
            success: function(response) {
                if (response.success) {
                    showConnectionResult('success', '✅ اتصال با موفقیت برقرار شد!');
                    trackEvent('api_test', 'success');
                } else {
                    showConnectionResult('error', '❌ خطا: ' + response.message);
                    trackEvent('api_test', 'failed', response.message);
                }
            },
            error: function(xhr) {
                let message = 'خطا در اتصال به API';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showConnectionResult('error', '❌ ' + message);
                trackEvent('api_test', 'error', message);
            },
            complete: function() {
                $btn.prop('disabled', false).text('تست اتصال API');
            }
        });
    }

    // نمایش نتیجه تست اتصال
    function showConnectionResult(type, message) {
        const $result = $('#connection-result');
        $result.removeClass('success error').addClass(type).text(message);
        
        // مخفی کردن بعد از 5 ثانیه
        setTimeout(() => {
            $result.fadeOut();
        }, 5000);
    }

    // تغییر نمایش کلید API
    function toggleApiKeyVisibility() {
        const $input = $('#api_key');
        const $btn = $('#toggle-api-key');
        
        if ($input.attr('type') === 'password') {
            $input.attr('type', 'text');
            $btn.text('مخفی کردن');
        } else {
            $input.attr('type', 'password');
            $btn.text('نمایش');
        }
    }

    // کپی shortcode
    function copyShortcode() {
        const shortcode = $(this).data('shortcode');
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(shortcode).then(() => {
                showCopyNotification('شورت‌کد کپی شد!');
                trackEvent('shortcode_copy', 'success');
            }).catch(() => {
                fallbackCopy(shortcode, 'شورت‌کد کپی شد!');
            });
        } else {
            fallbackCopy(shortcode, 'شورت‌کد کپی شد!');
        }
    }

    // نمایش محتوا در مودال
    function viewContentModal() {
        const itemId = $(this).data('id');
        
        // بارگذاری محتوا از API
        $.ajax({
            url: aiContentGenerator.rest_url + 'history/' + itemId,
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiContentGenerator.rest_nonce);
            },
            success: function(response) {
                if (response.success && response.content) {
                    $('#modal-content-text').text(response.content.generated_content);
                    $('#modal-copy-btn').data('content', response.content.generated_content);
                    $('#content-modal').show();
                    
                    // فوکوس روی مودال
                    $('#content-modal .modal-content').focus();
                    trackEvent('content_view', 'modal', itemId);
                }
            },
            error: function() {
                alert('خطا در بارگذاری محتوا');
            }
        });
    }

    // کپی محتوا از تاریخچه
    function copyHistoryContent() {
        const content = $(this).data('content');
        
        if (!content) {
            alert('محتوایی برای کپی وجود ندارد');
            return;
        }
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(content).then(() => {
                showCopyNotification('محتوا کپی شد!');
                trackEvent('history_copy', 'success');
            }).catch(() => {
                fallbackCopy(content, 'محتوا کپی شد!');
            });
        } else {
            fallbackCopy(content, 'محتوا کپی شد!');
        }
    }

    // حذف آیتم از تاریخچه
    function deleteHistoryItem() {
        const itemId = $(this).data('id');
        const $row = $(this).closest('tr');
        
        if (!confirm('آیا مطمئن هستید که می‌خواهید این آیتم را حذف کنید؟')) {
            return;
        }
        
        $.ajax({
            url: aiContentGenerator.rest_url + 'history/' + itemId,
            type: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiContentGenerator.rest_nonce);
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        updateHistoryStats();
                    });
                    trackEvent('history_delete', 'success', itemId);
                } else {
                    alert('خطا در حذف آیتم');
                }
            },
            error: function() {
                alert('خطا در حذف آیتم');
            }
        });
    }

    // بستن مودال
    function closeModal() {
        $('#content-modal').hide();
        $('body').removeClass('modal-open');
    }

    // کپی محتوا از مودال
    function copyModalContent() {
        const content = $(this).data('content');
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(content).then(() => {
                showCopyNotification('محتوا کپی شد!');
                closeModal();
                trackEvent('modal_copy', 'success');
            }).catch(() => {
                fallbackCopy(content, 'محتوا کپی شد!');
                closeModal();
            });
        } else {
            fallbackCopy(content, 'محتوا کپی شد!');
            closeModal();
        }
    }

    // جستجو در تاریخچه
    function filterHistory() {
        const searchTerm = $(this).val().toLowerCase();
        const $rows = $('.history-table-container tbody tr');
        
        $rows.each(function() {
            const $row = $(this);
            const prompt = $row.find('.prompt-preview').text().toLowerCase();
            
            if (prompt.includes(searchTerm)) {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        trackEvent('history_search', 'filter', searchTerm);
    }

    // فیلتر بر اساس نوع محتوا
    function filterByContentType() {
        const contentType = $(this).val();
        const $rows = $('.history-table-container tbody tr');
        
        if (!contentType) {
            $rows.show();
            return;
        }
        
        $rows.each(function() {
            const $row = $(this);
            const $badge = $row.find('.content-type-badge');
            
            if ($badge.hasClass(contentType)) {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        trackEvent('history_filter', 'content_type', contentType);
    }

    // صادرات تاریخچه
    function exportHistory() {
        const format = $('#export-format').val() || 'csv';
        
        $.ajax({
            url: aiContentGenerator.rest_url + 'export-history',
            type: 'GET',
            data: { format: format },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiContentGenerator.rest_nonce);
            },
            success: function(response) {
                if (response.success && response.download_url) {
                    // دانلود فایل
                    const link = document.createElement('a');
                    link.href = response.download_url;
                    link.download = response.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    trackEvent('history_export', format);
                }
            },
            error: function() {
                alert('خطا در صادرات فایل');
            }
        });
    }

    // روش جایگزین برای کپی
    function fallbackCopy(content, successMessage) {
        const textArea = document.createElement('textarea');
        textArea.value = content;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopyNotification(successMessage);
            trackEvent('copy', 'fallback');
        } catch (err) {
            alert('خطا در کپی محتوا');
        }
        
        document.body.removeChild(textArea);
    }

    // نمایش اعلان کپی
    function showCopyNotification(message) {
        const $notification = $('#copy-notification');
        $notification.text(message).show();
        
        setTimeout(() => {
            $notification.fadeOut();
        }, 2000);
    }

    // بارگذاری آمار
    function loadStats() {
        $.ajax({
            url: aiContentGenerator.rest_url + 'stats',
            type: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', aiContentGenerator.rest_nonce);
            },
            success: function(response) {
                if (response.success) {
                    updateStatsDisplay(response.stats);
                }
            },
            error: function() {
                console.warn('خطا در بارگذاری آمار');
            }
        });
    }

    // به‌روزرسانی نمایش آمار
    function updateStatsDisplay(stats) {
        if (stats.total_generations !== undefined) {
            $('#total-generations').text(stats.total_generations);
        }
        
        if (stats.today_generations !== undefined) {
            $('.stat-card:nth-child(2) .stat-info h3').text(stats.today_generations);
        }
        
        if (stats.month_generations !== undefined) {
            $('.stat-card:nth-child(3) .stat-info h3').text(stats.month_generations);
        }
    }

    // به‌روزرسانی آمار تاریخچه
    function updateHistoryStats() {
        const visibleRows = $('.history-table-container tbody tr:visible').length;
        const totalRows = $('.history-table-container tbody tr').length;
        
        $('.stat-box:first-child .stat-number').text(totalRows);
    }

    // تنظیم tooltips
    function setupTooltips() {
        $('[title]').each(function() {
            const $element = $(this);
            const title = $element.attr('title');
            
            $element.hover(
                function() {
                    const $tooltip = $('<div class="admin-tooltip">')
                        .text(title)
                        .css({
                            position: 'absolute',
                            background: '#333',
                            color: 'white',
                            padding: '5px 10px',
                            borderRadius: '4px',
                            fontSize: '12px',
                            zIndex: 9999,
                            whiteSpace: 'nowrap'
                        });
                    
                    $('body').append($tooltip);
                    
                    const offset = $(this).offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 5,
                        left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    });
                },
                function() {
                    $('.admin-tooltip').remove();
                }
            );
            
            // حذف title برای جلوگیری از tooltip پیش‌فرض
            $element.removeAttr('title').data('title', title);
        });
    }

    // بررسی وضعیت API
    function checkApiStatus() {
        const apiKey = $('#api_key').val();
        const $statusIndicator = $('.stat-card:last-child');
        
        if (!apiKey) {
            $statusIndicator.find('.stat-icon').text('❌');
            $statusIndicator.find('h3').text('تنظیم نشده');
            $statusIndicator.addClass('error-status');
        } else {
            $statusIndicator.find('.stat-icon').text('✅');
            $statusIndicator.find('h3').text('آماده');
            $statusIndicator.removeClass('error-status');
        }
    }

    // ردیابی رویدادها
    function trackEvent(action, category, label = null) {
        const data = {
            action: action,
            category: category,
            timestamp: new Date().getTime()
        };
        
        if (label) {
            data.label = label;
        }
        
        // ارسال به Google Analytics اگر موجود باشد
        if (typeof gtag !== 'undefined') {
            gtag('event', action, {
                event_category: 'AI Generator Admin',
                event_label: label,
                custom_map: data
            });
        }
        
        // ذخیره در localStorage
        let events = JSON.parse(localStorage.getItem('ai_admin_events') || '[]');
        events.push(data);
        
        // نگه‌داری آخرین ۵۰ رویداد
        if (events.length > 50) {
            events = events.slice(-50);
        }
        
        localStorage.setItem('ai_admin_events', JSON.stringify(events));
    }

    // مدیریت خطاهای AJAX
    $(document).ajaxError(function(event, xhr, settings, thrownError) {
        if (xhr.status !== 0) { // نادیده گرفتن درخواست‌های لغو شده
            trackEvent('ajax_error', 'admin', {
                url: settings.url,
                status: xhr.status,
                error: thrownError
            });
        } 
    });

})(jQuery);