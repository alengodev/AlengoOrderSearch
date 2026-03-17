import Plugin from 'src/plugin-system/plugin.class';

export default class AlengoOrderSearchPlugin extends Plugin {

    init() {
        const params = new URLSearchParams(window.location.search);
        this._searchTerm = params.get('search');

        if (!this._searchTerm) {
            return;
        }

        this._applyToLinks();
        this._watchForUpdates();
    }

    _applyToLinks() {
        const links = document.querySelectorAll('.pagination-nav a');

        links.forEach((link) => {
            try {
                const url = new URL(link.getAttribute('href'), window.location.origin);
                url.searchParams.set('search', this._searchTerm);
                link.setAttribute('href', url.pathname + url.search);
            } catch (e) {
                // Ungueltige URL ignorieren
            }
        });
    }

    _watchForUpdates() {
        this._observer = new MutationObserver((mutations) => {
            const hasNewElements = mutations.some((mutation) => {
                return Array.from(mutation.addedNodes).some(
                    (node) => node.nodeType === Node.ELEMENT_NODE
                );
            });

            if (hasNewElements) {
                this._applyToLinks();
            }
        });

        this._observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    onUnmount() {
        if (this._observer) {
            this._observer.disconnect();
            this._observer = null;
        }
    }
}
