<x-layout>
    <x-slot name="pretitle">Dashboard</x-slot>
    <x-slot name="title">Your Trackables</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables.create') }}" class="btn btn-primary">
            New trackable
        </a>
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
                                        <div class="text-secondary small">
                                            Alias: <span class="font-monospace">{{ $trackable->alias }}</span>
                                        </div>
                                    </div>
                                    <span class="badge {{ $trackable->deleted ? 'bg-red-lt' : 'bg-azure-lt' }}">
                                        {{ $trackable->deleted ? 'Disabled' : 'Active' }}
                                    </span>
                                </div>

                                <div class="d-flex flex-column gap-2 text-secondary small mb-3">
                                    <div>Created {{ optional($trackable->created_at)->format('d M Y, H:i') }}</div>
                                    <div>Schema fields: {{ $trackable->schema_count }}</div>
                                    <div>
                                        Last record:
                                        {{ $trackable->records_max_record_date ? \Illuminate\Support\Carbon::parse($trackable->records_max_record_date)->format('d M Y, H:i') : 'No records yet' }}
                                    </div>
                                </div>

                                <div class="mt-auto d-flex flex-wrap gap-2 align-items-stretch">
                                    <a href="{{ route('trackables.show', $trackable) }}" class="btn btn-primary btn-sm flex-fill">
                                        Open records
                                    </a>
                                    <a href="{{ route('trackables.statistics', $trackable) }}" class="btn btn-outline-primary btn-sm flex-fill">
                                        Open statistics
                                    </a>
                                    <a href="{{ route('trackables.edit', $trackable) }}" class="btn btn-outline-secondary btn-sm flex-fill">
                                        Edit trackable
                                    </a>
                                    <a href="{{ route('trackables.schema.edit', $trackable) }}" class="btn btn-outline-secondary btn-sm flex-fill">
                                        Edit schema
                                    </a>
                                    <form method="POST" action="{{ route('trackables.toggle', $trackable) }}" class="flex-fill">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $trackable->deleted ? 'btn-success' : 'btn-outline-danger' }} w-100">
                                            {{ $trackable->deleted ? 'Enable trackable' : 'Disable trackable' }}
                                        </button>
                                    </form>
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
