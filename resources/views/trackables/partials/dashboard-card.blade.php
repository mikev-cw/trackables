@php($lastRecord = $trackable->records_max_record_date ? \Illuminate\Support\Carbon::parse($trackable->records_max_record_date) : null)
<article class="dashboard-trackable {{ $trackable->deleted ? 'is-disabled' : '' }}">
    <span class="dashboard-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($trackable->name, 0, 1)) }}</span>
    <div class="dashboard-trackable-info">
        <a class="dashboard-trackable-name" href="{{ route('trackables.show', $trackable) }}">{{ $trackable->name }}</a>
        <div class="dashboard-trackable-meta">
            @if($trackable->deleted)<span class="dashboard-disabled-label">Disabled</span>@endif
            @if($lastRecord)
                <span title="Last record: {{ $lastRecord->format('d M Y, H:i') }}">Last record: {{ $lastRecord->diffForHumans() }}</span>
            @else
                <span>No records yet</span>
            @endif
        </div>
    </div>
    <div class="dashboard-trackable-actions">
        <a href="{{ route('trackables.statistics', $trackable) }}" class="dashboard-icon-button dashboard-statistics" aria-label="Statistics for {{ $trackable->name }}" title="Statistics">@include('trackables.partials.dashboard-icon', ['icon' => 'chart'])</a>
        @unless($trackable->deleted)
            <a href="{{ route('trackables.records.create', $trackable) }}" class="dashboard-add" aria-label="Add record to {{ $trackable->name }}" title="Add record">@include('trackables.partials.dashboard-icon', ['icon' => 'plus'])<span>Record</span></a>
        @endunless
        <div class="dropdown">
            <button type="button" class="dashboard-icon-button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="More actions for {{ $trackable->name }}">@include('trackables.partials.dashboard-icon', ['icon' => 'more'])</button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="{{ route('trackables.show', $trackable) }}">Open records</a>
                <a class="dropdown-item" href="{{ route('trackables.statistics', $trackable) }}">Statistics</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="{{ route('trackables.edit', $trackable) }}">Edit trackable</a>
                <a class="dropdown-item" href="{{ route('trackables.schema.edit', $trackable) }}">Edit schema</a>
                <a class="dropdown-item" href="{{ route('trackables.duplicate', $trackable) }}">Duplicate</a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('trackables.toggle', $trackable) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="dropdown-item {{ $trackable->deleted ? 'text-success' : 'text-danger' }}">{{ $trackable->deleted ? 'Enable trackable' : 'Disable trackable' }}</button>
                </form>
            </div>
        </div>
    </div>
</article>
