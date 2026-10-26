<x-layout>
    <x-slot name="pretitle">Your workspace</x-slot>
    <x-slot name="title">Trackables</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables.create') }}" class="btn btn-primary">@include('trackables.partials.dashboard-icon', ['icon' => 'plus'])<span class="ms-2">New trackable</span></a>
    </x-slot>
    <x-slot name="script">js/dashboard.js</x-slot>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    <div class="dashboard" data-dashboard data-default-view="{{ $activeCount + $disabledCount > 24 ? 'list' : 'grid' }}" data-user="{{ auth()->id() }}">
        <div class="dashboard-intro">
            <p>A little clarity for everything you track.</p>
            <span><strong>{{ number_format($activeCount) }}</strong> active trackables <span class="dashboard-dot">·</span> {{ $groups->count() }} {{ \Illuminate\Support\Str::plural('group', $groups->count()) }}</span>
        </div>
        <form action="{{ route('dashboard') }}" method="GET" class="dashboard-toolbar" data-dashboard-filters role="search">
            <input type="hidden" name="group" value="{{ $selectedGroup }}">
            <div class="dashboard-search">
                @include('trackables.partials.dashboard-icon', ['icon' => 'search'])
                <input type="search" name="q" value="{{ $search }}" placeholder="Find a trackable…" aria-label="Search trackables by name or alias" maxlength="255" autocomplete="off">
                <span class="dashboard-search-hint">Search your workspace</span>
            </div>
            <label class="dashboard-filter"><span class="visually-hidden">Status</span>
                <select name="status" aria-label="Trackable status">
                    <option value="active" @selected($status === 'active')>Active</option>
                    <option value="disabled" @selected($status === 'disabled')>Disabled ({{ $disabledCount }})</option>
                    <option value="all" @selected($status === 'all')>All statuses</option>
                </select>
            </label>
            <label class="dashboard-filter"><span class="visually-hidden">Sort within groups</span>
                <select name="sort" aria-label="Sort within groups">
                    <option value="recent" @selected($sort === 'recent')>Recently updated</option>
                    <option value="record" @selected($sort === 'record')>Latest record</option>
                    <option value="name" @selected($sort === 'name')>Name A–Z</option>
                </select>
            </label>
            <input type="hidden" name="per_page" value="{{ $perPage }}">
            <button type="submit" class="btn btn-primary dashboard-apply">Apply</button>
            <div class="dashboard-view-switch" role="group" aria-label="Display style">
                <button type="button" data-dashboard-view="grid" aria-label="Card view" aria-pressed="true">@include('trackables.partials.dashboard-icon', ['icon' => 'grid'])</button>
                <button type="button" data-dashboard-view="list" aria-label="List view" aria-pressed="false">@include('trackables.partials.dashboard-icon', ['icon' => 'list'])</button>
            </div>
        </form>

        <div class="dashboard-workspace {{ $groups->isEmpty() ? 'without-groups' : '' }}">
            @if($groups->isNotEmpty())
                <aside class="dashboard-sidebar" aria-label="Filter by group">
                    <div class="dashboard-sidebar-title">Collections <a href="{{ route('trackable-groups.index') }}" title="Manage groups">Manage</a></div>
                    <nav class="dashboard-group-nav">
                        <a data-dashboard-link href="{{ route('dashboard', array_merge(request()->except(['page', 'group']), ['group' => ''])) }}" @if($selectedGroup === '') aria-current="page" @endif>
                            @include('trackables.partials.dashboard-icon', ['icon' => 'grid'])<span>All trackables</span><small>{{ $allCount }}</small>
                        </a>
                        @foreach($groups as $group)
                            <a data-dashboard-link href="{{ route('dashboard', array_merge(request()->except('page'), ['group' => $group->uid])) }}" @if($selectedGroup === $group->uid) aria-current="page" @endif title="{{ $group->name }}">
                                @include('trackables.partials.dashboard-icon', ['icon' => 'folder'])<span>{{ $group->name }}</span><small>{{ $groupCounts[$group->uid] ?? 0 }}</small>
                            </a>
                        @endforeach
                        <a data-dashboard-link href="{{ route('dashboard', array_merge(request()->except('page'), ['group' => 'ungrouped'])) }}" @if($selectedGroup === 'ungrouped') aria-current="page" @endif>
                            @include('trackables.partials.dashboard-icon', ['icon' => 'folder'])<span>Ungrouped</span><small>{{ $ungroupedCount }}</small>
                        </a>
                    </nav>
                </aside>
            @endif

            <main class="dashboard-main" id="dashboard-results">
                <div class="dashboard-results-heading">
                    <div>
                        <h3>{{ $selectedGroup === 'ungrouped' ? 'Ungrouped' : ($groups->firstWhere('uid', $selectedGroup)?->name ?? 'All trackables') }}</h3>
                        <span class="text-secondary small" role="status" aria-live="polite">{{ number_format($list->total()) }} {{ \Illuminate\Support\Str::plural('trackable', $list->total()) }}{{ $search !== '' ? ' matching “'.$search.'”' : '' }}</span>
                    </div>
                    @if($groups->isNotEmpty() && $list->isNotEmpty())
                        <button class="dashboard-text-button" type="button" data-dashboard-collapse>Collapse groups</button>
                    @endif
                </div>

                @if($list->isEmpty())
                    <div class="dashboard-empty">
                        <span class="dashboard-empty-icon">@include('trackables.partials.dashboard-icon', ['icon' => ($activeCount + $disabledCount === 0 ? 'plus' : 'search')])</span>
                        @if($activeCount + $disabledCount === 0)
                            <h3>Make room for a new habit.</h3>
                            <p>Health, home, or something entirely your own.<br>Create your first trackable and start collecting what matters.</p>
                            <a href="{{ route('trackables.create') }}" class="btn btn-primary">Create your first trackable</a>
                        @else
                            <h3>{{ $status === 'active' && $activeCount === 0 ? 'A fresh start awaits' : 'No trackables found' }}</h3>
                            <p>{{ $status === 'active' && $activeCount === 0 ? 'Your disabled trackables are tucked away. You can enable them whenever you’re ready.' : 'Try another search, group, or status to find what you’re looking for.' }}</p>
                            <a data-dashboard-link href="{{ route('dashboard', $activeCount === 0 ? ['status' => 'disabled'] : []) }}" class="btn btn-outline-primary">{{ $activeCount === 0 ? 'View disabled trackables' : 'Clear filters' }}</a>
                        @endif
                    </div>
                @else
                    @foreach($dashboardSections as $section)
                        <section class="dashboard-section">
                            @if($groups->isNotEmpty())
                                <button class="dashboard-group-bar" type="button" data-bs-toggle="collapse" data-bs-target="#dashboard-group-{{ $section['key'] }}" data-dashboard-group-key="{{ $section['key'] === 'ungrouped' ? 'ungrouped' : 'group-'.$section['key'] }}" aria-controls="dashboard-group-{{ $section['key'] }}" aria-expanded="true">
                                    @include('trackables.partials.dashboard-icon', ['icon' => 'chevron'])
                                    <span class="dashboard-section-title">{{ $section['group']?->name ?? 'Ungrouped' }}</span>
                                    <span class="dashboard-section-count">{{ $section['trackables']->count() }} on this page</span>
                                    @if($section['group']?->description)<span class="dashboard-section-description">{{ $section['group']->description }}</span>@endif
                                </button>
                            @endif
                            <div class="collapse show" id="dashboard-group-{{ $section['key'] }}">
                                <div class="dashboard-grid">
                                    @foreach($section['trackables'] as $trackable)
                                        @include('trackables.partials.dashboard-card', ['trackable' => $trackable])
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    @endforeach
                    <div class="dashboard-pagination">
                        <label class="dashboard-filter">Per page
                            <select data-dashboard-per-page aria-label="Trackables per page">
                                @foreach([24, 48, 96] as $size)<option value="{{ $size }}" @selected($perPage === $size)>{{ $size }}</option>@endforeach
                            </select>
                        </label>
                        <div data-dashboard-pagination>{{ $list->links() }}</div>
                    </div>
                @endif
            </main>
        </div>
    </div>
</x-layout>
