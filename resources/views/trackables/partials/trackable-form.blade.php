<div class="card" @if ($autoAlias ?? false) id="trackable-create-form" data-alias-preview-url="{{ route('trackables.alias-preview') }}" @endif>
    <div class="card-header">
        <h3 class="card-title">{{ $cardTitle }}</h3>
    </div>
    <form method="POST" action="{{ $action }}">
        @csrf
        @if (!empty($method) && strtoupper($method) !== 'POST')
            @method($method)
        @endif
        <div class="card-body d-flex flex-column gap-3">
            <div>
                <label class="form-label" for="trackable-name">Name</label>
                <input
                    class="form-control @error('name') is-invalid @enderror"
                    id="trackable-name"
                    name="name"
                    @if ($autoAlias ?? false) v-model="name" @endif
                    type="text"
                    maxlength="255"
                    value="{{ old('name', $trackable->name ?? '') }}"
                    placeholder="Fuel prices, Office climate, Water meter"
                >
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label class="form-label" for="trackable-alias">Alias</label>
                <input
                    class="form-control @error('alias') is-invalid @enderror"
                    id="trackable-alias"
                    name="alias"
                    @if ($autoAlias ?? false) v-model="alias" v-on:input="editAlias" @endif
                    type="text"
                    maxlength="255"
                    value="{{ old('alias', $trackable->alias ?? '') }}"
                    placeholder="fuel_prices"
                >
                <div class="form-hint">Readable identifier for API routes and integrations.</div>
                @error('alias')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label class="form-label" for="trackable-group">Group</label>
                <select
                    class="form-select @error('group_uid') is-invalid @enderror"
                    id="trackable-group"
                    name="group_uid"
                >
                    <option value="">No group</option>
                    @foreach (($groups ?? collect()) as $group)
                        <option
                            value="{{ $group->uid }}"
                            @selected(old('group_uid', $trackable->group_uid ?? '') === $group->uid)
                        >
                            {{ $group->name }}{{ $group->deleted ? ' (disabled)' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="form-hint">Optional dashboard grouping for related trackables.</div>
                @error('group_uid')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        </div>
    </form>
</div>
