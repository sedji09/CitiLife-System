/**
 * CitiLife Global Security & CSRF Interceptor (Phase 3 Security)
 * Automatically attaches CSRF protection tokens to all Fetch requests,
 * XMLHttpRequests, and HTML Form submissions without breaking existing code.
 */
(function () {
    'use strict';

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) {
            return meta.content;
        }
        // Fallback: check window.__APP__ or hidden input if available
        if (window.__APP__ && window.__APP__.csrfToken) {
            return window.__APP__.csrfToken;
        }
        const input = document.querySelector('input[name="_csrf_token"]');
        if (input && input.value) {
            return input.value;
        }
        return '';
    }

    // 1. Hook window.fetch
    if (window.fetch) {
        const originalFetch = window.fetch;
        window.fetch = function (resource, init) {
            init = init || {};
            const method = (init.method || 'GET').toUpperCase();
            
            // Attach CSRF Token to headers for all AJAX/Fetch requests
            const token = getCsrfToken();
            if (token) {
                if (!init.headers) {
                    init.headers = {};
                }

                if (init.headers instanceof Headers) {
                    if (!init.headers.has('X-CSRF-TOKEN')) {
                        init.headers.set('X-CSRF-TOKEN', token);
                    }
                } else if (Array.isArray(init.headers)) {
                    const hasHeader = init.headers.some(function (h) {
                        return (h[0] || '').toLowerCase() === 'x-csrf-token';
                    });
                    if (!hasHeader) {
                        init.headers.push(['X-CSRF-TOKEN', token]);
                    }
                } else if (typeof init.headers === 'object') {
                    if (!init.headers['X-CSRF-TOKEN'] && !init.headers['x-csrf-token']) {
                        init.headers['X-CSRF-TOKEN'] = token;
                    }
                }

                // For modifying methods (POST, PUT, DELETE, PATCH), also inject into FormData
                if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
                    if (init.body instanceof FormData) {
                        if (!init.body.has('_csrf_token')) {
                            init.body.append('_csrf_token', token);
                        }
                    }
                }
            }

            return originalFetch.call(this, resource, init).catch(function (error) {
                if (!navigator.onLine && window.CitiLifeOfflineTracker) {
                    window.CitiLifeOfflineTracker.showOffline();
                }
                throw error;
            });
        };
    }

    // 2. Hook XMLHttpRequest (for standard AJAX & jQuery)
    if (window.XMLHttpRequest) {
        const originalOpen = XMLHttpRequest.prototype.open;
        const originalSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
            this._csrfMethod = (method || 'GET').toUpperCase();
            return originalOpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function (body) {
            this.addEventListener('error', function () {
                if (!navigator.onLine && window.CitiLifeOfflineTracker) {
                    window.CitiLifeOfflineTracker.showOffline();
                }
            });

            if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(this._csrfMethod)) {
                const token = getCsrfToken();
                if (token) {
                    try {
                        this.setRequestHeader('X-CSRF-TOKEN', token);
                    } catch (e) { }

                    // If sending FormData without token, append it
                    if (body instanceof FormData && !body.has('_csrf_token')) {
                        body.append('_csrf_token', token);
                    }
                }
            }
            return originalSend.apply(this, arguments);
        };
    }

    // 3. Hook standard HTML Form submissions and programmatic form.submit()
    function ensureCsrfInput(form) {
        if (!form || !form.tagName || form.tagName.toLowerCase() !== 'form') return;
        const method = (form.method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            const token = getCsrfToken();
            if (token && !form.querySelector('input[name="_csrf_token"]')) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = '_csrf_token';
                hiddenInput.value = token;
                form.appendChild(hiddenInput);
            }
        }
    }

    document.addEventListener('submit', function (e) {
        ensureCsrfInput(e.target);
    }, true);

    if (typeof HTMLFormElement !== 'undefined' && HTMLFormElement.prototype.submit) {
        const originalFormSubmit = HTMLFormElement.prototype.submit;
        HTMLFormElement.prototype.submit = function () {
            ensureCsrfInput(this);
            return originalFormSubmit.apply(this, arguments);
        };
    }

})();

