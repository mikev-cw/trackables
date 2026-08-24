<x-layout>
    <x-slot name="pretitle">Groups</x-slot>
    <x-slot name="title">Create Group</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackable-groups.index') }}" class="btn btn-outline-secondary">Back to groups</a>
    </x-slot>

    @include('trackable-groups.partials.form', [
        'action' => route('trackable-groups.store'),
        'method' => 'POST',
        'cardTitle' => 'Group details',
        'submitLabel' => 'Create group',
    ])
</x-layout>
