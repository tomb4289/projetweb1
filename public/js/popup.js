/**
 * Reusable Popup System
 * A flexible and animated popup system for web applications
 */
class PopupSystem {
    constructor() {
        this.activePopup = null;
        this.popupCount = 0;
        this.init();
    }

    init() {
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activePopup) {
                this.closePopup(this.activePopup);
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('popup-overlay') && this.activePopup) {
                this.closePopup(this.activePopup);
            }
        });
    }

    /**
     * Create and show a popup
     * @param {Object} options - Popup configuration
     * @param {string} options.title - Popup title
     * @param {string} options.content - HTML content for the popup body
     * @param {string} options.size - Popup size (small, medium, large, fullscreen)
     * @param {boolean} options.closeable - Whether the popup can be closed
     * @param {Function} options.onClose - Callback when popup is closed
     * @param {Function} options.onOpen - Callback when popup is opened
     * @returns {HTMLElement} The created popup element
     */
    createPopup(options = {}) {
        const {
            title = 'Popup',
            content = '',
            size = 'medium',
            closeable = true,
            onClose = null,
            onOpen = null
        } = options;

        const popupId = `popup-${++this.popupCount}`;
        const popupHTML = `
            <div class="popup-overlay ${size ? `popup-${size}` : ''}" id="${popupId}">
                <div class="popup-content">
                    <div class="popup-header">
                        <h3>${title}</h3>
                        ${closeable ? '<button class="popup-close-btn" aria-label="Fermer">×</button>' : ''}
                    </div>
                    <div class="popup-body">
                        ${content}
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', popupHTML);
        const popup = document.getElementById(popupId);

        if (closeable) {
            const closeBtn = popup.querySelector('.popup-close-btn');
            closeBtn.addEventListener('click', () => this.closePopup(popup));
        }

        popup.onClose = onClose;
        popup.onOpen = onOpen;

        this.showPopup(popup);

        return popup;
    }

    /**
     * Show a popup with animation
     * @param {HTMLElement} popup - The popup element to show
     */
    showPopup(popup) {
        if (this.activePopup) {
            this.closePopup(this.activePopup);
        }

        this.activePopup = popup;
        popup.classList.add('active');
        document.body.style.overflow = 'hidden';

        if (popup.onOpen) {
            popup.onOpen(popup);
        }

        const focusableElements = popup.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusableElements.length > 0) {
            focusableElements[0].focus();
        }
    }

    /**
     * Close a popup with animation
     * @param {HTMLElement} popup - The popup element to close
     */
    closePopup(popup) {
        if (!popup) return;

        popup.classList.remove('active');
        document.body.style.overflow = '';

        if (popup.onClose) {
            popup.onClose(popup);
        }

        setTimeout(() => {
            if (popup.parentNode) {
                popup.parentNode.removeChild(popup);
            }
        }, 300);

        if (this.activePopup === popup) {
            this.activePopup = null;
        }
    }

    /**
     * Close all active popups
     */
    closeAllPopups() {
        const popups = document.querySelectorAll('.popup-overlay.active');
        popups.forEach(popup => this.closePopup(popup));
    }

    /**
     * Update popup content
     * @param {HTMLElement} popup - The popup element to update
     * @param {string} content - New HTML content
     */
    updateContent(popup, content) {
        const body = popup.querySelector('.popup-body');
        if (body) {
            body.innerHTML = content;
        }
    }

    /**
     * Update popup title
     * @param {HTMLElement} popup - The popup element to update
     * @param {string} title - New title
     */
    updateTitle(popup, title) {
        const header = popup.querySelector('.popup-header h3');
        if (header) {
            header.textContent = title;
        }
    }

    /**
     * Show loading state in popup
     * @param {HTMLElement} popup - The popup element
     * @param {string} message - Loading message
     */
    showLoading(popup, message = 'Chargement...') {
        const loadingHTML = `
            <div class="popup-loading">
                <div class="popup-loading-spinner"></div>
                <p>${message}</p>
            </div>
        `;
        this.updateContent(popup, loadingHTML);
    }

    /**
     * Create a confirmation popup
     * @param {Object} options - Confirmation options
     * @param {string} options.title - Popup title
     * @param {string} options.message - Confirmation message
     * @param {string} options.confirmText - Confirm button text
     * @param {string} options.cancelText - Cancel button text
     * @param {Function} options.onConfirm - Callback when confirmed
     * @param {Function} options.onCancel - Callback when cancelled
     * @returns {HTMLElement} The confirmation popup
     */
    confirm(options = {}) {
        const {
            title = 'Confirmation',
            message = 'Êtes-vous sûr ?',
            confirmText = 'Confirmer',
            cancelText = 'Annuler',
            onConfirm = null,
            onCancel = null
        } = options;

        const content = `
            <div class="popup-confirmation">
                <p>${message}</p>
                <div class="popup-actions">
                    <button class="btn btn--secondary cancel-btn">${cancelText}</button>
                    <button class="btn btn--primary confirm-btn">${confirmText}</button>
                </div>
            </div>
        `;

        const popup = this.createPopup({
            title,
            content,
            size: 'small',
            closeable: false
        });

        const confirmBtn = popup.querySelector('.confirm-btn');
        const cancelBtn = popup.querySelector('.cancel-btn');

        confirmBtn.addEventListener('click', () => {
            if (onConfirm) onConfirm();
            this.closePopup(popup);
        });

        cancelBtn.addEventListener('click', () => {
            if (onCancel) onCancel();
            this.closePopup(popup);
        });

        return popup;
    }

    /**
     * Create an alert popup
     * @param {Object} options - Alert options
     * @param {string} options.title - Popup title
     * @param {string} options.message - Alert message
     * @param {string} options.buttonText - Button text
     * @param {Function} options.onClose - Callback when closed
     * @returns {HTMLElement} The alert popup
     */
    alert(options = {}) {
        const {
            title = 'Information',
            message = '',
            buttonText = 'OK',
            onClose = null
        } = options;

        const content = `
            <div class="popup-alert">
                <p>${message}</p>
                <div class="popup-actions">
                    <button class="btn btn--primary close-btn">${buttonText}</button>
                </div>
            </div>
        `;

        const popup = this.createPopup({
            title,
            content,
            size: 'small',
            closeable: false
        });

        const closeBtn = popup.querySelector('.close-btn');
        closeBtn.addEventListener('click', () => {
            if (onClose) onClose();
            this.closePopup(popup);
        });

        return popup;
    }
}

/**
 * Notification System
 * A simple notification system for showing success, error, info, and warning messages
 */
class NotificationSystem {
    constructor() {
        this.notificationCount = 0;
    }

    /**
     * Show a notification
     * @param {Object} options - Notification options
     * @param {string} options.message - Notification message
     * @param {string} options.type - Notification type (success, error, info, warning)
     * @param {number} options.duration - Duration in milliseconds (0 = infinite)
     * @param {Function} options.onClose - Callback when notification is closed
     * @returns {HTMLElement} The notification element
     */
    show(options = {}) {
        const {
            message = '',
            type = 'info',
            duration = 5000,
            onClose = null
        } = options;

        const notificationId = `notification-${++this.notificationCount}`;
        const notificationHTML = `
            <div class="notification ${type}" id="${notificationId}">
                <span class="notification-message">${message}</span>
                <button class="notification-close" aria-label="Fermer">×</button>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', notificationHTML);
        const notification = document.getElementById(notificationId);

        const closeBtn = notification.querySelector('.notification-close');
        closeBtn.addEventListener('click', () => this.close(notification));

        if (duration > 0) {
            setTimeout(() => {
                this.close(notification);
            }, duration);
        }

        notification.onClose = onClose;

        return notification;
    }

    /**
     * Close a notification
     * @param {HTMLElement} notification - The notification element to close
     */
    close(notification) {
        if (!notification) return;

        notification.style.animation = 'notification-slideOutRight 0.3s ease-in';
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);

        if (notification.onClose) {
            notification.onClose(notification);
        }
    }

    /**
     * Show success notification
     * @param {string} message - Success message
     * @param {Object} options - Additional options
     */
    success(message, options = {}) {
        return this.show({ message, type: 'success', ...options });
    }

    /**
     * Show error notification
     * @param {string} message - Error message
     * @param {Object} options - Additional options
     */
    error(message, options = {}) {
        return this.show({ message, type: 'error', ...options });
    }

    /**
     * Show info notification
     * @param {string} message - Info message
     * @param {Object} options - Additional options
     */
    info(message, options = {}) {
        return this.show({ message, type: 'info', ...options });
    }

    /**
     * Show warning notification
     * @param {string} message - Warning message
     * @param {Object} options - Additional options
     */
    warning(message, options = {}) {
        return this.show({ message, type: 'warning', ...options });
    }
}

window.PopupSystem = new PopupSystem();
window.NotificationSystem = new NotificationSystem();

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PopupSystem, NotificationSystem };
}

console.log('=== POPUP.JS COMPLETE ===');
console.log('window.PopupSystem:', window.PopupSystem);
console.log('window.PopupSystem.createPopup:', window.PopupSystem ? window.PopupSystem.createPopup : 'undefined');
