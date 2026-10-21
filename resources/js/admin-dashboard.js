import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Legend,
    Tooltip,
    Filler,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Legend,
    Tooltip,
    Filler,
);

function formatDayLabel(isoDate) {
    const parts = String(isoDate).split('-').map(Number);
    if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) {
        return isoDate;
    }
    const date = new Date(parts[0], parts[1] - 1, parts[2]);
    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function initProductViewsChart() {
    const canvas = document.getElementById('admin-product-views-chart');
    if (! canvas) {
        return;
    }

    const payloadEl = document.getElementById('admin-dashboard-chart-data');
    const statusEl = document.getElementById('admin-product-views-chart-status');

    let payload = { labels: [], datasets: [], empty: true, empty_reason: 'No chart data.' };
    try {
        payload = JSON.parse(payloadEl?.textContent || '{}');
    } catch (error) {
        if (statusEl) {
            statusEl.hidden = false;
            statusEl.textContent = 'Unable to load chart data.';
        }
        return;
    }

    if (payload.empty || ! Array.isArray(payload.datasets) || payload.datasets.length === 0) {
        if (statusEl) {
            statusEl.hidden = false;
            statusEl.textContent = payload.empty_reason || 'No product views to display for this period.';
        }
        canvas.setAttribute('aria-hidden', 'true');
        return;
    }

    if (statusEl) {
        statusEl.hidden = true;
    }

    const labels = (payload.labels || []).map(formatDayLabel);

    new Chart(canvas.getContext('2d'), {
        type: 'line',
        data: {
            labels,
            datasets: payload.datasets.map((dataset) => ({
                label: dataset.label,
                data: dataset.data,
                borderColor: dataset.borderColor,
                backgroundColor: dataset.borderColor + '22',
                borderWidth: 2,
                pointRadius: 2,
                pointHoverRadius: 4,
                tension: 0.25,
                fill: false,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        usePointStyle: true,
                        pointStyle: 'circle',
                    },
                },
                tooltip: {
                    callbacks: {
                        title(items) {
                            return items[0]?.label || '';
                        },
                        label(context) {
                            const value = context.parsed.y ?? 0;
                            return `${context.dataset.label}: ${value} view${value === 1 ? '' : 's'}`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date',
                    },
                    grid: {
                        display: false,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                    },
                    title: {
                        display: true,
                        text: 'Product page views',
                    },
                },
            },
        },
    });
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('adminDashPanel', (key, defaultOpen = true) => ({
        open: defaultOpen,
        init() {
            try {
                const stored = localStorage.getItem(`adminDashPanel.${key}`);
                if (stored === '0') {
                    this.open = false;
                } else if (stored === '1') {
                    this.open = true;
                }
            } catch (error) {
                // Ignore private-mode / storage errors.
            }
        },
        toggle() {
            this.open = ! this.open;
            try {
                localStorage.setItem(`adminDashPanel.${key}`, this.open ? '1' : '0');
            } catch (error) {
                // Ignore private-mode / storage errors.
            }
        },
    }));
});

document.addEventListener('DOMContentLoaded', initProductViewsChart);
