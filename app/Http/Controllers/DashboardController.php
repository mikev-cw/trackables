<?php

namespace App\Http\Controllers;

use App\Models\Trackable;
use App\Models\TrackableGroup;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,disabled,all',
            'group' => 'nullable|string|max:24',
            'sort' => 'nullable|in:recent,name,record',
            'per_page' => 'nullable|in:24,48,96',
        ]);
        $search = trim($filters['q'] ?? '');
        $status = $filters['status'] ?? 'active';
        $selectedGroup = $filters['group'] ?? '';
        $sort = $filters['sort'] ?? 'recent';
        $perPage = (int) ($filters['per_page'] ?? 24);
        $owned = Trackable::query()->where('trackables.user_id', $request->user()->id);
        $activeCount = (clone $owned)->where('trackables.deleted', 0)->count();
        $disabledCount = (clone $owned)->where('trackables.deleted', 1)->count();

        $query = (clone $owned)
            ->when($status !== 'all', fn ($query) => $query->where('trackables.deleted', $status === 'disabled' ? 1 : 0))
            ->when($search !== '', function ($query) use ($search) {
                // Treat search as literal text, including SQL LIKE wildcards.
                $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search).'%';
                $query->where(function ($query) use ($term) {
                    $query->whereRaw("trackables.name LIKE ? ESCAPE '!'", [$term])
                        ->orWhereRaw("trackables.alias LIKE ? ESCAPE '!'", [$term]);
                });
            });

        $groups = TrackableGroup::query()->where('user_id', $request->user()->id)
            ->where('deleted', 0)->orderBy('name')->get();
        $groupCounts = (clone $query)->selectRaw('group_uid, COUNT(*) as total')->groupBy('group_uid')
            ->pluck('total', 'group_uid');
        $allCount = $groupCounts->sum();
        $ungroupedCount = $groupCounts->reject(fn ($count, $key) => $groups->contains('uid', $key))->sum();
        $activeGroupUids = $groups->pluck('uid');

        $query->when($selectedGroup !== '', function ($query) use ($selectedGroup, $activeGroupUids) {
            if ($selectedGroup === 'ungrouped') {
                $query->where(fn ($query) => $query->whereNull('group_uid')->orWhereNotIn('group_uid', $activeGroupUids));
            } else {
                $query->whereIn('group_uid', $activeGroupUids)->where('group_uid', $selectedGroup);
            }
        });

        $query->select('trackables.*')->with('group')->withMax('records', 'record_date');
        // Keep groups together; every sort also has a stable tie-breaker for pagination.
        $query->leftJoin('trackable_groups as dashboard_groups', function ($join) use ($request) {
            $join->on('trackables.group_uid', '=', 'dashboard_groups.uid')
                ->where('dashboard_groups.user_id', $request->user()->id)->where('dashboard_groups.deleted', 0);
        })->orderByRaw('CASE WHEN dashboard_groups.uid IS NULL THEN 1 ELSE 0 END')
            ->orderBy('dashboard_groups.name');
        match ($sort) {
            'name' => $query->orderBy('trackables.name'),
            'record' => $query->orderByDesc('records_max_record_date'),
            default => $query->orderByDesc('trackables.updated_at'),
        };
        $list = $query->orderBy('trackables.uid')->paginate($perPage)->withQueryString();
        $dashboardSections = $list->getCollection()
            ->groupBy(fn ($trackable) => $activeGroupUids->contains($trackable->group_uid) ? $trackable->group_uid : 'ungrouped')
            ->map(fn ($trackables, $key) => [
                'key' => $key,
                'group' => $key === 'ungrouped' ? null : $trackables->first()->group,
                'trackables' => $trackables->values(),
            ])->values();

        return view('dashboard', compact('list', 'groups', 'dashboardSections', 'search', 'status',
            'selectedGroup', 'sort', 'perPage', 'activeCount', 'disabledCount', 'groupCounts', 'allCount', 'ungroupedCount'));
    }
}
