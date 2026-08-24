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
