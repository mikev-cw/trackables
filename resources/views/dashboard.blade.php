<x-layout>
    <x-slot name="pretitle">Dashboard</x-slot>
    <x-slot name="title">Your Trackables</x-slot>
    <x-slot name="actions">
        <a href="{{ route('trackables.create') }}" class="btn btn-primary">
            New trackable
        </a>
        <a href="{{ route('trackable-groups.index') }}" class="btn btn-outline-primary">
            Manage groups
        </a>
        <a href="{{ route('trackables_index') }}" class="btn btn-outline-primary">
            Refresh list
        </a>
    </x-slot>

    @isset($list)
        @if($list->isEmpty() && empty($groupsConfigured))
            <div class="card">
                <div class="card-body text-center py-5">
                    <h3 class="card-title mb-2">No trackables yet</h3>
                    <p class="text-secondary mb-0">
                        Once you start creating trackables, they will appear here with direct links to records and statistics.
                    </p>
                </div>
            </div>
        @else
            @if(empty($groupsConfigured))
                <div class="row row-cards">
                    @foreach ($list as $trackable)
                        <div class="col-12 col-md-6 col-xl-4">
                            @include('trackables.partials.dashboard-card', ['trackable' => $trackable])
                        </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    {{ $list->links() }}
                </div>
            @else
                <style>
                    .dashboard-group-bar {
                        text-align: left;
                    }

                    .dashboard-group-arrow {
                        transition: transform .2s ease;
                    }

                    .dashboard-group-bar[aria-expanded="true"] .dashboard-group-arrow {
                        transform: rotate(180deg);
                    }
                </style>
                <div class="d-flex flex-column gap-4">
                    @if($dashboardSections->isEmpty())
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <h3 class="card-title mb-2">No trackables yet</h3>
                                <p class="text-secondary mb-0">
                                    Create a trackable and assign it to a group to start organizing the dashboard.
                                </p>
                            </div>
                        </div>
                    @endif

                    @foreach ($dashboardSections as $section)
                        @php($group = $section['group'])
                        @php($groupTrackables = $section['trackables'])
                        @php($sectionKey = $section['key'])
                        <section>
                            <button
                                class="dashboard-group-bar btn btn-outline-secondary w-100 d-flex align-items-center justify-content-between gap-3 mb-3 p-3"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#dashboard-group-{{ $sectionKey }}"
                                data-dashboard-group-key="{{ $sectionKey === 'ungrouped' ? 'ungrouped' : 'group-'.$sectionKey }}"
                                aria-expanded="true"
                                aria-controls="dashboard-group-{{ $sectionKey }}"
                            >
                                <span>
                                    <span class="h3 d-block mb-1">{{ $group?->name ?? 'Ungrouped' }}</span>
                                    @if($group?->description)
                                        <span class="text-secondary d-block">{{ $group->description }}</span>
                                    @endif
                                </span>
                                <span class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $group ? 'bg-blue-lt' : 'bg-secondary-lt' }}">{{ $groupTrackables->count() }} shown</span>
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="dashboard-group-arrow icon icon-2"
                                        aria-hidden="true"
                                    >
                                        <path d="M6 9l6 6l6 -6" />
                                    </svg>
                                </span>
                            </button>
                            <div class="collapse show" id="dashboard-group-{{ $sectionKey }}">
                                <div class="row row-cards">
                                    @foreach ($groupTrackables as $trackable)
                                        <div class="col-12 col-md-6 col-xl-4">
                                            @include('trackables.partials.dashboard-card', ['trackable' => $trackable])
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                    @endforeach

                    <div>
                        {{ $list->links() }}
                    </div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        var storagePrefix = 'trackables.dashboard.group.';

                        document.querySelectorAll('[data-dashboard-group-key]').forEach(function (bar) {
                            var targetSelector = bar.getAttribute('data-bs-target');
                            var target = document.querySelector(targetSelector);
                            var storageKey = storagePrefix + bar.getAttribute('data-dashboard-group-key');

                            if (!target) {
                                return;
                            }

                            if (localStorage.getItem(storageKey) === 'closed') {
                                target.classList.remove('show');
                                bar.classList.add('collapsed');
                                bar.setAttribute('aria-expanded', 'false');
                            }

                            target.addEventListener('shown.bs.collapse', function () {
                                localStorage.setItem(storageKey, 'open');
                                bar.setAttribute('aria-expanded', 'true');
                            });

                            target.addEventListener('hidden.bs.collapse', function () {
                                localStorage.setItem(storageKey, 'closed');
                                bar.setAttribute('aria-expanded', 'false');
                            });
                        });
                    });
                </script>
            @endif
        @endif
    @endisset

</x-layout>
