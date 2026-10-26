<x-layout>
    <x-slot name="pretitle">Trackables</x-slot>
    <x-slot name="title">Duplicate {{ $source->name }}</x-slot>
    <x-slot name="script">js/trackable-form.js</x-slot>

    <p class="text-secondary">Save to create a new trackable with the schema and statistics from {{ $source->name }}. No records will be copied.</p>

    @include('trackables.partials.trackable-form', [
        'action' => route('trackables.duplicate.store', $source->uid),
        'method' => 'POST',
        'cardTitle' => 'New trackable details',
        'submitLabel' => 'Save trackable',
        'autoAlias' => true,
        'cancelUrl' => route('dashboard'),
    ])
</x-layout>
