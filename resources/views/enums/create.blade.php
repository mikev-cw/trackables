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
                                    <th>Label</th>
                                    <th>Key</th>
                                    <th>Description</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody data-value-rows>
                                @foreach($initialValues as $index => $value)
                                    <tr data-value-row>
                                        <td>
                                            <input class="form-control @error("values.$index.label") is-invalid @enderror" name="values[{{ $index }}][label]" v-model="label" maxlength="255" value="{{ $value['label'] ?? '' }}" placeholder="In progress">
                                            @error("values.$index.label")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                        </td>
                                        <td>
                                            <input class="form-control @error("values.$index.key") is-invalid @enderror" name="values[{{ $index }}][key]" v-model="key" v-on:input="editKey" maxlength="255" value="{{ $value['key'] ?? '' }}" placeholder="in_progress">
                                            @error("values.$index.key")<div class="invalid-feedback">{{ $message }}</div>@enderror
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
            <td><input class="form-control" name="values[__INDEX__][label]" v-model="label" maxlength="255" placeholder="In progress"></td>
            <td><input class="form-control" name="values[__INDEX__][key]" v-model="key" v-on:input="editKey" maxlength="255" placeholder="in_progress"></td>
            <td><input class="form-control" name="values[__INDEX__][description]" placeholder="Optional context"></td>
            <td><button class="btn btn-sm btn-outline-danger" type="button" data-remove-value aria-label="Remove value">Remove</button></td>
        </tr>
    </template>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = document.querySelector('[data-value-rows]');
            const template = document.getElementById('enum-value-row-template');
            const rowApps = new WeakMap();
            let nextIndex = {{ count($initialValues) ? max(array_keys($initialValues)) + 1 : 0 }};

            const mountRow = (row) => {
                const initialLabel = row.querySelector('[name$="[label]"]').value;
                const initialKey = row.querySelector('[name$="[key]"]').value;
                let timer;
                let version = 0;

                const cancelPreview = () => {
                    clearTimeout(timer);
                    version++;
                };

                const app = Vue.createApp({
                    data: () => ({ label: initialLabel, key: initialKey, customKey: initialKey !== '' }),
                    watch: {
                        label() { this.previewKey(); },
                    },
                    mounted() { this.previewKey(); },
                    beforeUnmount() { cancelPreview(); },
                    methods: {
                        editKey(event) {
                            this.customKey = event.target.value !== '';
                            this.previewKey();
                        },
                        reset() {
                            this.label = '';
                            this.key = '';
                            this.customKey = false;
                            cancelPreview();
                        },
                        previewKey() {
                            cancelPreview();
                            const currentVersion = version;
                            if (this.customKey) return;
                            this.key = '';
                            if (!this.label.trim()) return;

                            timer = setTimeout(async () => {
                                const url = new URL(@json(route('enums.key-preview')));
                                url.searchParams.set('label', this.label);
                                try {
                                    const response = await fetch(url, { headers: { Accept: 'application/json' } });
                                    if (!response.ok) return;
                                    const preview = await response.json();
                                    if (version === currentVersion) this.key = preview.key;
                                } catch {
                                    // Blank keys are generated from the label when saving.
                                }
                            }, 150);
                        },
                    },
                });

                rowApps.set(row, { app, state: app.mount(row) });
            };

            rows.querySelectorAll('[data-value-row]').forEach(mountRow);

            const removeRow = (button) => {
                const row = button.closest('[data-value-row]');

                if (rows.children.length === 1) {
                    rowApps.get(row).state.reset();
                    row.querySelectorAll('input').forEach((input) => input.value = '');
                    return;
                }

                rowApps.get(row).app.unmount();
                rowApps.delete(row);
                row.remove();
            };

            document.querySelector('[data-add-value]').addEventListener('click', () => {
                rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex));
                mountRow(rows.lastElementChild);
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
