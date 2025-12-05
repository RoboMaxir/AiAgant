// assets/js/editor.js
(function($) {
    'use strict';

    let isGenerating = false;
    let currentVariations = [];

    // راه‌اندازی اولیه
    $(window).on('elementor:init', function() {
        initializeElementorAI();
    });

    function initializeElementorAI() {
        // افزودن event listeners به Elementor
        elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
            setupAIControls(panel, model, view);
        });

        // اضافه کردن event listeners سراسری
        setupGlobalEventListeners();
        
        // اضافه کردن کلاس‌های CSS
        addCustomStyles();
    }

    function setupGlobalEventListeners() {
        // Event listener برای تولید محتوا
        $(document).on('click', '.elementor-control-generate_button .elementor-button', function(e) {
            e.preventDefault();
            const model = elementor.getPanelView().getCurrentPageView().model;
            generateContentForWidget(model);
        });

        // Event listeners برای سایر دکمه‌ها
        $(document).on('click', '.elementor-control-generate_variations .elementor-button', function(e) {
            e.preventDefault();
            const model = elementor.getPanelView().getCurrentPageView().model;
            generateVariationsForWidget(model);
        });

        $(document).on('click', '.elementor-control-improve_content .elementor-button', function(e) {
            e.preventDefault();
            const model = elementor.getPanelView().getCurrentPageView().model;
            improveContentForWidget(model);
        });
    }

    function setupAIControls(panel, model, view) {
        // افزودن observer برای تغییرات کنترل‌ها
        setTimeout(() => {
            enhanceAIControls(panel, model);
        }, 100);
    }

    function enhanceAIControls(panel, model) {
        // افزودن دکمه‌های اضافی
        const aiSection = panel.$el.find('.elementor-control-ai_generator_section');
        if (aiSection.length) {
            addAIEnhancements(aiSection, model);
        }
    }

    function addAIEnhancements(section, model) {
        // افزودن پیش‌نمایش تاریخچه
        const historyHtml = `
            <div class="elementor-control elementor-control-type-raw_html">
                <div class="elementor-control-content">
                    <div class="ai-history-preview">
                        <button type="button" class="elementor-button elementor-button-default view-ai-history">
                            <i class="eicon-history"></i>
                            ${elementorAI.strings.view_history || 'View History'}
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        section.append(historyHtml);
        
        // Event listener برای تاریخچه
        section.find('.view-ai-history').on('click', function() {
            showAIHistory(model);
        });
    }

    function generateContentForWidget(model) {
        if (isGenerating) {
            showNotification('در حال تولید محتوا...', 'info');
            return;
        }

        const settings = model.get('settings').attributes;
        
        // اعتبارسنجی
        if (!settings.ai_prompt || settings.ai_prompt.trim() === '') {
            showNotification('لطفاً توضیح محتوای مورد نظر خود را وارد کنید', 'error');
            highlightControl('ai_prompt');
            return;
        }

        // آماده‌سازی داده‌ها
        const requestData = {
            prompt: settings.ai_prompt,
            content_type: settings.ai_content_type || 'paragraph',
            length: settings.ai_length || 'medium',
            tone: settings.ai_tone || 'professional',
            language: settings.ai_language || 'persian',
            widget_type: model.get('widgetType'),
            keywords: settings.ai_include_keywords || '',
            element_id: model.get('id')
        };

        // نمایش loading
        showLoadingState(model, true);
        isGenerating = true;

        // ارسال درخواست
        $.ajax({
            url: elementorAI.rest_url + 'generate',
            type: 'POST',
            data: JSON.stringify(requestData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', elementorAI.rest_nonce);
            },
            success: function(response) {
                if (response.success && response.content) {
                    applyGeneratedContent(model, response.content, response.meta);
                    showNotification('محتوا با موفقیت تولید شد!', 'success');
                    
                    // نمایش گزینه‌های اضافی
                    showContentActions(model, response.content);
                } else {
                    showNotification('خطا در تولید محتوا', 'error');
                }
            },
            error: function(xhr) {
                handleAjaxError(xhr);
            },
            complete: function() {
                showLoadingState(model, false);
                isGenerating = false;
            }
        });
    }

    function generateVariationsForWidget(model) {
        const settings = model.get('settings').attributes;
        const currentContent = getCurrentContent(model);
        
        if (!currentContent) {
            showNotification('ابتدا محتوایی تولید کنید', 'error');
            return;
        }

        showLoadingState(model, true, 'در حال تولید تنوع‌ها...');

        $.ajax({
            url: elementorAI.rest_url + 'generate-variations',
            type: 'POST',
            data: JSON.stringify({
                content: currentContent,
                count: 3,
                widget_type: model.get('widgetType')
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', elementorAI.rest_nonce);
            },
            success: function(response) {
                if (response.success && response.variations) {
                    showVariationsModal(model, response.variations);
                } else {
                    showNotification('خطا در تولید تنوع‌ها', 'error');
                }
            },
            error: handleAjaxError,
            complete: function() {
                showLoadingState(model, false);
            }
        });
    }

    function improveContentForWidget(model) {
        const currentContent = getCurrentContent(model);
        
        if (!currentContent) {
            showNotification('محتوایی برای بهبود وجود ندارد', 'error');
            return;
        }

        // نمایش گزینه‌های بهبود
        showImprovementOptions(model, currentContent);
    }

    function showImprovementOptions(model, content) {
        const improvements = [
            { type: 'general', label: 'بهبود کلی', icon: 'eicon-editor-bold' },
            { type: 'seo', label: 'بهینه‌سازی SEO', icon: 'eicon-search' },
            { type: 'readability', label: 'افزایش خوانایی', icon: 'eicon-text' },
            { type: 'engagement', label: 'جذابیت بیشتر', icon: 'eicon-heart' },
            { type: 'professional', label: 'حرفه‌ای‌تر', icon: 'eicon-user-circle-o' }
        ];

        let modalHtml = `
            <div class="ai-improvement-modal">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>نوع بهبود مورد نظر را انتخاب کنید</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="improvement-options">
        `;

        improvements.forEach(improvement => {
            modalHtml += `
                <button class="improvement-option" data-type="${improvement.type}">
                    <i class="${improvement.icon}"></i>
                    <span>${improvement.label}</span>
                </button>
            `;
        });

        modalHtml += `
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);

        // Event listeners
        $('.ai-improvement-modal .modal-close, .ai-improvement-modal .modal-overlay').on('click', function() {
            $('.ai-improvement-modal').remove();
        });

        $('.improvement-option').on('click', function() {
            const improvementType = $(this).data('type');
            $('.ai-improvement-modal').remove();
            performImprovement(model, content, improvementType);
        });
    }

    function performImprovement(model, content, improvementType) {
        showLoadingState(model, true, 'در حال بهبود محتوا...');

        $.ajax({
            url: elementorAI.rest_url + 'improve-content',
            type: 'POST',
            data: JSON.stringify({
                content: content,
                improvement_type: improvementType,
                widget_type: model.get('widgetType')
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', elementorAI.rest_nonce);
            },
            success: function(response) {
                if (response.success && response.improved_content) {
                    showComparisonModal(model, content, response.improved_content);
                } else {
                    showNotification('خطا در بهبود محتوا', 'error');
                }
            },
            error: handleAjaxError,
            complete: function() {
                showLoadingState(model, false);
            }
        });
    }

    function showComparisonModal(model, originalContent, improvedContent) {
        const modalHtml = `
            <div class="ai-comparison-modal">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>مقایسه محتوا</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="comparison-container">
                            <div class="content-column">
                                <h4>محتوای فعلی</h4>
                                <div class="content-preview">${originalContent}</div>
                            </div>
                            <div class="content-column">
                                <h4>محتوای بهبود یافته</h4>
                                <div class="content-preview improved">${improvedContent}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="elementor-button apply-improved">اعمال محتوای بهبود یافته</button>
                        <button class="elementor-button elementor-button-default keep-original">نگه داشتن محتوای فعلی</button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);

        // Event listeners
        $('.ai-comparison-modal .modal-close, .ai-comparison-modal .keep-original').on('click', function() {
            $('.ai-comparison-modal').remove();
        });

        $('.apply-improved').on('click', function() {
            applyGeneratedContent(model, improvedContent);
            $('.ai-comparison-modal').remove();
            showNotification('محتوای بهبود یافته اعمال شد', 'success');
        });
    }

    function showVariationsModal(model, variations) {
        let modalHtml = `
            <div class="ai-variations-modal">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>تنوع‌های تولید شده</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="variations-container">
        `;

        variations.forEach((variation, index) => {
            modalHtml += `
                <div class="variation-item">
                    <div class="variation-header">
                        <h4>نسخه ${index + 1}</h4>
                        <button class="apply-variation" data-content="${encodeURIComponent(variation)}">
                            اعمال
                        </button>
                    </div>
                    <div class="variation-content">${variation}</div>
                </div>
            `;
        });

        modalHtml += `
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="elementor-button generate-more">تولید تنوع‌های بیشتر</button>
                        <button class="elementor-button elementor-button-default modal-close">بستن</button>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);
        currentVariations = variations;

        // Event listeners
        $('.ai-variations-modal .modal-close').on('click', function() {
            $('.ai-variations-modal').remove();
        });

        $('.apply-variation').on('click', function() {
            const content = decodeURIComponent($(this).data('content'));
            applyGeneratedContent(model, content);
            $('.ai-variations-modal').remove();
            showNotification('تنوع انتخابی اعمال شد', 'success');
        });

        $('.generate-more').on('click', function() {
            $('.ai-variations-modal').remove();
            generateVariationsForWidget(model);
        });
    }

    function applyGeneratedContent(model, content, meta = null) {
        const widgetType = model.get('widgetType');
        
        // تعیین کنترل مناسب برای هر ویجت
        let controlName = 'text_content';
        
        switch (widgetType) {
            case 'heading':
                controlName = 'title';
                break;
            case 'button':
                controlName = 'text';
                break;
            case 'text-editor':
                controlName = 'editor';
                break;
            case 'ai-text':
                controlName = 'text_content';
                break;
        }

        // اعمال محتوا
        model.setSetting(controlName, content);
        
        // به‌روزرسانی پنل
        if (elementor.getPanelView().getCurrentPageView()) {
            elementor.getPanelView().getCurrentPageView().render();
        }

        // ذخیره در تاریخچه
        if (meta) {
            saveToHistory(model, content, meta);
        }
    }

    function getCurrentContent(model) {
        const widgetType = model.get('widgetType');
        const settings = model.get('settings').attributes;
        
        switch (widgetType) {
            case 'heading':
                return settings.title || '';
            case 'button':
                return settings.text || '';
            case 'text-editor':
                return settings.editor || '';
            case 'ai-text':
                return settings.text_content || '';
            default:
                return '';
        }
    }

    function showLoadingState(model, show, message = null) {
        const elementId = model.get('id');
        const widgetElement = $(`[data-id="${elementId}"]`);
        
        if (show) {
            const loadingHtml = `
                <div class="ai-loading-overlay">
                    <div class="ai-loading-spinner"></div>
                    <div class="ai-loading-text">${message || elementorAI.strings.generating}</div>
                </div>
            `;
            widgetElement.append(loadingHtml);
        } else {
            widgetElement.find('.ai-loading-overlay').remove();
        }
    }

    function showContentActions(model, content) {
        // نمایش دکمه‌های عمل اضافی در کنار محتوای تولیدی
        const actionsHtml = `
            <div class="ai-content-actions">
                <button class="action-btn regenerate" title="تولید مجدد">
                    <i class="eicon-refresh"></i>
                </button>
                <button class="action-btn copy" title="کپی">
                    <i class="eicon-copy"></i>
                </button>
                <button class="action-btn variations" title="تولید تنوع‌ها">
                    <i class="eicon-posts-group"></i>
                </button>
            </div>
        `;
        
        // اضافه کردن به پنل کنترل
        const currentPanel = elementor.getPanelView().getCurrentPageView();
        if (currentPanel) {
            currentPanel.$el.find('.elementor-control-ai_generator_section').append(actionsHtml);
        }
    }

    function showAIHistory(model) {
        // نمایش تاریخچه تولیدات برای این المنت
        $.ajax({
            url: elementorAI.rest_url + 'element-history',
            type: 'GET',
            data: { element_id: model.get('id') },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', elementorAI.rest_nonce);
            },
            success: function(response) {
                if (response.success && response.history) {
                    displayHistoryModal(model, response.history);
                }
            },
            error: handleAjaxError
        });
    }

    function displayHistoryModal(model, history) {
        let modalHtml = `
            <div class="ai-history-modal">
                <div class="modal-overlay"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>تاریخچه تولیدات</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="history-list">
        `;

        if (history.length === 0) {
            modalHtml += '<p class="no-history">هنوز تاریخچه‌ای ثبت نشده است.</p>';
        } else {
            history.forEach(item => {
                modalHtml += `
                    <div class="history-item">
                        <div class="history-meta">
                            <span class="date">${item.created_at}</span>
                            <span class="prompt">${item.prompt}</span>
                        </div>
                        <div class="history-content">${item.generated_content.substring(0, 150)}...</div>
                        <div class="history-actions">
                            <button class="apply-history" data-content="${encodeURIComponent(item.generated_content)}">
                                اعمال
                            </button>
                        </div>
                    </div>
                `;
            });
        }

        modalHtml += `
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);

        // Event listeners
        $('.ai-history-modal .modal-close').on('click', function() {
            $('.ai-history-modal').remove();
        });

        $('.apply-history').on('click', function() {
            const content = decodeURIComponent($(this).data('content'));
            applyGeneratedContent(model, content);
            $('.ai-history-modal').remove();
            showNotification('محتوا از تاریخچه اعمال شد', 'success');
        });
    }

    function saveToHistory(model, content, meta) {
        const settings = model.get('settings').attributes;
        
        $.ajax({
            url: elementorAI.rest_url + 'save-history',
            type: 'POST',
            data: JSON.stringify({
                element_id: model.get('id'),
                widget_type: model.get('widgetType'),
                prompt: settings.ai_prompt,
                content: content,
                meta: meta
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', elementorAI.rest_nonce);
            }
        });
    }

    function showNotification(message, type = 'info') {
        const notificationHtml = `
            <div class="ai-notification ai-notification-${type}">
                <span class="notification-text">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        $('body').append(notificationHtml);
        
        // خودکار حذف شدن
        setTimeout(() => {
            $('.ai-notification').fadeOut(300, function() {
                $(this).remove();
            });
        }, 4000);
        
        // دکمه بستن
        $('.notification-close').on('click', function() {
            $(this).parent().fadeOut(300, function() {
                $(this).remove();
            });
        });
    }

    function highlightControl(controlName) {
        const control = $(`.elementor-control-${controlName}`);
        if (control.length) {
            control.addClass('elementor-control-error');
            setTimeout(() => {
                control.removeClass('elementor-control-error');
            }, 3000);
        }
    }

    function handleAjaxError(xhr) {
        let message = 'خطا در ارتباط با سرور';
        
        if (xhr.responseJSON && xhr.responseJSON.message) {
            message = xhr.responseJSON.message;
        } else if (xhr.status === 0) {
            message = 'خطا در اتصال به اینترنت';
        } else if (xhr.status === 400) {
            message = 'درخواست نامعتبر';
        } else if (xhr.status === 403) {
            message = 'عدم مجوز دسترسی';
        }
        
        showNotification(message, 'error');
    }

    function addCustomStyles() {
        const styles = `
            <style id="elementor-ai-editor-styles">
                .ai-loading-overlay {
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255, 255, 255, 0.9);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    z-index: 1000;
                }
                
                .ai-loading-spinner {
                    width: 40px;
                    height: 40px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #6366f1;
                    border-radius: 50%;
                    animation: aiSpin 1s linear infinite;
                    margin-bottom: 10px;
                }
                
                @keyframes aiSpin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .ai-loading-text {
                    color: #6366f1;
                    font-size: 14px;
                    font-weight: 500;
                }
                
                .ai-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 8px;
                    color: white;
                    font-weight: 500;
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    animation: slideInRight 0.3s ease-out;
                }
                
                .ai-notification-success { background: #10b981; }
                .ai-notification-error { background: #ef4444; }
                .ai-notification-info { background: #6366f1; }
                
                @keyframes slideInRight {
                    from { transform: translateX(100px); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                
                .notification-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 18px;
                    cursor: pointer;
                }
            </style>
        `;
        
        $('head').append(styles);
    }

})(jQuery);