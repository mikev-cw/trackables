<x-layout>
    <x-slot name="pretitle">Edit Record</x-slot>
    <x-slot name="title">Edit data in {{ $trackable->name }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables.show', $trackable->uid) }}" class="btn btn-outline-secondary">
            Back to records
        </a>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">The record could not be updated.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('trackables.partials.record-form', [
        'action' => route('trackables.records.update', [$trackable->uid, $record->uid]),
        'method' => 'PUT',
        'cardTitle' => 'Record data',
        'recordValues' => $recordValues,
        'footerText' => 'Updating a record refreshes its saved timestamp.',
        'submitLabel' => 'Update record',
    ])
</x-layout>
