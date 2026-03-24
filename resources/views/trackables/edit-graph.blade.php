<x-layout>
    <x-slot name="pretitle">Edit Graph</x-slot>
    <x-slot name="title">{{ $trackable->name }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables.statistics', $trackable->uid) }}" class="btn btn-outline-secondary">
            Back to statistics
        </a>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold mb-2">The graph could not be updated.</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('trackables.partials.graph-form', [
        'action' => route('trackables.statistics.graphs.update', [$trackable->uid, $graph->uid]),
        'method' => 'PUT',
        'cardTitle' => 'Edit Graph',
        'submitLabel' => 'Update graph',
    ])
</x-layout>
