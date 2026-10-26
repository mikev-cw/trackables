(() => {
    let timer;
    let pending;
    const root = () => document.querySelector('[data-dashboard]');
    const read = (key) => { try { return localStorage.getItem(key); } catch { return null; } };
    const write = (key, value) => { try { localStorage.setItem(key, value); } catch {} };
    const groupKey = (bar) => `trackables.dashboard.group.${root().dataset.user}.${bar.dataset.dashboardGroupKey}`;

    function initialize() {
        const dashboard = root();
        if (!dashboard) return;
        dashboard.classList.add('is-enhanced');
        const savedView = read('trackables.dashboard.view');
        const view = ['grid', 'list'].includes(savedView) ? savedView : dashboard.dataset.defaultView;
        dashboard.dataset.view = view;
        dashboard.querySelectorAll('[data-dashboard-view]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.dashboardView === view)));
        dashboard.querySelectorAll('[data-dashboard-group-key]').forEach(bar => {
            const target = dashboard.querySelector(bar.dataset.bsTarget);
            const closed = read(groupKey(bar)) === 'closed';
            target.classList.toggle('show', !closed);
            bar.setAttribute('aria-expanded', String(!closed));
        });
        updateCollapseLabel();
    }

    function updateCollapseLabel() {
        const button = root()?.querySelector('[data-dashboard-collapse]');
        if (button) button.textContent = root().querySelector('.dashboard-section .collapse.show') ? 'Collapse groups' : 'Expand groups';
    }

    async function navigate(url, push = true) {
        clearTimeout(timer);
        pending?.abort();
        pending = new AbortController();
        const signal = pending.signal;
        root().setAttribute('aria-busy', 'true');
        const focused = document.activeElement;
        const focusName = focused?.getAttribute('name');
        const selection = focused?.type === 'search' ? focused.selectionStart : null;
        try {
            const response = await fetch(url, { signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok || response.redirected) { window.location.assign(response.url || url); return; }
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const replacement = html.querySelector('[data-dashboard]');
            if (!replacement) { window.location.assign(url); return; }
            if (signal.aborted) return;
            root().replaceWith(replacement);
            if (push) history.pushState({}, '', url);
            initialize();
            if (focusName) {
                const input = root().querySelector(`[name="${focusName}"]`);
                input?.focus({ preventScroll: true });
                if (selection !== null) input?.setSelectionRange(selection, selection);
            } else {
                const heading = root().querySelector('.dashboard-results-heading h3');
                heading.setAttribute('tabindex', '-1');
                heading.focus({ preventScroll: true });
            }
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(url);
        } finally {
            if (!signal.aborted) root()?.removeAttribute('aria-busy');
        }
    }

    function filterUrl() {
        const form = root().querySelector('[data-dashboard-filters]');
        const url = new URL(form.action);
        new FormData(form).forEach((value, key) => { if (value) url.searchParams.set(key, value); });
        return url;
    }
    document.addEventListener('submit', event => {
        if (!event.target.matches('[data-dashboard-filters]')) return;
        event.preventDefault();
        navigate(filterUrl());
    });
    document.addEventListener('input', event => {
        if (!event.target.matches('[data-dashboard-filters] input[name=q]')) return;
        clearTimeout(timer);
        // Cancel stale responses immediately, before the debounce expires.
        pending?.abort();
        timer = setTimeout(() => navigate(filterUrl()), 300);
    });
    document.addEventListener('change', event => {
        if (event.target.matches('[data-dashboard-filters] select')) navigate(filterUrl());
        if (event.target.matches('[data-dashboard-per-page]')) {
            root().querySelector('[name=per_page]').value = event.target.value;
            navigate(filterUrl());
        }
    });
    document.addEventListener('click', event => {
        const link = event.target.closest('[data-dashboard-link], [data-dashboard-pagination] a');
        if (link && !event.metaKey && !event.ctrlKey && !event.shiftKey && !event.altKey && event.button === 0) {
            event.preventDefault();
            navigate(link.href);
        }
        const viewButton = event.target.closest('[data-dashboard-view]');
        if (viewButton) { write('trackables.dashboard.view', viewButton.dataset.dashboardView); initialize(); }
        if (event.target.closest('[data-dashboard-collapse]')) {
            const close = Boolean(root().querySelector('.dashboard-section .collapse.show'));
            root().querySelectorAll('[data-dashboard-group-key]').forEach(bar => {
                const target = root().querySelector(bar.dataset.bsTarget);
                if (target.classList.contains('show') === close && !target.classList.contains('collapsing')) bar.click();
            });
        }
    });
    ['shown.bs.collapse', 'hidden.bs.collapse'].forEach(type => document.addEventListener(type, event => {
        const bar = root()?.querySelector(`[aria-controls="${event.target.id}"]`);
        if (!bar) return;
        write(groupKey(bar), type === 'shown.bs.collapse' ? 'open' : 'closed');
        updateCollapseLabel();
    }));
    window.addEventListener('popstate', () => { if (root()) navigate(window.location.href, false); });
    initialize();
})();
