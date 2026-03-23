<x-layout>
    <x-slot name="pretitle">Trackable View</x-slot>
    <x-slot name="title">{{ $trackable->name }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables.records.create', $trackable->uid) }}" class="btn btn-primary">
            Add record
        </a>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <form method="GET" action="{{ route('trackables.show', $trackable->uid) }}" class="d-flex flex-column gap-4">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Filters</h3>
                            <div class="text-secondary small">
                                {{ $activeFilterCount }} active {{ \Illuminate\Support\Str::plural('filter', $activeFilterCount) }}
                            </div>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column gap-4">
                        <div>
                            <label class="form-label" for="filter-q">Free text</label>
                            <input
                                class="form-control"
                                type="text"
                                id="filter-q"
                                name="q"
                                value="{{ $filters['q'] }}"
                                placeholder="Search inside any saved value"
                            >
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="filter-date-from">From date</label>
                                <input
                                    class="form-control"
                                    type="date"
                                    id="filter-date-from"
                                    name="date_from"
                                    value="{{ $filters['date_from'] }}"
                                >
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="filter-date-to">To date</label>
                                <input
                                    class="form-control"
                                    type="date"
                                    id="filter-date-to"
                                    name="date_to"
                                    value="{{ $filters['date_to'] }}"
                                >
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="filter-sort-by">Sort by</label>
                                <select class="form-select" id="filter-sort-by" name="sort_by">
                                    @foreach($sortOptions as $option)
                                        <option value="{{ $option }}" @selected($filters['sort_by'] === $option)>
                                            {{ $option === 'record_date' ? 'Record date' : 'Created at' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="filter-sort-dir">Direction</label>
                                <select class="form-select" id="filter-sort-dir" name="sort_dir">
                                    <option value="desc" @selected($filters['sort_dir'] === 'desc')>Newest first</option>
                                    <option value="asc" @selected($filters['sort_dir'] === 'asc')>Oldest first</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="filter-per-page">Rows per page</label>
                                <select class="form-select" id="filter-per-page" name="per_page">
                                    @foreach($perPageOptions as $option)
                                        <option value="{{ $option }}" @selected((int) $filters['per_page'] === $option)>
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div>
                            <h3 class="card-title">Schema-Aware Filters</h3>
                            <div class="text-secondary small">Generated automatically from the current trackable schema</div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @forelse($schema as $field)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                        <div>
                                            <div class="fw-semibold">{{ $field->name }}</div>
                                            <div class="text-secondary small">Type: {{ $field->field_type ?: 'string' }}</div>
                                        </div>
                                        @if(!empty($filters['schema'][$field->uid]))
                                            <span class="badge bg-blue-lt">Active</span>
                                        @endif
                                    </div>

                                    <input
                                        class="form-control"
                                        type="{{ in_array($field->field_type, ['int', 'float']) ? 'number' : (in_array($field->field_type, ['date', 'datetime']) ? ($field->field_type === 'date' ? 'date' : 'datetime-local') : 'text') }}"
                                        @if($field->field_type === 'float') step="any" @endif
                                        id="schema-filter-{{ $field->uid }}"
                                        name="schema[{{ $field->uid }}]"
                                        value="{{ $filters['schema'][$field->uid] ?? '' }}"
                                        placeholder="{{ in_array($field->field_type, ['int', 'float', 'bool', 'date', 'datetime']) ? 'Exact value' : 'Contains value' }}"
                                    >

                                    <div class="text-secondary small mt-2">
                                        {{ in_array($field->field_type, ['int', 'float', 'bool', 'date', 'datetime']) ? 'Exact match' : 'Partial match' }}
                                        · {{ $field->uid }}
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item text-secondary">
                                    No schema fields configured.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Apply filters</button>
                        <a href="{{ route('trackables.show', $trackable->uid) }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Records</h3>
                        <div class="text-secondary small">{{ number_format($records->total()) }} matching records</div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($records as $record)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                <div>
                                    <div class="fw-semibold">
                                        Record Date: {{ $record->record_date }}
                                    </div>
                                    <a href="{{ route('trackables.records.edit', [$trackable->uid, $record->uid]) }}" class="btn btn-sm btn-outline-primary mt-2">
                                        Edit record
                                    </a>
                                </div>
                                <div class="text-secondary small">
                                    Created: {{ $record->created_at }}
                                </div>
                            </div>

                            @if($record->data->isEmpty())
                                <div class="text-secondary">No values stored for this record.</div>
                            @else
                                <div class="row g-3">
                                    @foreach($record->data->sortBy(function ($field) use ($schemaOrder) {
                                        return $schemaOrder[$field->trackable_schema_uid] ?? PHP_INT_MAX;
                                    }) as $field)
                                        <div class="col-12 col-md-6">
                                            <div class="text-secondary small">
                                                {{ $schemaByUid[$field->trackable_schema_uid]->name ?? $field->trackable_schema_uid }}
                                            </div>
                                            <div>{{ $field->value }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-secondary">No records match the current filters.</div>
                    @endforelse
                </div>
                @if($records->hasPages())
                    <div class="card-footer">
                        {{ $records->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

</x-layout>
