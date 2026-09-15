(function () {
    'use strict';

    var CinderwellHydration = {
        observer: null,
        disabled: false,
        rootMargin: '200px',
        safeMode: true,
        seoBypass: true,
        stats: { hydrated: 0, deferred: 0, errors: 0 },

        init: function () {
            if (typeof cinderwellHydration === 'undefined') return;

            this.rootMargin = cinderwellHydration.rootMargin || '200px';
            this.safeMode = cinderwellHydration.safeMode !== false;
            this.seoBypass = cinderwellHydration.seoBypass !== false;

            if (this.seoBypass && this.isBot()) {
                this.hydrateAll();
                return;
            }

            if (!('IntersectionObserver' in window)) {
                this.hydrateAll();
                return;
            }

            if (this.safeMode) {
                this.setupSafeMode();
            }

            this.observer = new IntersectionObserver(function (entries) {
                if (CinderwellHydration.disabled) {
                    CinderwellHydration.observer.disconnect();
                    CinderwellHydration.hydrateAll();
                    return;
                }

                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        CinderwellHydration.hydrate(entry.target);
                        CinderwellHydration.observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: this.rootMargin });

            var elements = document.querySelectorAll('[data-cw-hydrate="true"]');
            for (var i = 0; i < elements.length; i++) {
                elements[i].classList.add('cinderwell-hydrating');
                this.observer.observe(elements[i]);
                this.stats.deferred++;
            }

            this.setupMutationObserver();
        },

        hydrate: function (element) {
            try {
                element.classList.remove('cinderwell-hydrating');
                element.classList.add('is-hydrated');

                var event = new CustomEvent('cinderwell:hydrated', {
                    bubbles: true,
                    detail: { element: element }
                });
                element.dispatchEvent(event);

                this.stats.hydrated++;
            } catch (e) {
                this.stats.errors++;
                this.handleError(e, element);
            }
        },

        hydrateAll: function () {
            this.disabled = true;
            if (this.observer) this.observer.disconnect();

            var elements = document.querySelectorAll('[data-cw-hydrate="true"]');
            for (var i = 0; i < elements.length; i++) {
                this.hydrate(elements[i]);
            }
        },

        isBot: function () {
            if (typeof cinderwellHydration === 'undefined' || !cinderwellHydration.botUserAgents) {
                return false;
            }
            var ua = navigator.userAgent;
            return cinderwellHydration.botUserAgents.some(function (bot) {
                return ua.indexOf(bot) !== -1;
            });
        },

        setupSafeMode: function () {
            window.addEventListener('error', function () {
                if (CinderwellHydration.stats.hydrated > 0) {
                    CinderwellHydration.disabled = true;
                    CinderwellHydration.hydrateAll();
                }
            });

            window.addEventListener('unhandledrejection', function () {
                if (CinderwellHydration.stats.hydrated > 0) {
                    CinderwellHydration.disabled = true;
                    CinderwellHydration.hydrateAll();
                }
            });
        },

        setupMutationObserver: function () {
            var mo = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) return;
                        if (node.matches && node.matches('[data-cw-hydrate="true"]')) {
                            node.classList.add('cinderwell-hydrating');
                            CinderwellHydration.observer.observe(node);
                            CinderwellHydration.stats.deferred++;
                        }
                        var children = node.querySelectorAll ? node.querySelectorAll('[data-cw-hydrate="true"]') : [];
                        for (var i = 0; i < children.length; i++) {
                            children[i].classList.add('cinderwell-hydrating');
                            CinderwellHydration.observer.observe(children[i]);
                            CinderwellHydration.stats.deferred++;
                        }
                    });
                });
            });

            mo.observe(document.body, { childList: true, subtree: true });
        },

        handleError: function (error, element) {
            try {
                element.classList.remove('cinderwell-hydrating');
                element.classList.add('is-hydrated');
                element.classList.add('cinderwell-hydration-error');
            } catch (e) {
                // Ignore
            }
        },

        force: function (element) {
            this.hydrate(element);
            if (this.observer) this.observer.unobserve(element);
        },

        forceAll: function () {
            this.hydrateAll();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { CinderwellHydration.init(); });
    } else {
        CinderwellHydration.init();
    }

    window.CinderwellHydration = CinderwellHydration;
})();
