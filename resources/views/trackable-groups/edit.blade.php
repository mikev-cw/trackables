<x-layout>
    <x-slot name="pretitle">Groups</x-slot>
    <x-slot name="title">Edit {{ $trackableGroup->name }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackable-groups.index') }}" class="btn btn-outline-secondary">Back to groups</a>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            @include('trackable-groups.partials.form', [
                'action' => route('trackable-groups.update', $trackableGroup->uid),
                'method' => 'PUT',
                'cardTitle' => 'Group details',
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
                        <div class="fw-semibold">{{ $trackableGroup->deleted ? 'Disabled' : 'Enabled' }}</div>
                    </div>
                    <div>
                        <div class="text-secondary small">Trackables</div>
                        <div class="fw-semibold">{{ $trackableGroup->trackables_count }}</div>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="POST" action="{{ route('trackable-groups.toggle', $trackableGroup->uid) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn {{ $trackableGroup->deleted ? 'btn-success' : 'btn-outline-danger' }} w-100">
                            {{ $trackableGroup->deleted ? 'Enable group' : 'Disable group' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
