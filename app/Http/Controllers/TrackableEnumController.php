<?php

namespace App\Http\Controllers;

use App\Models\TrackableData;
use App\Models\TrackableEnum;
use App\Models\TrackableEnumValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TrackableEnumController extends Controller
{
    public function index(Request $request)
    {
        $enums = TrackableEnum::query()
            ->where('user_id', $request->user()->id)
            ->withCount(['values', 'schemaFields'])
            ->orderBy('name')
            ->paginate(15);

        return view('enums.index', compact('enums'));
    }

    public function create()
    {
        return view('enums.create');
    }

    public function store(Request $request)
    {
        $request->merge(['values' => $this->normalizedNewValues($request->input('values', []))]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'values' => 'required|array|min:1',
            'values.*.key' => ['required', 'string', 'max:255', 'regex:/^[A-Za-z0-9]+(?:_[A-Za-z0-9]+)*$/', 'distinct'],
            'values.*.label' => 'required|string|max:255',
            'values.*.description' => 'nullable|string|max:1000',
        ]);

        $enum = DB::transaction(function () use ($request, $validated) {
            $enum = TrackableEnum::create([
                'user_id' => $request->user()->id,
                'name' => $validated['name'],
            ]);

            foreach ($validated['values'] as $position => $value) {
                $enum->values()->create([
                    'value' => $value['key'],
                    'label' => $value['label'],
                    'description' => $value['description'] ?: null,
                    'is_active' => true,
                    'position' => $position,
                ]);
            }

            return $enum;
        });

        return redirect()->route('enums.edit', $enum->uid)->with('status', 'List created successfully.');
    }

    public function edit(Request $request, TrackableEnum $enum)
    {
        $this->authorizeOwner($request, $enum);
        $enum->load(['values', 'schemaFields.trackable']);

        $usedValueUids = TrackableData::query()
            ->whereIn('value', $enum->values->pluck('uid'))
            ->pluck('value')
            ->unique();

        return view('enums.edit', compact('enum', 'usedValueUids'));
    }

    public function update(Request $request, TrackableEnum $enum)
    {
        $this->authorizeOwner($request, $enum);
        $validated = $request->validate(['name' => 'required|string|max:255']);
        $enum->update($validated);

        return redirect()->route('enums.edit', $enum->uid)->with('status', 'List updated successfully.');
    }

    public function storeValue(Request $request, TrackableEnum $enum)
    {
        $this->authorizeOwner($request, $enum);
        $request->merge(['key' => Str::snake((string) $request->input('key'))]);
        $validated = $request->validate($this->valueRules($enum));

        $enum->values()->create([
            'value' => Str::snake($validated['key']),
            'label' => $validated['label'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
            'position' => ((int) $enum->values()->max('position')) + 1,
        ]);

        return redirect()->route('enums.edit', $enum->uid)->with('status', 'Value added successfully.');
    }

    public function updateValue(Request $request, TrackableEnum $enum, TrackableEnumValue $enumValue)
    {
        $this->authorizeOwner($request, $enum);
        $this->authorizeValue($enum, $enumValue);
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        $enumValue->update($validated);

        return redirect()->route('enums.edit', $enum->uid)->with('status', 'Value updated successfully.');
    }

    public function toggleValue(Request $request, TrackableEnum $enum, TrackableEnumValue $enumValue)
    {
        $this->authorizeOwner($request, $enum);
        $this->authorizeValue($enum, $enumValue);
        $enumValue->update(['is_active' => ! $enumValue->is_active]);

        return redirect()->route('enums.edit', $enum->uid)
            ->with('status', $enumValue->is_active ? 'Value enabled.' : 'Value retired. Existing records are unchanged.');
    }

    private function normalizedNewValues(array $values): array
    {
        return collect($values)
            ->map(function (array $value) {
                return [
                    'key' => Str::snake(trim((string) ($value['key'] ?? ''))),
                    'label' => trim((string) ($value['label'] ?? '')),
                    'description' => trim((string) ($value['description'] ?? '')),
                ];
            })
            ->filter(fn (array $value) => implode('', $value) !== '')
            ->values()
            ->all();
    }

    private function valueRules(TrackableEnum $enum): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9]+(?:[ _-][A-Za-z0-9]+)*$/',
                Rule::unique('enum_values', 'value')->where(fn ($query) => $query->where('enum_uid', $enum->uid)),
            ],
            'label' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    private function authorizeOwner(Request $request, TrackableEnum $enum): void
    {
        abort_unless($enum->user_id === $request->user()->id, 404);
    }

    private function authorizeValue(TrackableEnum $enum, TrackableEnumValue $enumValue): void
    {
        abort_unless($enumValue->enum_uid === $enum->uid, 404);
    }
}
