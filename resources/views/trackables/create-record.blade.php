<x-layout>
    <x-slot name="pretitle">New Record</x-slot>
    <x-slot name="title">Add data to {{ $trackable->name }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables.show', $trackable->uid) }}" class="btn btn-outline-secondary">
            Back to records
        </a>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">The record could not be saved.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('trackables.partials.record-form', [
        'action' => route('trackables.records.store', $trackable->uid),
        'method' => 'POST',
        'cardTitle' => 'Record data',
        'recordValues' => [],
        'recordDateValue' => now()->format('Y-m-d\TH:i'),
        'showRecordDateInput' => true,
        'footerText' => 'The measurement time can be set manually when needed.',
        'submitLabel' => 'Save record',
    ])
</x-layout>
