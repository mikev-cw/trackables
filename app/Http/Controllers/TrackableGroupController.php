<?php

namespace App\Http\Controllers;

use App\Models\TrackableGroup;
use Illuminate\Http\Request;

class TrackableGroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = TrackableGroup::query()
            ->where('user_id', $request->user()->id)
            ->withCount('trackables')
            ->orderBy('deleted')
            ->orderBy('name')
            ->paginate(15);

        return view('trackable-groups.index', compact('groups'));
    }

    public function create()
    {
        return view('trackable-groups.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateGroup($request);

        TrackableGroup::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'deleted' => 0,
        ]);

        return redirect()
            ->route('trackable-groups.index')
            ->with('status', 'Group created successfully.');
    }

    public function edit(Request $request, TrackableGroup $trackableGroup)
    {
        $this->authorizeGroup($request, $trackableGroup);

        $trackableGroup->loadCount('trackables');

        return view('trackable-groups.edit', compact('trackableGroup'));
    }

    public function update(Request $request, TrackableGroup $trackableGroup)
    {
        $this->authorizeGroup($request, $trackableGroup);
        $validated = $this->validateGroup($request);

        $trackableGroup->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('trackable-groups.edit', $trackableGroup->uid)
            ->with('status', 'Group updated successfully.');
    }

    public function toggle(Request $request, TrackableGroup $trackableGroup)
    {
        $this->authorizeGroup($request, $trackableGroup);

        $trackableGroup->update([
            'deleted' => !$trackableGroup->deleted,
        ]);

        return redirect()
            ->route('trackable-groups.index')
            ->with('status', $trackableGroup->deleted ? 'Group disabled.' : 'Group enabled.');
    }

    private function validateGroup(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
    }

    private function authorizeGroup(Request $request, TrackableGroup $trackableGroup): void
    {
        abort_unless($trackableGroup->user_id === $request->user()->id, 404);
    }
}
