function initializeInformationAdminTabs() {
    const container = document.querySelector('[data-info-tabs]');

    if (!container) {
        return;
    }

    const tabs = Array.from(container.querySelectorAll('[data-info-tab]'));
    const panels = Array.from(container.querySelectorAll('[data-info-tabpanel]'));

    if (tabs.length === 0 || panels.length === 0) {
        return;
    }

    container.classList.add('is-enhanced');

    function activate(key, { focusTab = false } = {}) {
        tabs.forEach((tab) => {
            const isActive = tab.dataset.infoTab === key;
            tab.classList.toggle('is-active', isActive);
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.tabIndex = isActive ? 0 : -1;

            if (isActive && focusTab) {
                tab.focus();
            }
        });

        panels.forEach((panel) => {
            panel.hidden = panel.dataset.infoTabpanel !== key;
        });

        window.history.replaceState(null, '', `#${key}`);
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => activate(tab.dataset.infoTab));

        tab.addEventListener('keydown', (event) => {
            const orientationKeys = ['ArrowRight', 'ArrowLeft', 'Home', 'End'];

            if (!orientationKeys.includes(event.key)) {
                return;
            }

            event.preventDefault();
            const currentIndex = tabs.indexOf(tab);
            let nextIndex = currentIndex;

            if (event.key === 'Home') {
                nextIndex = 0;
            } else if (event.key === 'End') {
                nextIndex = tabs.length - 1;
            } else {
                /** The tablist is RTL, so ArrowLeft advances and ArrowRight goes back. */
                const step = event.key === 'ArrowLeft' ? 1 : -1;
                nextIndex = (currentIndex + step + tabs.length) % tabs.length;
            }

            activate(tabs[nextIndex].dataset.infoTab, { focusTab: true });
        });
    });

    const requestedKey = window.location.hash.replace('#', '');
    const initialTab = tabs.find((tab) => tab.dataset.infoTab === requestedKey) ?? tabs[0];
    activate(initialTab.dataset.infoTab);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeInformationAdminTabs);
} else {
    initializeInformationAdminTabs();
}
