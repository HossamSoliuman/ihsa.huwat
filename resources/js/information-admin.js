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
                governorate.dispatchEvent(new Event('change'));
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

function initializeInformationPortFilters() {
    document.querySelectorAll('[data-lookup-governorate-filter]').forEach((governorateFilter) => {
        const port = document.getElementById(governorateFilter.dataset.lookupGovernorateFilter);

        if (!port) {
            return;
        }

        function filterPorts() {
            const selectedGovernorate = governorateFilter.value;
            const selectedOption = port.selectedOptions[0];

            Array.from(port.options).forEach((option, index) => {
                if (index === 0) {
                    return;
                }

                const isVisible = !selectedGovernorate || option.dataset.governorate === selectedGovernorate;
                option.hidden = !isVisible;
                option.disabled = !isVisible;
            });

            if (selectedOption?.disabled) {
                port.value = '';
            }
        }

        const restoredGovernorate = port.selectedOptions[0]?.dataset.governorate;

        if (restoredGovernorate && !governorateFilter.value) {
            governorateFilter.value = restoredGovernorate;
            governorateFilter.dispatchEvent(new Event('change'));
        }

        governorateFilter.addEventListener('change', filterPorts);
        filterPorts();
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

/**
 * A دلال is either a فرد or a منشأة, and each branch asks its own questions. Both fieldsets
 * are in the markup so the form still works with no script — the server clears whichever
 * branch was not chosen — and this only narrows the page to the branch in play.
 */
function initializeInformationBrokerBranches() {
    document.querySelectorAll('[data-broker-form]').forEach((form) => {
        const entityType = form.querySelector('[data-broker-entity-type]');
        const branches = Array.from(form.querySelectorAll('[data-broker-branch]'));

        if (!entityType || branches.length === 0) {
            return;
        }

        function showSelectedBranch() {
            branches.forEach((branch) => {
                branch.hidden = branch.dataset.brokerBranch !== entityType.value;
            });
        }

        entityType.addEventListener('change', showSelectedBranch);
        showSelectedBranch();
    });
}

/**
 * A دلال works out of a دكة of his own market, so the دكة list is narrowed to the market in
 * play. With no script every دكة stays selectable and the server is the authority — it
 * rejects a دكة that does not belong to the market submitted.
 */
function initializeInformationBrokerStalls() {
    document.querySelectorAll('[data-broker-form]').forEach((form) => {
        const market = form.querySelector('[data-broker-market]');
        const stall = form.querySelector('[data-broker-stall]');

        if (!market || !stall) {
            return;
        }

        function filterStalls() {
            const selectedMarket = market.value;
            const selectedOption = stall.selectedOptions[0];

            Array.from(stall.options).forEach((option, index) => {
                if (index === 0) {
                    return;
                }

                const isVisible = option.dataset.market === selectedMarket;
                option.hidden = !isVisible;
                option.disabled = !isVisible;
            });

            if (selectedOption?.disabled) {
                stall.value = '';
            }
        }

        market.addEventListener('change', filterStalls);
        filterStalls();
    });
}

/**
 * الموظفون of a دلال are counted per وظيفة and جنسية, so the block is a list of rows rather
 * than a fixed set of fields. The rows are read by position, so dropping one renumbers the
 * names the server sees. The rows already in the markup submit with no script.
 */
function initializeInformationBrokerEmployees() {
    document.querySelectorAll('[data-broker-form]').forEach((form) => {
        const list = form.querySelector('[data-broker-employee-list]');
        const template = form.querySelector('[data-broker-employee-template]');

        if (!list || !template) {
            return;
        }

        function renumberRows() {
            Array.from(list.querySelectorAll('[data-broker-employee-row]')).forEach((row, index) => {
                row.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/employees\[\d+]/, `employees[${index}]`);
                });

                row.querySelectorAll('[id]').forEach((field) => {
                    field.id = field.id.replace(/_employee_\d+_/, `_employee_${index}_`);
                });

                row.querySelectorAll('[for]').forEach((label) => {
                    label.htmlFor = label.htmlFor.replace(/_employee_\d+_/, `_employee_${index}_`);
                });

                row.querySelectorAll('[aria-describedby]').forEach((field) => {
                    field.setAttribute(
                        'aria-describedby',
                        field.getAttribute('aria-describedby').replace(/_employee_\d+_/, `_employee_${index}_`),
                    );
                });
            });
        }

        form.querySelector('[data-broker-add-employee]')?.addEventListener('click', () => {
            const rows = list.querySelectorAll('[data-broker-employee-row]');

            if (rows.length >= 50) {
                return;
            }

            list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(rows.length)));
            list.lastElementChild?.querySelector('select, input')?.focus();
        });

        list.addEventListener('click', (event) => {
            const button = event.target.closest('[data-broker-remove-employee]');

            if (!button) {
                return;
            }

            const rows = Array.from(list.querySelectorAll('[data-broker-employee-row]'));

            /** The last row is emptied rather than dropped, so the block keeps a row to fill. */
            if (rows.length === 1) {
                rows[0].querySelectorAll('select, input').forEach((field) => {
                    field.value = '';
                });

                return;
            }

            button.closest('[data-broker-employee-row]')?.remove();
            renumberRows();
        });
    });
}

function initializeInformationAdmin() {
    initializeInformationAdminTabs();
    initializeInformationLookupFilters();
    initializeInformationPortFilters();
    initializeInformationAdminConfirmations();
    initializeInformationBrokerBranches();
    initializeInformationBrokerStalls();
    initializeInformationBrokerEmployees();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeInformationAdmin);
} else {
    initializeInformationAdmin();
}
