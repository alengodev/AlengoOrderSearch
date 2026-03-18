import Plugin from 'src/plugin-system/plugin.class';

export default class AlengoOrderSearchPlugin extends Plugin {

    init() {
        const params = new URLSearchParams(window.location.search);
        this._searchTerm = params.get('search');
        this._dateFrom   = params.get('dateFrom');
        this._dateTo     = params.get('dateTo');

        // Nothing to do when no filter is active — avoid unnecessary DOM queries.
        if (!this._searchTerm && !this._dateFrom && !this._dateTo) {
            return;
        }

        this._patchPaginationFormAction();
        this._watchForAjaxUpdates();
    }

    /**
     * Appends all active filter parameters to the pagination form's action URL.
     *
     * Since the pagination form uses POST, query params in the action URL are
     * preserved as GET params in the server request, which is what
     * OrderSearchSubscriber reads via $request->query->get(…).
     *
     * Parameters that are not set (null) are omitted from the URL so the server
     * treats them as absent rather than as empty strings.
     */
    _patchPaginationFormAction() {
        const form = document.querySelector('.account-orders-pagination-form');

        if (!form) {
            return;
        }

        try {
            const action = form.getAttribute('action') || window.location.pathname;
            const url = new URL(action, window.location.origin);

            if (this._searchTerm) {
                url.searchParams.set('search', this._searchTerm);
            } else {
                url.searchParams.delete('search');
            }

            if (this._dateFrom) {
                url.searchParams.set('dateFrom', this._dateFrom);
            } else {
                url.searchParams.delete('dateFrom');
            }

            if (this._dateTo) {
                url.searchParams.set('dateTo', this._dateTo);
            } else {
                url.searchParams.delete('dateTo');
            }

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
