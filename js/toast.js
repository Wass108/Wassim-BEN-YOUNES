// Système de notifications Toast
const Toast = {
    container: null,
    
    // Initialiser le conteneur de toast
    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            document.body.appendChild(this.container);
        }
    },
    
    // Afficher un toast
    show(options) {
        this.init();
        
        const {
            type = 'info',
            title = '',
            message = '',
            duration = 4000,
            closable = true
        } = options;
        
        // Créer l'élément toast
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        // Icônes selon le type
        const icons = {
            success: '✓',
            error: '✕',
            info: 'ℹ',
            warning: '⚠'
        };
        
        // Contenu du toast
        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${title}</div>` : ''}
                ${message ? `<div class="toast-message">${message}</div>` : ''}
            </div>
            ${closable ? `
                <button class="toast-close" aria-label="Fermer">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </button>
            ` : ''}
            ${duration > 0 ? `<div class="toast-progress" style="animation-duration: ${duration}ms;"></div>` : ''}
        `;
        
        // Ajouter au conteneur
        this.container.appendChild(toast);
        
        // Gérer la fermeture
        const closeToast = () => {
            toast.classList.add('removing');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        };
        
        // Bouton de fermeture
        if (closable) {
            const closeBtn = toast.querySelector('.toast-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeToast);
            }
        }
        
        // Fermeture automatique
        if (duration > 0) {
            setTimeout(closeToast, duration);
        }
        
        return toast;
    },
    
    // Méthodes raccourcies
    success(title, message, duration = 4000) {
        return this.show({ type: 'success', title, message, duration });
    },
    
    error(title, message, duration = 5000) {
        return this.show({ type: 'error', title, message, duration });
    },
    
    info(title, message, duration = 4000) {
        return this.show({ type: 'info', title, message, duration });
    },
    
    warning(title, message, duration = 4000) {
        return this.show({ type: 'warning', title, message, duration });
    }
};

// Rendre Toast disponible globalement
window.Toast = Toast;
