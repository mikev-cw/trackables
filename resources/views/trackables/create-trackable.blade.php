<x-layout>
    <x-slot name="pretitle">Trackables</x-slot>
    <x-slot name="title">Create Trackable</x-slot>
    <x-slot name="actions">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back to dashboard</a>
    </x-slot>

    @include('trackables.partials.trackable-form', [
        'action' => route('trackables.store'),
        'method' => 'POST',
        'cardTitle' => 'Trackable details',
        'submitLabel' => 'Create trackable',
    ])
</x-layout>
