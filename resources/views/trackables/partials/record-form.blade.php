<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $cardTitle }}</h3>
    </div>
    <form method="POST" action="{{ $action }}">
        @csrf
        @if (!empty($method) && strtoupper($method) !== 'POST')
            @method($method)
        @endif
        <div class="card-body">
            <div class="row g-4">
                @if(!empty($showRecordDateInput))
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="record-date">Measurement date and time</label>
                        <input
                            class="form-control @error('record_date') is-invalid @enderror"
                            type="datetime-local"
                            id="record-date"
                            name="record_date"
                            value="{{ old('record_date', $recordDateValue ?? now()->format('Y-m-d\\TH:i')) }}"
                        >
                        <div class="form-hint">Leave as is to use the current moment, or enter a past measurement time.</div>
                        @error('record_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @endif
                @forelse($schema as $field)
                    @php
                        $fieldValue = old($field->uid, $recordValues[$field->uid] ?? '');
                    @endphp
                    <div class="col-12 {{ in_array($field->field_type, ['json', 'string', 'calc']) ? '' : 'col-md-6' }}">
                        <label class="form-label" for="field-{{ $field->uid }}">{{ $field->name }}</label>

                        @switch($field->field_type)
                            @case('bool')
                                <input type="hidden" name="{{ $field->uid }}" value="0">
                                <label class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="field-{{ $field->uid }}"
                                        name="{{ $field->uid }}"
                                        value="1"
                                        @checked((string) $fieldValue === '1')
                                    >
                                    <span class="form-check-label">Enabled</span>
                                </label>
                                @break

                            @case('int')
                            @case('float')
                                <input
                                    class="form-control @error($field->uid) is-invalid @enderror"
                                    type="number"
                                    step="{{ $field->field_type === 'int' ? '1' : 'any' }}"
                                    id="field-{{ $field->uid }}"
                                    name="{{ $field->uid }}"
                                    value="{{ $fieldValue }}"
                                >
                                @break

                            @case('date')
                                <input
                                    class="form-control @error($field->uid) is-invalid @enderror"
                                    type="date"
                                    id="field-{{ $field->uid }}"
                                    name="{{ $field->uid }}"
                                    value="{{ $fieldValue }}"
                                >
                                @break

                            @case('datetime')
                                <input
                                    class="form-control @error($field->uid) is-invalid @enderror"
                                    type="datetime-local"
                                    id="field-{{ $field->uid }}"
                                    name="{{ $field->uid }}"
                                    value="{{ $fieldValue }}"
                                >
                                @break

                            @case('url')
                                <input
                                    class="form-control @error($field->uid) is-invalid @enderror"
                                    type="url"
                                    id="field-{{ $field->uid }}"
                                    name="{{ $field->uid }}"
                                    value="{{ $fieldValue }}"
                                >
                                @break

                            @case('json')
                                <textarea
                                    class="form-control @error($field->uid) is-invalid @enderror"
                                    id="field-{{ $field->uid }}"
                                    name="{{ $field->uid }}"
                                    rows="5"
                                    placeholder='{"key":"value"}'
                                >{{ $fieldValue }}</textarea>
                                @break

                            @default
                                <input
                                    class="form-control @error($field->uid) is-invalid @enderror"
                                    type="text"
                                    id="field-{{ $field->uid }}"
                                    name="{{ $field->uid }}"
                                    value="{{ $fieldValue }}"
                                >
                        @endswitch

                        <div class="form-hint">
                            Type: {{ $field->field_type ?: 'string' }}
                            @if ($field->validation_rule)
                                | Validation: {{ $field->validation_rule }}
                            @endif
                        </div>
                        @error($field->uid)
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-warning mb-0" role="alert">
                            This trackable has no schema fields yet, so a record cannot be saved.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <span class="text-secondary">{{ $footerText }}</span>
            <button type="submit" class="btn btn-primary" @disabled($schema->isEmpty())>{{ $submitLabel }}</button>
        </div>
    </form>
</div>
