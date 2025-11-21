/**
 * Lokalizacja: /assets/js/frontend.js
 * Przyjaciel na Godzinę - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    const PNG = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initComponents();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Favorite toggle
            $(document).on('click', '.png-favorite-btn', this.toggleFavorite);
            
            // Rating stars
            $(document).on('click', '.png-rating-input label', this.handleRating);
            
            // Tabs
            $(document).on('click', '.png-tab-button', this.switchTab);
            
            // Modal
            $(document).on('click', '[data-toggle="modal"]', this.openModal);
            $(document).on('click', '.png-modal-close, .png-modal', this.closeModal);
            $(document).on('click', '.png-modal-content', function(e) {
                e.stopPropagation();
            });
            
            // Confirm dialogs
            $(document).on('click', '[data-confirm]', this.confirmAction);
            
            // Auto-resize textareas
            $(document).on('input', 'textarea.auto-resize', this.autoResize);
            
            // Form validation
            $(document).on('submit', 'form.validate', this.validateForm);
        },
        
        /**
         * Initialize components
         */
        initComponents: function() {
            this.initTooltips();
            this.initNotifications();
            this.checkUnreadMessages();
        },
        
        /**
         * Toggle favorite
         */
        toggleFavorite: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const listingId = $btn.data('listing-id');
            
            if (!pngData.currentUserId) {
                alert(pngData.strings.login_required || 'Musisz być zalogowany');
                return;
            }
            
            $.ajax({
                url: pngData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'png_toggle_favorite',
                    nonce: pngData.nonce,
                    listing_id: listingId
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        $btn.find('i').toggleClass('far fas');
                        $btn.toggleClass('active');
                        
                        PNG.showNotification(
                            response.data.is_favorite ? 'Dodano do ulubionych' : 'Usunięto z ulubionych',
                            'success'
                        );
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        /**
         * Handle rating click
         */
        handleRating: function() {
            const $label = $(this);
            const value = $label.data('value');
            const $input = $label.siblings('input[value="' + value + '"]');
            
            $input.prop('checked', true);
            $label.addClass('active').prevAll().addClass('active');
            $label.nextAll().removeClass('active');
        },
        
        /**
         * Switch tabs
         */
        switchTab: function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const targetId = $btn.data('target');
            
            // Update buttons
            $btn.addClass('active').siblings().removeClass('active');
            
            // Update content
            $(targetId).addClass('active').siblings('.png-tab-content').removeClass('active');
        },
        
        /**
         * Open modal
         */
        openModal: function(e) {
            e.preventDefault();
            
            const target = $(this).data('target');
            $(target).addClass('active');
            $('body').css('overflow', 'hidden');
        },
        
        /**
         * Close modal
         */
        closeModal: function(e) {
            if ($(e.target).hasClass('png-modal') || $(e.target).hasClass('png-modal-close')) {
                $('.png-modal').removeClass('active');
                $('body').css('overflow', '');
            }
        },
        
        /**
         * Confirm action
         */
        confirmAction: function(e) {
            const message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        },
        
        /**
         * Auto-resize textarea
         */
        autoResize: function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        },
        
        /**
         * Validate form
         */
        validateForm: function(e) {
            const $form = $(this);
            let isValid = true;
            
            // Clear previous errors
            $form.find('.png-form-error').remove();
            $form.find('.error').removeClass('error');
            
            // Check required fields
            $form.find('[required]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="png-form-error">To pole jest wymagane</span>');
                }
            });
            
            // Check email fields
            $form.find('input[type="email"]').each(function() {
                const $field = $(this);
                const value = $field.val().trim();
                
                if (value && !PNG.isValidEmail(value)) {
                    isValid = false;
                    $field.addClass('error');
                    $field.after('<span class="png-form-error">Nieprawidłowy adres email</span>');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $form.find('.error').first().offset().top - 100
                }, 500);
            }
            
            return isValid;
        },
        
        /**
         * Validate email
         */
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            const $notification = $('<div>')
                .addClass('png-notification png-notification-' + type)
                .html('<i class="fas fa-check-circle"></i> ' + message)
                .appendTo('body');
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 10);
            
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('[data-tooltip]').each(function() {
                const $elem = $(this);
                const text = $elem.data('tooltip');
                
                $elem.on('mouseenter', function() {
                    const $tooltip = $('<div>')
                        .addClass('png-tooltip')
                        .text(text)
                        .appendTo('body');
                    
                    const offset = $elem.offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 10,
                        left: offset.left + ($elem.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    });
                });
                
                $elem.on('mouseleave', function() {
                    $('.png-tooltip').remove();
                });
            });
        },
        
        /**
         * Initialize notification badge
         */
        initNotifications: function() {
            if (!pngData.currentUserId) return;
            
            this.updateNotificationCount();
            
            // Check every minute
            setInterval(function() {
                PNG.updateNotificationCount();
            }, 60000);
        },
        
        /**
         * Update notification count
         */
        updateNotificationCount: function() {
            $.ajax({
                url: pngData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'png_get_unread_count',
                    nonce: pngData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.count > 0) {
                        $('.png-notification-badge').text(response.data.count).show();
                    } else {
                        $('.png-notification-badge').hide();
                    }
                }
            });
        },
        
        /**
         * Check unread messages
         */
        checkUnreadMessages: function() {
            if (!pngData.currentUserId) return;
            
            $.ajax({
                url: pngData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'png_get_unread_messages_count',
                    nonce: pngData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.count > 0) {
                        $('.png-messages-badge').text(response.data.count).show();
                    }
                }
            });
        },
        
        /**
         * AJAX helper
         */
        ajax: function(action, data, callback) {
            data = data || {};
            data.action = action;
            data.nonce = pngData.nonce;
            
            $.ajax({
                url: pngData.ajaxUrl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function() {
                    PNG.showNotification(pngData.strings.error || 'Wystąpił błąd', 'error');
                }
            });
        },
        
        /**
         * Debounce function
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        /**
         * Format number
         */
        formatNumber: function(num) {
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
        },
        
        /**
         * Format date
         */
        formatDate: function(date) {
            const d = new Date(date);
            const day = ('0' + d.getDate()).slice(-2);
            const month = ('0' + (d.getMonth() + 1)).slice(-2);
            const year = d.getFullYear();
            return day + '.' + month + '.' + year;
        },
        
        /**
         * Time ago
         */
        timeAgo: function(date) {
            const seconds = Math.floor((new Date() - new Date(date)) / 1000);
            
            let interval = seconds / 31536000;
            if (interval > 1) return Math.floor(interval) + ' lat temu';
            
            interval = seconds / 2592000;
            if (interval > 1) return Math.floor(interval) + ' miesięcy temu';
            
            interval = seconds / 86400;
            if (interval > 1) return Math.floor(interval) + ' dni temu';
            
            interval = seconds / 3600;
            if (interval > 1) return Math.floor(interval) + ' godzin temu';
            
            interval = seconds / 60;
            if (interval > 1) return Math.floor(interval) + ' minut temu';
            
            return Math.floor(seconds) + ' sekund temu';
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        PNG.init();
    });
    
    // Make PNG object globally available
    window.PNG = PNG;
    
})(jQuery);

/**
 * Additional styles for notifications
 */
const notificationStyles = `
<style>
.png-notification {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 10000;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s;
}

.png-notification.show {
    transform: translateY(0);
    opacity: 1;
}

.png-notification-success {
    background: #4caf50;
    color: white;
}

.png-notification-error {
    background: #e74c3c;
    color: white;
}

.png-notification-info {
    background: #007cba;
    color: white;
}

.png-tooltip {
    position: absolute;
    background: #333;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    z-index: 10000;
    pointer-events: none;
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', notificationStyles);