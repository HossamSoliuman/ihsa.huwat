function initializeInformationDashboard() {
    const dashboard = document.querySelector('.info-dashboard-header');
    const tooltip = document.querySelector('[data-chart-tooltip]');

    if (!dashboard || !tooltip) {
        return;
    }

    const positionTooltip = (mark) => {
        const bounds = mark.getBoundingClientRect();
        const tooltipBounds = tooltip.getBoundingClientRect();
        const preferredLeft = bounds.left + (bounds.width / 2) - (tooltipBounds.width / 2);
        const left = Math.min(window.innerWidth - tooltipBounds.width - 12, Math.max(12, preferredLeft));
        const top = Math.max(12, bounds.top - tooltipBounds.height - 10);

        tooltip.style.translate = `${Math.round(left)}px ${Math.round(top)}px`;
    };

    const showTooltip = (mark) => {
        tooltip.textContent = mark.dataset.tooltip;
        tooltip.hidden = false;
        positionTooltip(mark);

        const chart = mark.closest('svg');
        const crosshair = chart?.querySelector('[data-chart-crosshair]');

        if (crosshair && mark.dataset.chartX) {
            crosshair.setAttribute('x1', mark.dataset.chartX);
            crosshair.setAttribute('x2', mark.dataset.chartX);
            crosshair.hidden = false;
        }
    };

    const hideTooltip = (mark) => {
        tooltip.hidden = true;
        const crosshair = mark.closest('svg')?.querySelector('[data-chart-crosshair]');

        if (crosshair) {
            crosshair.hidden = true;
        }
    };

    document.querySelectorAll('[data-chart-mark], [data-chart-point]').forEach((mark) => {
        mark.addEventListener('pointerenter', () => showTooltip(mark));
        mark.addEventListener('pointerleave', () => hideTooltip(mark));
        mark.addEventListener('focus', () => showTooltip(mark));
        mark.addEventListener('blur', () => hideTooltip(mark));
    });

    window.addEventListener('scroll', () => {
        tooltip.hidden = true;
    }, { passive: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeInformationDashboard);
} else {
    initializeInformationDashboard();
}
