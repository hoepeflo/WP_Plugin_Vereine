(function () {
    'use strict';

    if (typeof ksvVereinssuche === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-ksv-vereinssuche-form]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const pageUrl = ksvVereinssuche.vereinePageUrl;
            if (!pageUrl) {
                e.preventDefault();
                return;
            }

            const input = form.querySelector('input[name="ksv_search"]');
            const query = input ? input.value.trim() : '';

            if (!query) {
                e.preventDefault();
                window.location.href = pageUrl;
                return;
            }

            e.preventDefault();

            const url = new URL(pageUrl, window.location.origin);
            url.searchParams.set(ksvVereinssuche.searchParam || 'ksv_search', query);
            window.location.href = url.toString();
        });
    });
})();
