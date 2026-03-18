import Plugin from 'src/plugin-system/plugin.class';

export default class AlengoOrderSearchPlugin extends Plugin {

    init() {
        const params = new URLSearchParams(window.location.search);
        this._searchTerm = params.get('search');

        // Nothing to do when no search is active — avoid unnecessary DOM queries.
        if (!this._searchTerm) {
            return;
        }

        this._patchPaginationFormAction();
        this._watchForAjaxUpdates();
    }

    /**
     * Appends the search term to the pagination form's action URL.
     * Since the pagination form uses POST, query params in the action URL
     * are preserved as GET params in the server request, which is what
     * OrderSearchSubscriber reads via $request->query->get('search').
     */
    _patchPaginationFormAction() {
        const form = document.querySelector('.account-orders-pagination-form');

        if (!form) {
            return;
        }

        try {
            const action = form.getAttribute('action') || window.location.pathname;
            const url = new URL(action, window.location.origin);
            url.searchParams.set('search', this._searchTerm);
            form.setAttribute('action', url.pathname + url.search);
        } catch (e) {
            // Ungueltige URL ignorieren
        }
    }

    /**
     * Watches .account-orders-main for AJAX content replacement
     * (triggered by Shopware's FormAjaxSubmit plugin) and re-patches the new form.
     *
     * subtree: false is intentional — only direct child replacements matter here.
     * Deeper mutations would cause redundant re-patches without benefit.
     */
    _watchForAjaxUpdates() {
        const target = document.querySelector('.account-orders-main');

        if (!target) {
            return;
        }

        this._observer = new MutationObserver(() => {
            this._patchPaginationFormAction();
        });

        this._observer.observe(target, {
            childList: true,
            subtree: false,
        });
    }

    disconnect() {
        if (this._observer) {
            this._observer.disconnect();
            this._observer = null;
        }
    }
}
