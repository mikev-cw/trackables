<?php

namespace App\Http\Controllers;

use App\Models\Trackable;
use App\Models\TrackableGroup;

class DashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $groupsConfigured = TrackableGroup::query()
            ->where('user_id', $user->id)
            ->exists();

        $trackablesQuery = Trackable::query()
            ->select('trackables.*')
            ->where('trackables.user_id', $user->id)
            ->with('group')
            ->withMax('records', 'record_date')
            ->withCount('schema');

        if (!$groupsConfigured) {
            $list = $trackablesQuery
                ->orderBy('trackables.deleted')
                ->orderByDesc('trackables.updated_at')
                ->paginate(12);

            return view('dashboard', compact('list', 'groupsConfigured'));
        }

        $list = $trackablesQuery
            ->leftJoin('trackable_groups as dashboard_groups', function ($join) {
                $join->on('trackables.group_uid', '=', 'dashboard_groups.uid')
                    ->where('dashboard_groups.deleted', 0);
            })
            ->orderByRaw('CASE WHEN dashboard_groups.uid IS NULL THEN 1 ELSE 0 END')
            ->orderBy('dashboard_groups.name')
            ->orderBy('trackables.deleted')
            ->orderByDesc('trackables.updated_at')
            ->paginate(12);

        $dashboardSections = $list->getCollection()
            ->groupBy(fn ($trackable) => $trackable->group && !$trackable->group->deleted ? $trackable->group_uid : 'ungrouped')
            ->map(function ($trackables, $key) {
                return [
                    'key' => $key,
                    'group' => $key === 'ungrouped' ? null : $trackables->first()->group,
                    'trackables' => $trackables->values(),
                ];
            })
            ->values();

        return view('dashboard', compact(
            'list',
            'groupsConfigured',
            'dashboardSections'
        ));
    }
}
