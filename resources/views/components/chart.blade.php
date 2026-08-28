@props(['type', 'labels', 'datasets', 'height' => '260px', 'options' => []])

<div
    x-data="{
        chart: null,
        init() {
            this.chart = new Chart(this.$refs.canvas.getContext('2d'), {
                type: @js($type),
                data: {
                    labels: @js($labels),
                    datasets: @js($datasets),
                },
                options: Object.assign({
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: {{ count($datasets) > 1 ? 'true' : 'false' }} } },
                }, @js($options)),
            });
        },
        destroy() {
            this.chart?.destroy();
        },
    }"
    x-init="init()"
    x-on:destroy="destroy()"
    wire:ignore
    style="height: {{ $height }}"
>
    <canvas x-ref="canvas"></canvas>
</div>