import { Controller } from '@hotwired/stimulus';

/*
 * Contrôleur du menu burger mobile (assets/controllers/burger_menu_controller.js)
 *
 * Bascule l'affichage du panneau de navigation (.nav-panel) au clic sur le
 * bouton burger, et le referme automatiquement au clic sur un lien ou si
 * l'écran repasse en desktop.
 */
export default class extends Controller {
    static targets = ['panel'];

    connect() {
        this.mediaQuery = window.matchMedia('(min-width: 64rem)');
        this.boundHandleResize = this.handleResize.bind(this);
        this.mediaQuery.addEventListener('change', this.boundHandleResize);
    }

    disconnect() {
        this.mediaQuery.removeEventListener('change', this.boundHandleResize);
    }

    toggle() {
        const isOpen = this.panelTarget.classList.toggle('is-open');
        this.element.querySelector('.burger-toggle').setAttribute(
            'aria-expanded',
            isOpen ? 'true' : 'false'
        );
        this.element.querySelector('.burger-toggle').setAttribute(
            'aria-label',
            isOpen ? 'Fermer le menu' : 'Ouvrir le menu'
        );
    }

    close() {
        this.panelTarget.classList.remove('is-open');
        const toggleButton = this.element.querySelector('.burger-toggle');
        toggleButton.setAttribute('aria-expanded', 'false');
        toggleButton.setAttribute('aria-label', 'Ouvrir le menu');
    }

    handleResize(event) {
        if (event.matches) this.close();
    }
}