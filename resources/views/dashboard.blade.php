<x-layout>
    <x-slot name="pretitle">Dashboard</x-slot>
    <x-slot name="title">Your Trackables</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables_index') }}" class="btn btn-outline-primary">
            Refresh list
        </a>
    </x-slot>

    @isset($list)
        @if($list->isEmpty())
            <div class="card">
                <div class="card-body text-center py-5">
                    <h3 class="card-title mb-2">No trackables yet</h3>
                    <p class="text-secondary mb-0">
                        Once you start creating trackables, they will appear here with direct links to records and statistics.
                    </p>
                </div>
            </div>
        @else
            <div class="row row-cards">
                @foreach ($list as $trackable)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                    <div>
                                        <div class="text-uppercase text-secondary fw-bold small mb-1">Trackable</div>
                                        <h3 class="card-title mb-1">{{ $trackable->name }}</h3>
                                        <div class="text-secondary small">
                                            UID: <span class="font-monospace">{{ $trackable->uid }}</span>
                                        </div>
                                    </div>
                                    <span class="badge bg-azure-lt">Active</span>
                                </div>

                                <div class="text-secondary small mb-3">
                                    Created {{ optional($trackable->created_at)->format('d M Y, H:i') }}
                                </div>

                                <div class="mt-auto d-grid gap-2">
                                    <a href="{{ route('trackables.show', $trackable) }}" class="btn btn-primary">
                                        Open records
                                    </a>
                                    <a href="{{ route('trackables.statistics', $trackable) }}" class="btn btn-outline-primary">
                                        Open statistics
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $list->links() }}
            </div>
        @endif
    @endisset

</x-layout>
