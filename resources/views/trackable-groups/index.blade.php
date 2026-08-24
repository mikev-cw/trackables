<x-layout>
    <x-slot name="pretitle">Groups</x-slot>
    <x-slot name="title">Trackable Groups</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackable-groups.create') }}" class="btn btn-primary">New group</a>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Back to dashboard</a>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Trackables</th>
                        <th>Status</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr>
                            <td class="fw-semibold">{{ $group->name }}</td>
                            <td class="text-secondary">{{ $group->description ?: 'No description' }}</td>
                            <td>{{ $group->trackables_count }}</td>
                            <td>
                                <span class="badge {{ $group->deleted ? 'bg-red-lt' : 'bg-azure-lt' }}">
                                    {{ $group->deleted ? 'Disabled' : 'Active' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <a href="{{ route('trackable-groups.edit', $group->uid) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form method="POST" action="{{ route('trackable-groups.toggle', $group->uid) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm {{ $group->deleted ? 'btn-success' : 'btn-outline-danger' }}">
                                            {{ $group->deleted ? 'Enable' : 'Disable' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">No groups configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $groups->links() }}
    </div>
</x-layout>
