<x-layout>
    <x-slot name="pretitle">Trackables</x-slot>
    <x-slot name="title">Edit {{ $trackable->name }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back to dashboard</a>
        <a href="{{ route('trackables.schema.edit', $trackable->uid) }}" class="btn btn-outline-primary">Edit schema</a>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            @include('trackables.partials.trackable-form', [
                'action' => route('trackables.update', $trackable->uid),
                'method' => 'PUT',
                'cardTitle' => 'Trackable details',
                'submitLabel' => 'Save changes',
            ])
        </div>
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body d-flex flex-column gap-3">
                    <div>
                        <div class="text-secondary small">Current status</div>
                        <div class="fw-semibold">{{ $trackable->deleted ? 'Disabled' : 'Enabled' }}</div>
                    </div>
                    <div>
                        <div class="text-secondary small">Alias</div>
                        <div class="fw-semibold font-monospace">{{ $trackable->alias }}</div>
                    </div>
                    <div>
                        <div class="text-secondary small">Schema fields</div>
                        <div class="fw-semibold">{{ $trackable->schema_count }}</div>
                    </div>
                    <div>
                        <div class="text-secondary small">Last record</div>
                        <div class="fw-semibold">
                            {{ $trackable->records_max_record_date ? \Illuminate\Support\Carbon::parse($trackable->records_max_record_date)->format('d M Y, H:i') : 'No records yet' }}
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="POST" action="{{ route('trackables.toggle', $trackable->uid) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn {{ $trackable->deleted ? 'btn-success' : 'btn-outline-danger' }} w-100">
                            {{ $trackable->deleted ? 'Enable trackable' : 'Disable trackable' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
