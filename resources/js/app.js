

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

Alpine.start();

document.querySelectorAll('[data-report-chart]').forEach((canvas) => {
    const source = document.getElementById(canvas.dataset.reportChart);

    if (!source) {
        return;
    }

    const config = JSON.parse(source.textContent);
    const hasData = config.data.datasets.some((dataset) => dataset.data.some((value) => Number(value) !== 0));

    if (!hasData) {
        canvas.closest('[data-chart-container]')?.querySelector('[data-chart-empty]')?.classList.remove('hidden');
        canvas.classList.add('hidden');
        return;
    }

    new Chart(canvas, config);
});
