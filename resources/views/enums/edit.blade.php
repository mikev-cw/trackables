<x-layout>
    <x-slot name="pretitle">Lists</x-slot>
    <x-slot name="title">{{ $enum->name }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('enums.index') }}" class="btn btn-outline-secondary">Back to lists</a>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">Check the highlighted fields and save again.</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">List details</h3></div>
                <form method="POST" action="{{ route('enums.update', $enum->uid) }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <label class="form-label" for="enum-name">Name</label>
                        <input class="form-control @error('name') is-invalid @enderror" id="enum-name" name="name" type="text" value="{{ old('name', $enum->name) }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="card-footer"><button class="btn btn-primary" type="submit">Save name</button></div>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Values</h3>
                        <div class="text-secondary small">Labels and descriptions are editable. Keys and UIDs remain stable.</div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Key / UID</th>
                                <th>Presentation</th>
                                <th>Status</th>
                                <th class="w-1"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($enum->values as $value)
                                <tr>
                                    <td>
                                        <div class="font-monospace fw-semibold">{{ $value->value }}</div>
                                        <div class="font-monospace text-secondary small">{{ $value->uid }}</div>
                                        @if ($usedValueUids->contains($value->uid))
                                            <span class="badge bg-blue-lt text-blue mt-2">In use</span>
                                        @endif
                                    </td>
                                    <td style="min-width: 19rem">
                                        <form id="value-form-{{ $value->uid }}" method="POST" action="{{ route('enums.values.update', [$enum->uid, $value->uid]) }}">
                                            @csrf
                                            @method('PUT')
                                            <input class="form-control mb-2" name="label" value="{{ $value->display_label }}" aria-label="Label for {{ $value->value }}">
                                            <textarea class="form-control" name="description" rows="2" aria-label="Description for {{ $value->value }}">{{ $value->description }}</textarea>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge {{ $value->is_active ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' }}">
                                            {{ $value->is_active ? 'Active' : 'Retired' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-list flex-nowrap">
                                            <button class="btn btn-sm btn-primary" type="submit" form="value-form-{{ $value->uid }}">Save</button>
                                            <form method="POST" action="{{ route('enums.values.toggle', [$enum->uid, $value->uid]) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm {{ $value->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                                                    {{ $value->is_active ? 'Retire' : 'Enable' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card mb-4">
                <div class="card-header"><h3 class="card-title">Add value</h3></div>
                <form method="POST" action="{{ route('enums.values.store', $enum->uid) }}">
                    @csrf
                    <div class="card-body d-flex flex-column gap-3">
                        <div>
                            <label class="form-label">Key</label>
                            <input class="form-control @error('key') is-invalid @enderror" name="key" value="{{ old('key') }}" placeholder="in_progress">
                            @error('key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label">Label</label>
                            <input class="form-control @error('label') is-invalid @enderror" name="label" value="{{ old('label') }}" placeholder="In progress">
                            @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer"><button class="btn btn-primary" type="submit">Add value</button></div>
                </form>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Used by</h3></div>
                <div class="list-group list-group-flush">
                    @forelse ($enum->schemaFields as $field)
                        <a class="list-group-item list-group-item-action" href="{{ route('trackables.schema.edit', $field->trackable_uid) }}">
                            <div class="fw-semibold">{{ $field->name }}</div>
                            <div class="text-secondary small">{{ $field->trackable?->name }}</div>
                        </a>
                    @empty
                        <div class="list-group-item text-secondary">Not assigned to a schema field yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layout>
