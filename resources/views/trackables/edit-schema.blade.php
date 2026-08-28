@php
    $selectedSchemaUid = old('_schema_form', session('selected_schema_uid'));
    $shouldOpenCreateModal = in_array($selectedSchemaUid, ['create', 'preset'], true);

    if ($shouldOpenCreateModal || !$schemaFields->contains('uid', $selectedSchemaUid)) {
        $selectedSchemaUid = optional($schemaFields->first())->uid;
    }

    $createValidationConfig = \App\Models\TrackableSchema::normalizeValidationConfig(
        old('field_type', 'string'),
        $shouldOpenCreateModal ? old('validation_config', []) : ['max_length' => 255]
    );
    $createValidationPreview = \App\Models\TrackableSchema::validationRuleFromConfig(old('field_type', 'string'), $createValidationConfig);

    $typeToneMap = [
        'int' => 'blue',
        'float' => 'cyan',
        'json' => 'purple',
        'string' => 'green',
        'bool' => 'yellow',
        'date' => 'orange',
        'datetime' => 'orange',
        'img' => 'pink',
        'url' => 'indigo',
        'enum' => 'teal',
        'calc' => 'red',
    ];
@endphp

<x-layout>
    <x-slot name="pretitle">Trackables</x-slot>
    <x-slot name="title">Edit Schema for {{ $trackable->name }}</x-slot>
    <x-slot name="actions">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFieldModal">
            Add field
        </button>
        <a href="{{ route('trackables.edit', $trackable->uid) }}" class="btn btn-outline-secondary">Back to trackable</a>
    </x-slot>

    <style>
        .schema-workspace {
            display: grid;
            grid-template-columns: minmax(280px, 360px) minmax(0, 1fr);
            gap: 1.25rem;
            align-items: start;
        }

        .schema-rail,
        .schema-detail {
            border: 1px solid var(--tblr-border-color);
            box-shadow: 0 18px 44px rgba(31, 41, 55, .06);
        }

        .schema-rail {
            position: sticky;
            top: 1rem;
            max-height: calc(100vh - 8rem);
            overflow: hidden;
        }

        .schema-search {
            position: relative;
        }

        .schema-search::before {
            content: "";
            position: absolute;
            left: .9rem;
            top: 50%;
            width: .75rem;
            height: .75rem;
            border: 2px solid var(--tblr-secondary);
            border-radius: 50%;
            transform: translateY(-58%);
            opacity: .72;
            pointer-events: none;
        }

        .schema-search::after {
            content: "";
            position: absolute;
            left: 1.56rem;
            top: calc(50% + .28rem);
            width: .42rem;
            height: 2px;
            border-radius: 99px;
            background: var(--tblr-secondary);
            transform: rotate(45deg);
            opacity: .72;
            pointer-events: none;
        }

        .schema-search input {
            padding-left: 2.35rem;
        }

        .schema-field-list {
            max-height: calc(100vh - 18rem);
            overflow: auto;
        }

        .schema-field-button {
            width: 100%;
            border: 0;
            border-bottom: 1px solid var(--tblr-border-color);
            background: transparent;
            color: inherit;
            cursor: pointer;
            display: flex;
            gap: .875rem;
            padding: 1rem 1.125rem;
            text-align: left;
            transition: background-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }

        .schema-field-button:hover,
        .schema-field-button:focus-visible {
            background: rgba(var(--tblr-primary-rgb), .045);
            outline: 0;
        }

        .schema-field-button.is-active {
            background: linear-gradient(90deg, rgba(var(--tblr-primary-rgb), .12), rgba(var(--tblr-primary-rgb), .035));
            box-shadow: inset 3px 0 0 var(--tblr-primary);
        }

        .schema-field-button.is-hidden {
            display: none;
        }

        .schema-type-mark {
            align-items: center;
            border-radius: 6px;
            display: inline-flex;
            flex: 0 0 2.4rem;
            font-size: .72rem;
            font-weight: 700;
            height: 2.4rem;
            justify-content: center;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .schema-field-meta {
            min-width: 0;
        }

        .schema-field-name,
        .schema-field-subtitle {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .schema-detail-pane {
            display: none;
        }

        .schema-detail-pane.is-active {
            display: block;
            animation: schemaPaneIn .18s ease-out;
        }

        .schema-empty-state {
            min-height: 28rem;
        }

        .schema-stat {
            border: 1px solid var(--tblr-border-color);
            border-radius: 8px;
            padding: .85rem 1rem;
        }

        .schema-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .schema-form-grid .is-wide {
            grid-column: 1 / -1;
        }

        .schema-validation-builder {
            border: 1px solid var(--tblr-border-color);
            border-radius: 8px;
            padding: 1rem;
        }

        .schema-validation-controls {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
        }

        .schema-validation-preview {
            background: var(--tblr-bg-surface-secondary);
            border-radius: 6px;
            color: var(--tblr-secondary);
            font-size: .8rem;
            margin-top: 1rem;
            padding: .75rem .9rem;
        }

        .schema-modal .modal-content {
            border: 0;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .22);
        }

        .schema-modal .modal-dialog {
            max-width: 760px;
        }

        .schema-preset-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .schema-preset-card {
            border: 1px solid var(--tblr-border-color);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            height: 100%;
            padding: 1rem;
        }

        .schema-preset-fields {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        @keyframes schemaPaneIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 991.98px) {
            .schema-workspace {
                grid-template-columns: 1fr;
            }

            .schema-rail {
                position: static;
                max-height: none;
            }

            .schema-field-list {
                max-height: 22rem;
            }
        }

        @media (max-width: 575.98px) {
            .schema-form-grid {
                grid-template-columns: 1fr;
            }

            .schema-validation-controls {
                grid-template-columns: 1fr;
            }

            .schema-preset-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <div class="fw-semibold">There are a few things to fix.</div>
            <div class="small">Check the highlighted fields and save again.</div>
        </div>
    @endif

    <div class="schema-workspace" id="schemaEditor" data-selected-schema="{{ $selectedSchemaUid }}" data-open-create="{{ $shouldOpenCreateModal ? '1' : '0' }}">
        <section class="card schema-rail">
            <div class="card-header border-0 pb-0">
                <div>
                    <h3 class="card-title mb-1">Fields</h3>
                    <div class="text-secondary small">{{ $schemaFields->count() }} configured for this trackable</div>
                </div>
            </div>
            <div class="card-body border-bottom">
                <div class="schema-search">
                    <input class="form-control" type="search" id="schemaFieldSearch" placeholder="Search fields" autocomplete="off">
                </div>
            </div>

            <div class="schema-field-list" id="schemaFieldList">
                @forelse($schemaFields as $field)
                    @php
                        $tone = $typeToneMap[$field->field_type] ?? 'secondary';
                        $abbreviation = str($field->field_type)->substr(0, 3)->upper();
                    @endphp
                    <button
                        type="button"
                        class="schema-field-button {{ $selectedSchemaUid === $field->uid ? 'is-active' : '' }}"
                        data-schema-select="{{ $field->uid }}"
                        data-schema-search="{{ str($field->name.' '.$field->alias.' '.$field->field_type.' '.$field->validation_rule)->lower() }}"
                    >
                        <span class="schema-type-mark bg-{{ $tone }}-lt text-{{ $tone }}">{{ $abbreviation }}</span>
                        <span class="schema-field-meta">
                            <span class="schema-field-name d-block fw-semibold">{{ $field->name }}</span>
                            <span class="schema-field-subtitle d-block text-secondary small">
                                {{ $field->alias ?: 'No alias' }} · {{ $field->field_type }}
                            </span>
                        </span>
                    </button>
                @empty
                    <div class="p-4 text-center text-secondary">
                        <div class="fw-semibold text-body mb-1">No schema fields yet</div>
                        <div class="small mb-3">Add the first field to start collecting structured records.</div>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFieldModal">Add field</button>
                    </div>
                @endforelse
            </div>

            @if($schemaFields->isNotEmpty())
                <div class="p-3 text-secondary small d-none" id="schemaNoMatches">
                    No fields match that search.
                </div>
            @endif
        </section>

        <section class="card schema-detail">
            @forelse($schemaFields as $field)
                @php
                    $tone = $typeToneMap[$field->field_type] ?? 'secondary';
                    $isSelected = $selectedSchemaUid === $field->uid;
                    $isErroredField = old('_schema_form') === $field->uid;
                    $validationConfig = $isErroredField
                        ? \App\Models\TrackableSchema::normalizeValidationConfig(old('field_type', $field->field_type), old('validation_config', []))
                        : $field->validationConfigForEditor();
                    $validationPreview = \App\Models\TrackableSchema::validationRuleFromConfig(
                        $isErroredField ? old('field_type', $field->field_type) : $field->field_type,
                        $validationConfig
                    );
                @endphp
                <div class="schema-detail-pane {{ $isSelected ? 'is-active' : '' }}" data-schema-pane="{{ $field->uid }}">
                    <form method="POST" action="{{ route('trackables.schema.update', [$trackable->uid, $field->uid]) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_schema_form" value="{{ $field->uid }}">

                        <div class="card-header border-0 pb-0">
                            <div class="d-flex flex-column flex-md-row gap-3 justify-content-between w-100">
                                <div class="min-w-0">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-{{ $tone }}-lt text-{{ $tone }}">{{ $field->field_type }}</span>
                                        <span class="text-secondary small font-monospace">{{ $field->uid }}</span>
                                    </div>
                                    <h3 class="card-title h2 mb-1">{{ $field->name }}</h3>
                                    <div class="text-secondary">Edit how this field is stored, validated, and exposed through aliases.</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <div class="schema-stat">
                                        <div class="text-secondary small">Alias</div>
                                        <div class="fw-semibold font-monospace">{{ $field->alias ?: 'none' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="schema-form-grid">
                                <div>
                                    <label class="form-label">Name</label>
                                    <input class="form-control @error('name') {{ $isErroredField ? 'is-invalid' : '' }} @enderror" name="name" type="text" value="{{ $isErroredField ? old('name') : $field->name }}">
                                    @if($isErroredField)
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label">Alias</label>
                                    <input class="form-control @error('alias') {{ $isErroredField ? 'is-invalid' : '' }} @enderror" name="alias" type="text" value="{{ $isErroredField ? old('alias') : $field->alias }}" placeholder="pump_name">
                                    @if($isErroredField)
                                        @error('alias')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label">Field type</label>
                                    <select class="form-select @error('field_type') {{ $isErroredField ? 'is-invalid' : '' }} @enderror" name="field_type" data-validation-field-type>
                                        @foreach($fieldTypeOptions as $fieldType)
                                            <option value="{{ $fieldType }}" @selected(($isErroredField ? old('field_type') : $field->field_type) === $fieldType)>{{ $fieldType }}</option>
                                        @endforeach
                                    </select>
                                    @if($isErroredField)
                                        @error('field_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label">Enum UID</label>
                                    <input class="form-control @error('enum_uid') {{ $isErroredField ? 'is-invalid' : '' }} @enderror" name="enum_uid" type="text" value="{{ $isErroredField ? old('enum_uid') : $field->enum_uid }}">
                                    @if($isErroredField)
                                        @error('enum_uid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                                <div>
                                    <label class="form-label">UID</label>
                                    <div class="form-control-plaintext font-monospace text-secondary">{{ $field->uid }}</div>
                                </div>
                                <div class="is-wide">
                                    <div class="schema-validation-builder" data-validation-builder>
                                        <div class="d-flex flex-column flex-md-row gap-2 justify-content-between mb-3">
                                            <div>
                                                <label class="form-label mb-1">Validation</label>
                                                <div class="text-secondary small">Use field-friendly options; the app generates the runtime rule.</div>
                                            </div>
                                            <label class="form-check form-switch mb-0">
                                                <input type="hidden" name="validation_config[required]" value="0">
                                                <input class="form-check-input" type="checkbox" name="validation_config[required]" value="1" data-validation-input @checked($validationConfig['required'])>
                                                <span class="form-check-label">Required</span>
                                            </label>
                                        </div>
                                        <div class="schema-validation-controls">
                                            <div data-validation-control="number">
                                                <label class="form-label">Minimum value</label>
                                                <input class="form-control @error('validation_config.min') {{ $isErroredField ? 'is-invalid' : '' }} @enderror" name="validation_config[min]" type="number" step="any" value="{{ $validationConfig['min'] }}" data-validation-input>
                                                @if($isErroredField)
                                                    @error('validation_config.min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                @endif
                                            </div>
                                            <div data-validation-control="number">
                                                <label class="form-label">Maximum value</label>
                                                <input class="form-control @error('validation_config.max') {{ $isErroredField ? 'is-invalid' : '' }} @enderror" name="validation_config[max]" type="number" step="any" value="{{ $validationConfig['max'] }}" data-validation-input>
                                                @if($isErroredField)
                                                    @error('validation_config.max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                @endif
                                            </div>
                                            <div data-validation-control="length">
                                                <label class="form-label">Max length</label>
                                                <input class="form-control @error('validation_config.max_length') {{ $isErroredField ? 'is-invalid' : '' }} @enderror" name="validation_config[max_length]" type="number" min="1" step="1" value="{{ $validationConfig['max_length'] }}" data-validation-input>
                                                @if($isErroredField)
                                                    @error('validation_config.max_length')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                @endif
                                            </div>
                                            <div data-validation-control="format">
                                                <label class="form-label">Format</label>
                                                <select class="form-select @error('validation_config.format') {{ $isErroredField ? 'is-invalid' : '' }} @enderror" name="validation_config[format]" data-validation-input>
                                                    <option value="" @selected(!$validationConfig['format'])>Any text</option>
                                                    <option value="email" @selected($validationConfig['format'] === 'email')>Email</option>
                                                    <option value="url" @selected($validationConfig['format'] === 'url')>URL</option>
                                                    <option value="uuid" @selected($validationConfig['format'] === 'uuid')>UUID</option>
                                                </select>
                                                @if($isErroredField)
                                                    @error('validation_config.format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                @endif
                                            </div>
                                        </div>
                                        <div class="schema-validation-preview">
                                            Generated rule:
                                            <span class="font-monospace" data-validation-preview>{{ $validationPreview }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="is-wide">
                                    <label class="form-label">Calc formula</label>
                                    <textarea class="form-control @error('calc_formula') {{ $isErroredField ? 'is-invalid' : '' }} @enderror" name="calc_formula" rows="5" placeholder='{"field": "value"}'>{{ $isErroredField ? old('calc_formula') : $field->calc_formula }}</textarea>
                                    @if($isErroredField)
                                        @error('calc_formula')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card-footer d-flex flex-column flex-sm-row gap-2 justify-content-between align-items-sm-center">
                            <div class="text-secondary small">Changes apply to future forms and API validation immediately after save.</div>
                            <button type="submit" class="btn btn-primary">Save field</button>
                        </div>
                    </form>
                </div>
            @empty
                <div class="card-body schema-empty-state d-flex flex-column align-items-center justify-content-center text-center">
                    <div class="mb-3">
                        <span class="avatar avatar-xl bg-primary-lt text-primary">+</span>
                    </div>
                    <h3 class="h2 mb-2">Create your first schema field</h3>
                    <p class="text-secondary mb-4 col-lg-7">Fields define the shape of every record for {{ $trackable->name }}. Start with one value you know you want to track.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFieldModal">Add field</button>
                </div>
            @endforelse
        </section>
    </div>

    <div class="modal modal-blur fade schema-modal" id="addFieldModal" tabindex="-1" aria-labelledby="addFieldModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="modal-title h2" id="addFieldModalLabel">Add field</div>
                        <div class="text-secondary small">Create one field manually, or insert a Trackables preset.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ old('_schema_form') === 'preset' ? '' : 'active' }}" id="schema-single-tab" data-bs-toggle="tab" data-bs-target="#schemaSinglePane" type="button" role="tab" aria-controls="schemaSinglePane" aria-selected="{{ old('_schema_form') === 'preset' ? 'false' : 'true' }}">Single field</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ old('_schema_form') === 'preset' ? 'active' : '' }}" id="schema-presets-tab" data-bs-toggle="tab" data-bs-target="#schemaPresetsPane" type="button" role="tab" aria-controls="schemaPresetsPane" aria-selected="{{ old('_schema_form') === 'preset' ? 'true' : 'false' }}">Presets</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade {{ old('_schema_form') === 'preset' ? '' : 'show active' }}" id="schemaSinglePane" role="tabpanel" aria-labelledby="schema-single-tab" tabindex="0">
                            <form id="singleFieldForm" method="POST" action="{{ route('trackables.schema.store', $trackable->uid) }}">
                                @csrf
                                <input type="hidden" name="_schema_form" value="create">

                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Name</label>
                                        <input class="form-control @error('name') {{ old('_schema_form') === 'create' ? 'is-invalid' : '' }} @enderror" name="name" type="text" value="{{ old('_schema_form') === 'create' ? old('name') : '' }}" autofocus>
                                        @if(old('_schema_form') === 'create')
                                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Alias</label>
                                        <input class="form-control @error('alias') {{ old('_schema_form') === 'create' ? 'is-invalid' : '' }} @enderror" name="alias" type="text" value="{{ old('_schema_form') === 'create' ? old('alias') : '' }}" placeholder="pump_name">
                                        @if(old('_schema_form') === 'create')
                                            @error('alias')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Field type</label>
                                        <select class="form-select @error('field_type') {{ old('_schema_form') === 'create' ? 'is-invalid' : '' }} @enderror" name="field_type" data-validation-field-type>
                                            @foreach($fieldTypeOptions as $fieldType)
                                                <option value="{{ $fieldType }}" @selected(old('field_type', 'string') === $fieldType)>{{ $fieldType }}</option>
                                            @endforeach
                                        </select>
                                        @if(old('_schema_form') === 'create')
                                            @error('field_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Enum UID</label>
                                        <input class="form-control @error('enum_uid') {{ old('_schema_form') === 'create' ? 'is-invalid' : '' }} @enderror" name="enum_uid" type="text" value="{{ old('_schema_form') === 'create' ? old('enum_uid') : '' }}">
                                        @if(old('_schema_form') === 'create')
                                            @error('enum_uid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                    <div class="col-12">
                                        <div class="schema-validation-builder" data-validation-builder>
                                            <div class="d-flex flex-column flex-md-row gap-2 justify-content-between mb-3">
                                                <div>
                                                    <label class="form-label mb-1">Validation</label>
                                                    <div class="text-secondary small">Choose the constraints people should understand at a glance.</div>
                                                </div>
                                                <label class="form-check form-switch mb-0">
                                                    <input type="hidden" name="validation_config[required]" value="0">
                                                    <input class="form-check-input" type="checkbox" name="validation_config[required]" value="1" data-validation-input @checked($createValidationConfig['required'])>
                                                    <span class="form-check-label">Required</span>
                                                </label>
                                            </div>
                                            <div class="schema-validation-controls">
                                                <div data-validation-control="number">
                                                    <label class="form-label">Minimum value</label>
                                                    <input class="form-control @error('validation_config.min') {{ old('_schema_form') === 'create' ? 'is-invalid' : '' }} @enderror" name="validation_config[min]" type="number" step="any" value="{{ $createValidationConfig['min'] }}" data-validation-input>
                                                    @if(old('_schema_form') === 'create')
                                                        @error('validation_config.min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    @endif
                                                </div>
                                                <div data-validation-control="number">
                                                    <label class="form-label">Maximum value</label>
                                                    <input class="form-control @error('validation_config.max') {{ old('_schema_form') === 'create' ? 'is-invalid' : '' }} @enderror" name="validation_config[max]" type="number" step="any" value="{{ $createValidationConfig['max'] }}" data-validation-input>
                                                    @if(old('_schema_form') === 'create')
                                                        @error('validation_config.max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    @endif
                                                </div>
                                                <div data-validation-control="length">
                                                    <label class="form-label">Max length</label>
                                                    <input class="form-control @error('validation_config.max_length') {{ old('_schema_form') === 'create' ? 'is-invalid' : '' }} @enderror" name="validation_config[max_length]" type="number" min="1" step="1" value="{{ $createValidationConfig['max_length'] }}" data-validation-input>
                                                    @if(old('_schema_form') === 'create')
                                                        @error('validation_config.max_length')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    @endif
                                                </div>
                                                <div data-validation-control="format">
                                                    <label class="form-label">Format</label>
                                                    <select class="form-select @error('validation_config.format') {{ old('_schema_form') === 'create' ? 'is-invalid' : '' }} @enderror" name="validation_config[format]" data-validation-input>
                                                        <option value="" @selected(!$createValidationConfig['format'])>Any text</option>
                                                        <option value="email" @selected($createValidationConfig['format'] === 'email')>Email</option>
                                                        <option value="url" @selected($createValidationConfig['format'] === 'url')>URL</option>
                                                        <option value="uuid" @selected($createValidationConfig['format'] === 'uuid')>UUID</option>
                                                    </select>
                                                    @if(old('_schema_form') === 'create')
                                                        @error('validation_config.format')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="schema-validation-preview">
                                                Generated rule:
                                                <span class="font-monospace" data-validation-preview>{{ $createValidationPreview }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Calc formula</label>
                                        <textarea class="form-control @error('calc_formula') {{ old('_schema_form') === 'create' ? 'is-invalid' : '' }} @enderror" name="calc_formula" rows="4">{{ old('_schema_form') === 'create' ? old('calc_formula') : '' }}</textarea>
                                        @if(old('_schema_form') === 'create')
                                            @error('calc_formula')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        @endif
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade {{ old('_schema_form') === 'preset' ? 'show active' : '' }}" id="schemaPresetsPane" role="tabpanel" aria-labelledby="schema-presets-tab" tabindex="0">
                            @error('preset')
                                <div class="alert alert-danger" role="alert">{{ $message }}</div>
                            @enderror

                            <div class="schema-preset-grid">
                                @foreach($schemaPresets as $presetKey => $preset)
                                    <form class="schema-preset-card" method="POST" action="{{ route('trackables.schema.presets.store', $trackable->uid) }}">
                                        @csrf
                                        <input type="hidden" name="_schema_form" value="preset">
                                        <input type="hidden" name="preset" value="{{ $presetKey }}">
                                        <div>
                                            <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                                                <h3 class="h3 mb-0">{{ $preset['name'] }}</h3>
                                                <span class="badge bg-primary-lt text-primary">{{ count($preset['fields']) }} fields</span>
                                            </div>
                                            <div class="text-secondary small">{{ $preset['description'] }}</div>
                                        </div>
                                        <div class="schema-preset-fields">
                                            @foreach($preset['fields'] as $presetField)
                                                <span class="badge bg-secondary-lt text-secondary">{{ $presetField['name'] }}</span>
                                            @endforeach
                                        </div>
                                        <div class="mt-auto">
                                            <button type="submit" class="btn btn-primary w-100">Add preset</button>
                                        </div>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary {{ old('_schema_form') === 'preset' ? 'd-none' : '' }}" form="singleFieldForm" id="singleFieldSubmitButton">Add field</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editor = document.getElementById('schemaEditor');
            const searchInput = document.getElementById('schemaFieldSearch');
            const noMatches = document.getElementById('schemaNoMatches');
            const addFieldModal = document.getElementById('addFieldModal');
            const singleFieldSubmitButton = document.getElementById('singleFieldSubmitButton');
            const fieldButtons = [...document.querySelectorAll('[data-schema-select]')];
            const panes = [...document.querySelectorAll('[data-schema-pane]')];
            const typeRules = {
                int: 'integer',
                float: 'numeric',
                json: 'json',
                bool: 'boolean',
                date: 'date',
                datetime: 'date',
                url: 'url',
            };
            const numberTypes = ['int', 'float'];
            const lengthTypes = ['string', 'url', 'img', 'json'];

            const selectField = (uid) => {
                fieldButtons.forEach((button) => {
                    button.classList.toggle('is-active', button.dataset.schemaSelect === uid);
                });

                panes.forEach((pane) => {
                    pane.classList.toggle('is-active', pane.dataset.schemaPane === uid);
                });
            };

            fieldButtons.forEach((button) => {
                button.addEventListener('click', () => selectField(button.dataset.schemaSelect));
            });

            searchInput?.addEventListener('input', () => {
                const query = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                fieldButtons.forEach((button) => {
                    const isMatch = !query || button.dataset.schemaSearch.includes(query);
                    button.classList.toggle('is-hidden', !isMatch);
                    visibleCount += isMatch ? 1 : 0;
                });

                noMatches?.classList.toggle('d-none', visibleCount !== 0);
            });

            document.querySelectorAll('form').forEach((form) => {
                const fieldTypeInput = form.querySelector('[data-validation-field-type]');
                const builder = form.querySelector('[data-validation-builder]');

                if (!fieldTypeInput || !builder) {
                    return;
                }

                const preview = builder.querySelector('[data-validation-preview]');
                const requiredInput = builder.querySelector('input[name="validation_config[required]"][type="checkbox"]');
                const minInput = builder.querySelector('input[name="validation_config[min]"]');
                const maxInput = builder.querySelector('input[name="validation_config[max]"]');
                const maxLengthInput = builder.querySelector('input[name="validation_config[max_length]"]');
                const formatInput = builder.querySelector('select[name="validation_config[format]"]');
                const controls = [...builder.querySelectorAll('[data-validation-control]')];

                const setControlVisibility = (name, isVisible) => {
                    controls
                        .filter((control) => control.dataset.validationControl === name)
                        .forEach((control) => {
                            control.classList.toggle('d-none', !isVisible);
                        });
                };

                const updatePreview = () => {
                    const fieldType = fieldTypeInput.value || 'string';
                    const rules = [requiredInput?.checked ? 'required' : 'nullable'];
                    const usesNumber = numberTypes.includes(fieldType);
                    const usesLength = lengthTypes.includes(fieldType);
                    const usesFormat = fieldType === 'string';

                    setControlVisibility('number', usesNumber);
                    setControlVisibility('length', usesLength);
                    setControlVisibility('format', usesFormat);

                    rules.push(typeRules[fieldType] || 'string');

                    if (usesNumber && minInput?.value !== '') {
                        rules.push(`min:${minInput.value}`);
                    }

                    if (usesNumber && maxInput?.value !== '') {
                        rules.push(`max:${maxInput.value}`);
                    }

                    if (usesLength && maxLengthInput?.value !== '') {
                        rules.push(`max:${maxLengthInput.value}`);
                    }

                    if (usesFormat && formatInput?.value) {
                        rules.push(formatInput.value);
                    }

                    if (preview) {
                        preview.textContent = [...new Set(rules)].join('|');
                    }
                };

                form.querySelectorAll('[data-validation-input], [data-validation-field-type]').forEach((input) => {
                    input.addEventListener('input', updatePreview);
                    input.addEventListener('change', updatePreview);
                });

                updatePreview();
            });

            document.querySelectorAll('#addFieldModal [data-bs-toggle="tab"]').forEach((tab) => {
                tab.addEventListener('shown.bs.tab', (event) => {
                    singleFieldSubmitButton?.classList.toggle(
                        'd-none',
                        event.target.dataset.bsTarget !== '#schemaSinglePane'
                    );
                });
            });

            if (editor?.dataset.selectedSchema) {
                selectField(editor.dataset.selectedSchema);
            }

            if (editor?.dataset.openCreate === '1' && addFieldModal) {
                bootstrap.Modal.getOrCreateInstance(addFieldModal).show();
            }
        });
    </script>
</x-layout>
