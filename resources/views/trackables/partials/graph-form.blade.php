<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">{{ $cardTitle }}</h3>
            <div class="text-secondary small">Graphs adapt automatically to the current trackable schema.</div>
        </div>
    </div>
    <form method="POST" action="{{ $action }}">
        @csrf
        @if (!empty($method) && strtoupper($method) !== 'POST')
            @method($method)
        @endif
        <div class="card-body d-flex flex-column gap-3">
            <div>
                <label class="form-label" for="graph-title">Title</label>
                <input class="form-control" id="graph-title" name="title" type="text" value="{{ $graphForm['title'] }}" placeholder="Temperature, last 6 months">
            </div>
            <div>
                <label class="form-label" for="graph-type">Graph type</label>
                <select class="form-select" id="graph-type" name="graph_type">
                    @foreach($graphTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($graphForm['graph_type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="range-type">Range</label>
                <select class="form-select" id="range-type" name="range_type">
                    @foreach($rangeOptions as $value => $label)
                        <option value="{{ $value }}" @selected($graphForm['range_type'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label" for="bucket-size">Grouping</label>
                    <select class="form-select" id="bucket-size" name="bucket_size">
                        @foreach($bucketOptions as $value => $label)
                            <option value="{{ $value }}" @selected($graphForm['bucket_size'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label" for="aggregate">Aggregation</label>
                    <select class="form-select" id="aggregate" name="aggregate">
                        @foreach($aggregateOptions as $value => $label)
                            <option value="{{ $value }}" @selected($graphForm['aggregate'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Series</label>
                <div class="d-flex flex-column gap-2">
                    @forelse($graphableSchema as $field)
                        <label class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="schema_uids[]"
                                value="{{ $field->uid }}"
                                @checked(in_array($field->uid, $graphForm['schema_uids']))
                            >
                            <span class="form-check-label">{{ $field->name }}</span>
                            <span class="form-hint">Type: {{ $field->field_type }}</span>
                        </label>
                    @empty
                        <div class="text-secondary small">No numeric or boolean schema fields are available for charts yet.</div>
                    @endforelse
                </div>
            </div>
            <div>
                <div class="form-label">Record filters</div>
                <div class="d-flex flex-column gap-3">
                    @foreach($schema as $field)
                        <div>
                            <label class="form-label" for="graph-filter-{{ $field->uid }}">{{ $field->name }}</label>
                            <input
                                class="form-control"
                                type="{{ in_array($field->field_type, ['int', 'float']) ? 'number' : (in_array($field->field_type, ['date', 'datetime']) ? ($field->field_type === 'date' ? 'date' : 'datetime-local') : 'text') }}"
                                @if($field->field_type === 'float') step="any" @endif
                                id="graph-filter-{{ $field->uid }}"
                                name="filters[{{ $field->uid }}]"
                                value="{{ $graphForm['filters'][$field->uid] ?? '' }}"
                                placeholder="{{ in_array($field->field_type, ['int', 'float', 'bool', 'date', 'datetime']) ? 'Exact value' : 'Contains value' }}"
                            >
                            <div class="form-hint">Filter records included in the graph using this schema field.</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary" @disabled($graphableSchema->isEmpty())>{{ $submitLabel }}</button>
        </div>
    </form>
</div>
