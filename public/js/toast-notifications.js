/**
 * Service de Notification Toast pour AutiCare
 * Affiche des notifications élégantes dans le coin supérieur droit
 */

class ToastNotificationManager {
    constructor() {
        this.container = null;
        this.notifications = [];
        this.initContainer();
    }

    /**
     * Initialise le conteneur des notifications
     */
    initContainer() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-notifications-container';
            this.container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            `;
            document.body.appendChild(this.container);
        }
    }

    /**
     * Affiche une notification toast
     * @param {Object} options - Options de la notification
     *   - type: 'success', 'info', 'warning', 'error'
     *   - icon: Emoji ou texte d'icône
     *   - title: Titre de la notification
     *   - message: Message principal
     *   - details: Objet {key: value} ou array de détails
     *   - duration: Temps d'affichage en ms (0 = permanent)
     *   - dismissible: true/false permettre la fermeture manuelle
     */
    show(options = {}) {
        const {
            type = 'info',
            icon = 'ℹ️',
            title = 'Notification',
            message = '',
            details = {},
            duration = 5000,
            dismissible = true,
        } = options;

        const toastElement = this.createToastElement({
            type,
            icon,
            title,
            message,
            details,
            dismissible,
        });

        this.container.appendChild(toastElement);
        this.notifications.push(toastElement);

        // Animation d'entrée
        setTimeout(() => {
            toastElement.classList.add('toast-show');
        }, 10);

        // Auto-fermeture
        if (duration > 0) {
            setTimeout(() => {
                this.removeNotification(toastElement);
            }, duration);
        }

        return toastElement;
    }

    /**
     * Crée un élément toast
     */
    createToastElement(options) {
        const { type, icon, title, message, details, dismissible } = options;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.setAttribute('role', 'alert');

        const colors = {
            success: { bg: '#d4edda', border: '#28a745', text: '#155724' },
            info: { bg: '#d1ecf1', border: '#17a2b8', text: '#0c5460' },
            warning: { bg: '#fff3cd', border: '#ffc107', text: '#856404' },
            error: { bg: '#f8d7da', border: '#dc3545', text: '#721c24' },
        };

        const color = colors[type] || colors.info;

        toast.style.cssText = `
            background-color: ${color.bg};
            border-left: 4px solid ${color.border};
            border-radius: 4px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            animation: slideInRight 0.3s ease-out;
            opacity: 0;
            transform: translateX(400px);
            transition: all 0.3s ease-out;
            color: ${color.text};
        `;

        // Contenu principal
        const header = document.createElement('div');
        header.style.cssText = `
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        `;

        const titleElement = document.createElement('div');
        titleElement.style.cssText = `
            display: flex;
            align-items: center;
            font-weight: 600;
            font-size: 14px;
            flex: 1;
        `;
        titleElement.innerHTML = `<span style="font-size: 18px; margin-right: 8px;">${this.escapeHtml(icon)}</span>${this.escapeHtml(title)}`;

        header.appendChild(titleElement);

        // Bouton de fermeture
        if (dismissible) {
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '×';
            closeBtn.style.cssText = `
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                padding: 0;
                margin: -4px -4px 0 8px;
                color: inherit;
                opacity: 0.7;
                transition: opacity 0.2s;
            `;
            closeBtn.onmouseover = () => (closeBtn.style.opacity = '1');
            closeBtn.onmouseout = () => (closeBtn.style.opacity = '0.7');
            closeBtn.onclick = () => this.removeNotification(toast);
            header.appendChild(closeBtn);
        }

        toast.appendChild(header);

        // Message
        if (message) {
            const messageElement = document.createElement('p');
            messageElement.style.cssText = `
                margin: 8px 0;
                font-size: 13px;
                line-height: 1.4;
            `;
            messageElement.textContent = message;
            toast.appendChild(messageElement);
        }

        // Détails
        if (Object.keys(details).length > 0) {
            const detailsElement = document.createElement('div');
            detailsElement.style.cssText = `
                margin-top: 8px;
                font-size: 12px;
                border-top: 1px solid rgba(0, 0, 0, 0.1);
                padding-top: 8px;
            `;

            if (Array.isArray(details)) {
                details.forEach(detail => {
                    const line = document.createElement('div');
                    line.style.cssText = 'margin: 4px 0;';
                    line.textContent = detail;
                    detailsElement.appendChild(line);
                });
            } else if (typeof details === 'object') {
                Object.entries(details).forEach(([key, value]) => {
                    if (value) {
                        const line = document.createElement('div');
                        line.style.cssText = 'margin: 4px 0;';
                        line.textContent = value;
                        detailsElement.appendChild(line);
                    }
                });
            }

            toast.appendChild(detailsElement);
        }

        // Ajouter les styles d'animation au document si non présents
        this.addAnimations();

        return toast;
    }

    /**
     * Supprime une notification
     */
    removeNotification(element) {
        element.style.opacity = '0';
        element.style.transform = 'translateX(400px)';

        setTimeout(() => {
            element.remove();
            this.notifications = this.notifications.filter(n => n !== element);
        }, 300);
    }

    /**
     * Ajoute les animations CSS
     */
    addAnimations() {
        if (!document.getElementById('toast-animations')) {
            const style = document.createElement('style');
            style.id = 'toast-animations';
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        opacity: 0;
                        transform: translateX(400px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }

                .toast-show {
                    animation: slideInRight 0.3s ease-out !important;
                    opacity: 1 !important;
                    transform: translateX(0) !important;
                }

                /* Responsive design */
                @media (max-width: 600px) {
                    #toast-notifications-container {
                        left: 10px !important;
                        right: 10px !important;
                        max-width: calc(100% - 20px) !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }

    /**
     * Échappe le HTML
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Affiche une notification de succès
     */
    success(title, message, details = {}) {
        return this.show({
            type: 'success',
            icon: '✅',
            title,
            message,
            details,
            duration: 5000,
        });
    }

    /**
     * Affiche une notification d'info
     */
    info(title, message, details = {}) {
        return this.show({
            type: 'info',
            icon: 'ℹ️',
            title,
            message,
            details,
            duration: 5000,
        });
    }

    /**
     * Affiche une notification d'avertissement
     */
    warning(title, message, details = {}) {
        return this.show({
            type: 'warning',
            icon: '⚠️',
            title,
            message,
            details,
            duration: 5000,
        });
    }

    /**
     * Affiche une notification d'erreur
     */
    error(title, message, details = {}) {
        return this.show({
            type: 'error',
            icon: '❌',
            title,
            message,
            details,
            duration: 6000,
        });
    }

    /**
     * Affiche une notification JSON
     */
    showFromJSON(jsonData) {
        try {
            const data = typeof jsonData === 'string' ? JSON.parse(jsonData) : jsonData;
            return this.show(data);
        } catch (error) {
            console.error('Erreur lors du parsing JSON de notification:', error);
            return null;
        }
    }

    /**
     * Nettoie toutes les notifications
     */
    clear() {
        this.notifications.forEach(notification => {
            this.removeNotification(notification);
        });
    }
}

// Initialiser le gestionnaire global
const toastNotifier = new ToastNotificationManager();

// Ajouter des raccourcis globaux
window.notify = {
    success: (title, message, details) => toastNotifier.success(title, message, details),
    info: (title, message, details) => toastNotifier.info(title, message, details),
    warning: (title, message, details) => toastNotifier.warning(title, message, details),
    error: (title, message, details) => toastNotifier.error(title, message, details),
    show: (options) => toastNotifier.show(options),
    fromJSON: (json) => toastNotifier.showFromJSON(json),
    clear: () => toastNotifier.clear(),
};
