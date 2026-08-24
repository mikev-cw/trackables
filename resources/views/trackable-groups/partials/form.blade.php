<div class="card">
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
                <label class="form-label" for="group-name">Name</label>
                <input
                    class="form-control @error('name') is-invalid @enderror"
                    id="group-name"
                    name="name"
                    type="text"
                    value="{{ old('name', $trackableGroup->name ?? '') }}"
                    placeholder="Transport"
                >
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label class="form-label" for="group-description">Description</label>
                <textarea
                    class="form-control @error('description') is-invalid @enderror"
                    id="group-description"
                    name="description"
                    rows="4"
                    placeholder="Fuel prices, refueling, flights, tickets"
                >{{ old('description', $trackableGroup->description ?? '') }}</textarea>
                @error('description')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        </div>
    </form>
</div>
