<x-layout>
    <x-slot name="pretitle">Trackables</x-slot>
    <x-slot name="title">Edit Schema for {{ $trackable->name }}</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables.edit', $trackable->uid) }}" class="btn btn-outline-secondary">Back to trackable</a>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Add Field</h3>
                </div>
                <form method="POST" action="{{ route('trackables.schema.store', $trackable->uid) }}">
                    @csrf
                    <div class="card-body d-flex flex-column gap-3">
                        <div>
                            <label class="form-label">Name</label>
                            <input class="form-control" name="name" type="text" value="{{ old('name') }}">
                        </div>
                        <div>
                            <label class="form-label">Alias</label>
                            <input class="form-control" name="alias" type="text" value="{{ old('alias') }}" placeholder="pump_name">
                        </div>
                        <div>
                            <label class="form-label">Field type</label>
                            <select class="form-select" name="field_type">
                                @foreach($fieldTypeOptions as $fieldType)
                                    <option value="{{ $fieldType }}" @selected(old('field_type') === $fieldType)>{{ $fieldType }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Validation rule</label>
                            <input class="form-control" name="validation_rule" type="text" value="{{ old('validation_rule', 'nullable|string') }}">
                        </div>
                        <div>
                            <label class="form-label">Enum UID</label>
                            <input class="form-control" name="enum_uid" type="text" value="{{ old('enum_uid') }}">
                        </div>
                        <div>
                            <label class="form-label">Calc formula</label>
                            <textarea class="form-control" name="calc_formula" rows="3">{{ old('calc_formula') }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Add field</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="d-flex flex-column gap-4">
                @foreach($schemaFields as $field)
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ $field->name }}</h3>
                        </div>
                        <form method="POST" action="{{ route('trackables.schema.update', [$trackable->uid, $field->uid]) }}">
                            @csrf
                            @method('PUT')
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Name</label>
                                        <input class="form-control" name="name" type="text" value="{{ old('name', $field->name) }}">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Alias</label>
                                        <input class="form-control" name="alias" type="text" value="{{ old('alias', $field->alias) }}">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Field type</label>
                                        <select class="form-select" name="field_type">
                                            @foreach($fieldTypeOptions as $fieldType)
                                                <option value="{{ $fieldType }}" @selected(old('field_type', $field->field_type) === $fieldType)>{{ $fieldType }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Validation rule</label>
                                        <input class="form-control" name="validation_rule" type="text" value="{{ old('validation_rule', $field->validation_rule) }}">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Enum UID</label>
                                        <input class="form-control" name="enum_uid" type="text" value="{{ old('enum_uid', $field->enum_uid) }}">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">UID</label>
                                        <input class="form-control" type="text" value="{{ $field->uid }}" readonly>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Calc formula</label>
                                        <textarea class="form-control" name="calc_formula" rows="3">{{ old('calc_formula', $field->calc_formula) }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">Save field</button>
                            </div>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layout>
