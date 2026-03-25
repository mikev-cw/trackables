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
                <label class="form-label" for="trackable-name">Name</label>
                <input
                    class="form-control @error('name') is-invalid @enderror"
                    id="trackable-name"
                    name="name"
                    type="text"
                    value="{{ old('name', $trackable->name ?? '') }}"
                    placeholder="Fuel prices, Office climate, Water meter"
                >
                @error('name')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        </div>
    </form>
</div>
