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

/**
 * A governorate list runs to a hundred-odd entries, so the region picker in front of it
 * narrows the choices first. The filter itself carries no `name`, so it never submits.
 */
function initializeInformationLookupFilters() {
    document.querySelectorAll('[data-lookup-region-filter]').forEach((regionFilter) => {
        const governorate = document.getElementById(regionFilter.dataset.lookupRegionFilter);

        if (!governorate) {
            return;
        }

        function filterGovernorates() {
            const selectedRegion = regionFilter.value;
            const selectedOption = governorate.selectedOptions[0];

            Array.from(governorate.options).forEach((option, index) => {
                if (index === 0) {
                    return;
                }

                const isVisible = !selectedRegion || option.dataset.region === selectedRegion;
                option.hidden = !isVisible;
                option.disabled = !isVisible;
            });

            if (selectedOption?.disabled) {
                governorate.value = '';
            }
        }

        /** A rejected submission comes back with a governorate picked, so the region follows it. */
        const restoredRegion = governorate.selectedOptions[0]?.dataset.region;

        if (restoredRegion) {
            regionFilter.value = restoredRegion;
        }

        regionFilter.addEventListener('change', filterGovernorates);
        filterGovernorates();
    });
}

/** Deleting a reference record is irreversible, so every such form asks first. */
function initializeInformationAdminConfirmations() {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
}

function initializeInformationAdmin() {
    initializeInformationAdminTabs();
    initializeInformationLookupFilters();
    initializeInformationAdminConfirmations();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeInformationAdmin);
} else {
    initializeInformationAdmin();
}
