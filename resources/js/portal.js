/**
 * Public portal: Apache ECharts exposed to Alpine as x-data="echart(option)".
 * ECharts is loaded on demand (code-split), only on pages that show a chart.
 * Livewire ships Alpine; this registers the component before Alpine starts.
 */
const suppressed = (value) => (value === null || value === undefined ? '<5' : value);
let loader = null;

document.addEventListener('alpine:init', () => {
    window.Alpine.data('echart', (option) => {
        // The ECharts instance must NOT live in Alpine's reactive state: a reactive proxy
        // around it breaks ECharts internals (e.g. "reading 'getBaseAxis'").
        let chart = null;
        let observer = null;

        return {
            async init() {
                loader = loader || import('./portal-charts.js');
                const { default: echarts } = await loader;

                chart = echarts.init(this.$el, null, { renderer: 'svg' });
                chart.setOption({
                    aria: { enabled: true },
                    textStyle: { fontFamily: 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif' },
                    ...option,
                    tooltip: { ...(option.tooltip || {}), valueFormatter: suppressed },
                });
                observer = new ResizeObserver(() => chart && chart.resize());
                observer.observe(this.$el);
            },

            destroy() {
                if (observer) {
                    observer.disconnect();
                }
                if (chart) {
                    chart.dispose();
                    chart = null;
                }
            },
        };
    });
});
