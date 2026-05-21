/**
 * RideIt - Sistema de Modales Profesional
 * Uso:
 *   RideIt.confirm({ title, message, onConfirm, type })
 *   RideIt.alert({ title, message, type })
 *   RideIt.prompt({ title, message, placeholder, onConfirm, type })
 *   RideIt.toast(message, type)
 */
const RideIt = {
    _overlay: null,

    _getOverlay() {
        if (!this._overlay) {
            this._overlay = document.createElement('div');
            this._overlay.className = 'modal-overlay';
            this._overlay.addEventListener('click', (e) => {
                if (e.target === this._overlay) this.close();
            });
            document.body.appendChild(this._overlay);
        }
        return this._overlay;
    },

    _icons: {
        success: '&#10003;',
        danger: '&#9888;',
        warning: '&#9888;',
        info: '&#8505;'
    },

    close() {
        const overlay = this._getOverlay();
        overlay.classList.remove('active');
        overlay.innerHTML = '';
    },

    /**
     * Modal de confirmacion
     */
    confirm({ title = 'Confirmar', message = '¿Estás seguro?', confirmText = 'Confirmar', cancelText = 'Cancelar', onConfirm = null, onCancel = null, type = 'warning', confirmClass = '' } = {}) {
        const overlay = this._getOverlay();
        const btnClass = confirmClass || (type === 'danger' ? 'btn-danger' : 'btn-confirm');

        overlay.innerHTML = `
            <div class="modal-box">
                <button class="modal-close" onclick="RideIt.close()">&times;</button>
                <div class="modal-icon ${type}">${this._icons[type] || this._icons.info}</div>
                <h3 class="modal-title">${title}</h3>
                <div class="modal-body">${message}</div>
                <div class="modal-actions">
                    <button class="modal-btn btn-cancel" id="modal-cancel">${cancelText}</button>
                    <button class="modal-btn ${btnClass}" id="modal-confirm">${confirmText}</button>
                </div>
            </div>
        `;
        overlay.classList.add('active');

        document.getElementById('modal-cancel').onclick = () => {
            this.close();
            if (onCancel) onCancel();
        };
        document.getElementById('modal-confirm').onclick = () => {
            this.close();
            if (onConfirm) onConfirm();
        };

        // ESC para cerrar
        const escHandler = (e) => {
            if (e.key === 'Escape') { this.close(); document.removeEventListener('keydown', escHandler); }
        };
        document.addEventListener('keydown', escHandler);
    },

    /**
     * Modal de alerta
     */
    alert({ title = 'Aviso', message = '', type = 'info', buttonText = 'Aceptar', onClose = null } = {}) {
        const overlay = this._getOverlay();
        overlay.innerHTML = `
            <div class="modal-box">
                <button class="modal-close" onclick="RideIt.close()">&times;</button>
                <div class="modal-icon ${type}">${this._icons[type] || this._icons.info}</div>
                <h3 class="modal-title">${title}</h3>
                <div class="modal-body">${message}</div>
                <div class="modal-actions">
                    <button class="modal-btn btn-confirm" id="modal-ok">${buttonText}</button>
                </div>
            </div>
        `;
        overlay.classList.add('active');
        document.getElementById('modal-ok').onclick = () => {
            this.close();
            if (onClose) onClose();
        };
    },

    /**
     * Modal con input (prompt)
     */
    prompt({ title = 'Ingresa un valor', message = '', placeholder = '', inputType = 'text', onConfirm = null, confirmText = 'Confirmar', cancelText = 'Cancelar', type = 'info' } = {}) {
        const overlay = this._getOverlay();
        overlay.innerHTML = `
            <div class="modal-box">
                <button class="modal-close" onclick="RideIt.close()">&times;</button>
                <div class="modal-icon ${type}">${this._icons[type] || this._icons.info}</div>
                <h3 class="modal-title">${title}</h3>
                <div class="modal-body">${message}</div>
                <input type="${inputType}" class="modal-input" id="modal-input" placeholder="${placeholder}" autocomplete="off">
                <div class="modal-actions">
                    <button class="modal-btn btn-cancel" id="modal-cancel">${cancelText}</button>
                    <button class="modal-btn btn-confirm" id="modal-confirm">${confirmText}</button>
                </div>
            </div>
        `;
        overlay.classList.add('active');
        document.getElementById('modal-input').focus();

        document.getElementById('modal-cancel').onclick = () => this.close();
        document.getElementById('modal-confirm').onclick = () => {
            const val = document.getElementById('modal-input').value;
            if (onConfirm) onConfirm(val);
        };
        document.getElementById('modal-input').addEventListener('keydown', (e) => {
            if (e.key === 'Enter') document.getElementById('modal-confirm').click();
        });
    },

    /**
     * Toast notification
     */
    toast(message, type = 'info') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }
        const icons = { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' };
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<span>${icons[type] || ''}</span> ${message}`;
        container.appendChild(toast);
        setTimeout(() => { toast.remove(); }, 4000);
    },

    /**
     * Confirm form submission
     */
    confirmForm(formId, { title, message, type = 'warning', confirmText = 'Confirmar' } = {}) {
        const form = document.getElementById(formId);
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            RideIt.confirm({
                title: title || 'Confirmar accion',
                message: message || '¿Estas seguro de continuar?',
                type,
                confirmText,
                onConfirm: () => form.submit()
            });
        });
    }
};
