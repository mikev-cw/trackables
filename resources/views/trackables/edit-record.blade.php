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
        'recordDateValue' => $recordDateValue,
        'showRecordDateInput' => true,
        'footerText' => 'Measurement time can be corrected here, but future dates are not allowed.',
        'submitLabel' => 'Update record',
    ])
</x-layout>
