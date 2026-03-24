<x-layout>
    <x-slot name="pretitle">Statistics</x-slot>
    <x-slot name="title">{{ $trackable->name }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables.show', $trackable->uid) }}" class="btn btn-outline-secondary">
            Back to records
        </a>
    </x-slot>

    @php
        $graphConfigs = $graphs->map(function ($graph) {
            return [
                'uid' => $graph['uid'],
                'graphType' => $graph['graph_type'],
                'chart' => $graph['chart'],
            ];
        })->values();
    @endphp

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">The graph could not be saved.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            @include('trackables.partials.graph-form', [
                'action' => route('trackables.statistics.graphs.store', $trackable->uid),
                'method' => 'POST',
                'cardTitle' => 'Add Graph',
                'submitLabel' => 'Add graph',
            ])
        </div>

        <div class="col-12 col-xl-8">
            <div class="d-flex flex-column gap-4">
                @forelse($graphs as $graph)
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">{{ $graph['title'] }}</h3>
                                <div class="text-secondary small">
                                    {{ $graph['range_label'] }} · {{ $graph['bucket_label'] }} · {{ $graph['aggregate_label'] }} · {{ $graph['series_label'] }}
                                </div>
                                @if($graph['active_filter_count'] > 0)
                                    <div class="text-secondary small">
                                        Filters: {{ implode(' | ', $graph['filter_summary']) }}
                                    </div>
                                @endif
                            </div>
                            <div class="ms-auto d-flex gap-2">
                                <a href="{{ route('trackables.statistics.graphs.edit', [$trackable->uid, $graph['uid']]) }}" class="btn btn-sm btn-outline-primary">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('trackables.statistics.graphs.destroy', [$trackable->uid, $graph['uid']]) }}" onsubmit="return confirm('Delete this graph?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="ratio ratio-21x9">
                                <canvas id="graph-{{ $graph['uid'] }}"></canvas>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card">
                        <div class="card-body text-secondary">
                            No graphs yet. Add one from the left panel to start exploring this trackable.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
    <script>
        const graphConfigs = @json($graphConfigs);

        const palette = ['#206bc4', '#2fb344', '#f59f00', '#d63939', '#6f42c1', '#17a2b8'];

        graphConfigs.forEach((graph) => {
            const canvas = document.getElementById(`graph-${graph.uid}`);

            if (!canvas) {
                return;
            }

            new Chart(canvas, {
                type: graph.graphType,
                data: {
                    labels: graph.chart.labels,
                    datasets: graph.chart.datasets.map((dataset, index) => ({
                        label: dataset.label,
                        data: dataset.data,
                        borderColor: palette[index % palette.length],
                        backgroundColor: `${palette[index % palette.length]}33`,
                        tension: 0.2,
                        spanGaps: true,
                    })),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                        },
                    },
                    plugins: {
                        zoom: {
                            pan: {
                                enabled: true,
                                mode: 'x',
                            },
                            zoom: {
                                wheel: {
                                    enabled: true,
                                },
                                pinch: {
                                    enabled: true,
                                },
                                mode: 'x',
                            },
                        },
                    },
                },
            });
        });
    </script>
</x-layout>
