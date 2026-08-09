document.addEventListener('DOMContentLoaded', function () {
    var iconPaths = {
        'home': '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v10h13V10M9.5 20v-6h5v6"/>',
        'grid': '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
        'database': '<ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v6c0 1.7 3.6 3 8 3s8-1.3 8-3V5"/><path d="M4 11v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/>',
        'map': '<polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21 3 6"/><path d="M9 3v15M15 6v15"/>',
        'flag': '<path d="M5 21V4M5 5c5-4 9 4 14 0v10c-5 4-9-4-14 0"/>',
        'anchor': '<circle cx="12" cy="5" r="2"/><path d="M12 7v14M5 12H2c0 5.5 4.5 9 10 9s10-3.5 10-9h-3M8 17l4 4 4-4"/>',
        'globe': '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.2 3 14.8 0 18M12 3c-3 3.2-3 14.8 0 18"/>',
        'ship': '<path d="m3 15 2 5h14l2-5-9-3-9 3Z"/><path d="M7 14V7h10v7M10 7V3h4v4M4 21c2 0 2-1 4-1s2 1 4 1 2-1 4-1 2 1 4 1"/>',
        'user': '<circle cx="12" cy="8" r="4"/><path d="M4 21c.8-5 3.5-7 8-7s7.2 2 8 7"/>',
        'id-card': '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8" cy="11" r="2.2"/><path d="M5.5 16c.5-1.8 1.4-2.7 2.5-2.7s2 .9 2.5 2.7M13 10h5M13 14h5"/>',
        'briefcase': '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5h8v2M3 12c5 3 13 3 18 0M12 11v4"/>',
        'clipboard': '<rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4c0-1 1-2 3-2s3 1 3 2v2H9V4ZM9 11h6M9 15h6"/>',
        'alert-triangle': '<path d="M10.3 3.8 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.8a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
        'users': '<circle cx="9" cy="8" r="4"/><path d="M2 21c.7-5 3-7 7-7s6.3 2 7 7M16 4c2.2.3 3.5 1.7 3.5 4S18.2 11.7 16 12M17 14c3 .5 4.5 2.6 5 6"/>',
        'bar-chart': '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'clock': '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'dollar-sign': '<circle cx="12" cy="12" r="9"/><path d="M16 8.5c-.8-1-2-1.5-4-1.5-2.2 0-4 1.1-4 3s1.5 2.5 4 3 4 1.2 4 3-1.8 3-4 3c-2 0-3.3-.6-4-1.6M12 5v14"/>',
        'settings': '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
        'file-text': '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'bell': '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'calendar': '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>',
        'landmark': '<path d="m3 9 9-5 9 5M5 10h14M6 10v8M10 10v8M14 10v8M18 10v8M4 18h16M3 21h18"/>',
        'log-out': '<path d="M10 4H5v16h5M14 8l4 4-4 4M8 12h10"/>'
    };

    document.querySelectorAll('.nav-icon[data-icon]').forEach(function (icon) {
        var paths = iconPaths[icon.getAttribute('data-icon')] || iconPaths.grid;
        icon.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true">' + paths + '</svg>';
    });

    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');

    function isMobile() { return window.matchMedia('(max-width: 900px)').matches; }
    function closeMobileSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('open');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            if (isMobile()) {
                var willOpen = !sidebar.classList.contains('open');
                sidebar.classList.toggle('open', willOpen);
                if (backdrop) backdrop.classList.toggle('open', willOpen);
                toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            } else {
                document.body.classList.toggle('sidebar-collapsed');
            }
        });
    }
    if (backdrop) backdrop.addEventListener('click', closeMobileSidebar);
    window.addEventListener('resize', function () { if (!isMobile()) closeMobileSidebar(); });

    var themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-theme') || 'dark';
            var next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        });
    }

    var search = document.getElementById('headerSearch');
    var searchToggle = document.getElementById('headerSearchToggle');
    var searchClose = document.getElementById('headerSearchClose');
    var searchInput = document.getElementById('headerSearchInput');
    var navLinks = Array.from(document.querySelectorAll('.sidebar-nav .nav-link'));

    function closeSearch() {
        if (!search) return;
        search.classList.remove('open');
        if (searchInput) searchInput.value = '';
        navLinks.forEach(function (link) { link.hidden = false; });
    }
    if (searchToggle && search) searchToggle.addEventListener('click', function () {
        search.classList.add('open');
        window.setTimeout(function () { if (searchInput) searchInput.focus(); }, 180);
    });
    if (searchClose) searchClose.addEventListener('click', closeSearch);
    if (search) search.addEventListener('submit', function (event) { event.preventDefault(); });
    if (searchInput) searchInput.addEventListener('input', function () {
        var query = searchInput.value.trim().toLocaleLowerCase('ar');
        navLinks.forEach(function (link) {
            link.hidden = query !== '' && !link.textContent.toLocaleLowerCase('ar').includes(query);
        });
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') { closeSearch(); closeMobileSidebar(); }
    });

    document.querySelectorAll('[data-dialog]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            var dialog = document.getElementById(trigger.getAttribute('data-dialog'));
            if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
        });
    });
    document.querySelectorAll('[data-detail]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            var dialog = document.getElementById(trigger.getAttribute('data-detail'));
            if (!dialog || typeof dialog.showModal !== 'function') return;
            var filter = trigger.getAttribute('data-filter');
            dialog.querySelectorAll('[data-row-type]').forEach(function (row) {
                row.hidden = !!filter && row.getAttribute('data-row-type') !== filter;
            });
            dialog.showModal();
        });
    });
    document.querySelectorAll('dialog [data-close]').forEach(function (button) {
        button.addEventListener('click', function () { button.closest('dialog').close(); });
    });
    document.querySelectorAll('dialog').forEach(function (dialog) {
        dialog.addEventListener('click', function (event) {
            var rect = dialog.getBoundingClientRect();
            if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) dialog.close();
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) event.preventDefault();
        });
    });

    document.querySelectorAll('[data-print]').forEach(function (button) {
        button.addEventListener('click', function () { window.print(); });
    });

    document.querySelectorAll('[data-harbor-selector]').forEach(function (form) {
        var region = form.querySelector('[data-harbor-region]');
        var city = form.querySelector('[data-harbor-city]');
        var harbor = form.querySelector('[data-harbor-port]');
        var submit = form.querySelector('[data-harbor-submit]');

        if (!region || !city || !harbor) return;

        function filterOptions(select, attribute, value) {
            Array.from(select.options).forEach(function (option, index) {
                if (index === 0) return;
                var isVisible = value !== '' && option.getAttribute(attribute) === value;
                option.hidden = !isVisible;
                option.disabled = !isVisible;
            });
        }

        function refreshCities(resetValue) {
            if (resetValue) city.value = '';
            filterOptions(city, 'data-region-id', region.value);
            city.disabled = region.value === '';
            if (city.selectedOptions[0] && city.selectedOptions[0].disabled) city.value = '';
        }

        function refreshHarbors(resetValue) {
            if (resetValue) harbor.value = '';
            filterOptions(harbor, 'data-governorate-id', city.value);
            harbor.disabled = city.value === '';
            if (harbor.selectedOptions[0] && harbor.selectedOptions[0].disabled) harbor.value = '';
            if (submit) submit.disabled = harbor.value === '';
        }

        region.addEventListener('change', function () {
            refreshCities(true);
            refreshHarbors(true);
        });
        city.addEventListener('change', function () { refreshHarbors(true); });
        harbor.addEventListener('change', function () {
            if (submit) submit.disabled = harbor.value === '';
        });

        refreshCities(false);
        refreshHarbors(false);
    });
});
