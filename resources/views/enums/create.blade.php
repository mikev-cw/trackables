<x-layout>
    <x-slot name="pretitle">Lists</x-slot>
    <x-slot name="title">Create list</x-slot>
    <x-slot name="actions">
        <a href="{{ route('enums.index') }}" class="btn btn-outline-secondary">Back to lists</a>
    </x-slot>

    @php
        $initialValues = old('values', [['key' => '', 'label' => '', 'description' => '']]);
    @endphp

    <div class="card">
        <form method="POST" action="{{ route('enums.store') }}">
            @csrf
            <div class="card-body d-flex flex-column gap-4">
                <div>
                    <label class="form-label" for="enum-name">Name</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="enum-name" name="name" type="text" value="{{ old('name') }}" placeholder="Order status">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <label class="form-label mb-0">Initial values</label>
                            <div class="form-hint">Keys become stable after creation; labels and descriptions remain editable.</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-add-value>Add value</button>
                    </div>
                    @error('values')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                    <div class="table-responsive border rounded">
                        <table class="table table-vcenter mb-0">
                            <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Label</th>
                                    <th>Description</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody data-value-rows>
                                @foreach($initialValues as $index => $value)
                                    <tr data-value-row>
                                        <td>
                                            <input class="form-control @error("values.$index.key") is-invalid @enderror" name="values[{{ $index }}][key]" value="{{ $value['key'] ?? '' }}" placeholder="in_progress">
                                            @error("values.$index.key")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <input class="form-control @error("values.$index.label") is-invalid @enderror" name="values[{{ $index }}][label]" value="{{ $value['label'] ?? '' }}" placeholder="In progress">
                                            @error("values.$index.label")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <input class="form-control @error("values.$index.description") is-invalid @enderror" name="values[{{ $index }}][description]" value="{{ $value['description'] ?? '' }}" placeholder="Optional context">
                                            @error("values.$index.description")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </td>
                                        <td><button class="btn btn-sm btn-outline-danger" type="button" data-remove-value aria-label="Remove value">Remove</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Create list</button>
            </div>
        </form>
    </div>

    <template id="enum-value-row-template">
        <tr data-value-row>
            <td><input class="form-control" name="values[__INDEX__][key]" placeholder="in_progress"></td>
            <td><input class="form-control" name="values[__INDEX__][label]" placeholder="In progress"></td>
            <td><input class="form-control" name="values[__INDEX__][description]" placeholder="Optional context"></td>
            <td><button class="btn btn-sm btn-outline-danger" type="button" data-remove-value aria-label="Remove value">Remove</button></td>
        </tr>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = document.querySelector('[data-value-rows]');
            const template = document.getElementById('enum-value-row-template');
            let nextIndex = {{ count($initialValues) }};

            const removeRow = (button) => {
                const row = button.closest('[data-value-row]');

                if (rows.children.length === 1) {
                    row.querySelectorAll('input').forEach((input) => input.value = '');
                    return;
                }

                row.remove();
            };

            document.querySelector('[data-add-value]').addEventListener('click', () => {
                rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex));
                nextIndex++;
            });

            rows.addEventListener('click', (event) => {
                const button = event.target.closest('[data-remove-value]');

                if (button) {
                    removeRow(button);
                }
            });
        });
    </script>
</x-layout>
