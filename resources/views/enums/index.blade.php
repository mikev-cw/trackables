<x-layout>
    <x-slot name="pretitle">Settings</x-slot>
    <x-slot name="title">Lists</x-slot>
    <x-slot name="actions">
        <a href="{{ route('enums.create') }}" class="btn btn-primary">New list</a>
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
                        <th>Values</th>
                        <th>Schema fields</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enums as $enum)
                        <tr>
                            <td class="fw-semibold">{{ $enum->name }}</td>
                            <td>{{ $enum->values_count }}</td>
                            <td>{{ $enum->schema_fields_count }}</td>
                            <td><a href="{{ route('enums.edit', $enum->uid) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-5">No lists configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $enums->links() }}</div>
</x-layout>
