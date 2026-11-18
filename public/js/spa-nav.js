/**
 * Doña Ross - Navegación Fluida SPA Ligera
 * Proporciona transiciones suaves entre páginas sin parpadeos ni recargas completas.
 */
(function () {
    'use strict';

    // Barra de progreso superior
    let progressBar = null;

    function getProgressBar() {
        if (!progressBar) {
            progressBar = document.createElement('div');
            progressBar.id = 'spa-progress-bar';
            progressBar.style.cssText = [
                'position: fixed',
                'top: 0',
                'left: 0',
                'height: 3px',
                'width: 0%',
                'background: linear-gradient(90deg, #7A1113 0%, #FF5722 50%, #FFB300 100%)',
                'z-index: 99999',
                'opacity: 0',
                'pointer-events: none',
                'transition: width 0.25s ease, opacity 0.2s ease',
                'box-shadow: 0 0 10px rgba(255, 87, 34, 0.6)'
            ].join(';');
            document.body.appendChild(progressBar);
        }
        return progressBar;
    }

    function startProgress() {
        const bar = getProgressBar();
        bar.style.transition = 'width 0.25s ease, opacity 0.15s ease';
        bar.style.opacity = '1';
        bar.style.width = '70%';
    }

    function finishProgress() {
        const bar = getProgressBar();
        bar.style.width = '100%';
        setTimeout(function () {
            bar.style.opacity = '0';
            setTimeout(function () {
                bar.style.width = '0%';
            }, 250);
        }, 150);
    }

    function isEligibleLink(anchor, event) {
        if (!anchor || anchor.tagName !== 'A') return false;
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return false;
        if (anchor.target && anchor.target !== '_self') return false;
        if (anchor.hasAttribute('download')) return false;
        if (anchor.getAttribute('data-no-spa') === 'true') return false;
        if (anchor.getAttribute('data-bs-toggle')) return false;

        const href = anchor.getAttribute('href');
        if (!href) return false;
        if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;

        try {
            const url = new URL(anchor.href, window.location.origin);
            if (url.origin !== window.location.origin) return false;
            // No interceptar si es la misma URL exacta (con mismo hash o sin cambio)
            if (url.href === window.location.href) return false;
            // No interceptar rutas de logout
            if (url.pathname.includes('logout')) return false;
            return true;
        } catch (e) {
            return false;
        }
    }

    function executeScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(function (oldScript) {
            const newScript = document.createElement('script');
            Array.from(oldScript.attributes).forEach(function (attr) {
                newScript.setAttribute(attr.name, attr.value);
            });
            newScript.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    function updateActiveNavigation(targetUrl) {
        try {
            const targetPath = new URL(targetUrl, window.location.origin).pathname;
            document.querySelectorAll('.admin-sidebar .nav-link, .navbar-nav .nav-link').forEach(function (link) {
                const linkHref = link.getAttribute('href');
                if (!linkHref) return;
                try {
                    const linkPath = new URL(linkHref, window.location.origin).pathname;
                    const isActive = (linkPath === targetPath) || 
                        (targetPath.startsWith(linkPath) && linkPath !== '/' && linkPath !== '/admin');
                    link.classList.toggle('active', isActive);
                } catch (e) {}
            });
        } catch (e) {}
    }

    function closeOffcanvasIfOpen() {
        const offcanvasEl = document.querySelector('.offcanvas.show');
        if (offcanvasEl && window.bootstrap && window.bootstrap.Offcanvas) {
            const instance = window.bootstrap.Offcanvas.getInstance(offcanvasEl);
            if (instance) instance.hide();
        }
    }

    async function navigateTo(url, pushState = true) {
        const currentContainer = document.getElementById('spa-container');
        if (!currentContainer) {
            window.location.href = url;
            return;
        }

        startProgress();
        currentContainer.style.transition = 'opacity 0.12s ease';
        currentContainer.style.opacity = '0.25';

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-SPA-Request': 'true'
                }
            });

            if (response.redirected) {
                window.location.href = response.url;
                return;
            }

            if (!response.ok) {
                // Si retorna 404/403/etc., recarga o procesa respuesta
                if (response.status === 404 || response.status === 403) {
                    const errorHtml = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(errorHtml, 'text/html');
                    const newContainer = doc.getElementById('spa-container');
                    if (newContainer) {
                        currentContainer.innerHTML = newContainer.innerHTML;
                        document.title = doc.title;
                        if (pushState) window.history.pushState({ spa: true, url: url }, '', url);
                        finishProgress();
                        currentContainer.style.opacity = '1';
                        return;
                    }
                }
                window.location.href = url;
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContainer = doc.getElementById('spa-container');

            if (!newContainer) {
                // Layout diferente o estructura inesperada: navegación normal
                window.location.href = url;
                return;
            }

            // Actualizar contenido
            currentContainer.innerHTML = newContainer.innerHTML;
            document.title = doc.title;

            // Actualizar sidebar si existe
            const newSidebar = doc.getElementById('spa-sidebar');
            const currentSidebar = document.getElementById('spa-sidebar');
            if (newSidebar && currentSidebar) {
                currentSidebar.innerHTML = newSidebar.innerHTML;
            }

            if (pushState) {
                window.history.pushState({ spa: true, url: url }, '', url);
            }

            updateActiveNavigation(url);
            closeOffcanvasIfOpen();

            window.scrollTo({ top: 0, behavior: 'smooth' });

            // Ejecutar scripts dinámicos
            executeScripts(currentContainer);

            // Disparar evento global
            window.dispatchEvent(new CustomEvent('spa:navigated', { detail: { url: url } }));

            finishProgress();
            setTimeout(function () {
                currentContainer.style.opacity = '1';
            }, 40);

        } catch (error) {
            console.warn('[SPA] Error en navegación fluida, fallback estándar:', error);
            window.location.href = url;
        }
    }

    // Interceptar clics en enlaces
    document.addEventListener('click', function (e) {
        const anchor = e.target.closest('a');
        if (anchor && isEligibleLink(anchor, e)) {
            e.preventDefault();
            navigateTo(anchor.href, true);
        }
    });

    // Soporte para botones Atrás / Adelante del navegador
    window.addEventListener('popstate', function () {
        navigateTo(window.location.href, false);
    });

    window.SpaNavigation = {
        visit: navigateTo
    };
})();
