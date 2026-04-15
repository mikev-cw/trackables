<?php

namespace App\Http\Controllers;

use App\Http\Resources\TrackableResource;
use App\Models\Trackable;
use App\Models\TrackableData;
use App\Models\TrackableGraph;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TrackableController extends Controller
{

    public function create(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'alias' => 'nullable|string|max:255',
        ]);

        $t = Trackable::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'alias' => Trackable::generateUniqueAlias($validated['name'], $validated['alias'] ?? null),
        ]);

        return $t;
    }

    public function list(Request $request)
    {
        return TrackableResource::collection(Trackable::with('schema')
            ->where('deleted',0)
            ->where('user_id',$request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(10));
    }

    public function storeSingleRecord(Request $request)
    {
        $trackable = $request->trackable;
        $normalizedPayload = $this->normalizeApiRecordPayload($request->all(), $trackable);
        $this->validateRecordDateValue($normalizedPayload['record_date'] ?? null);
        $validated = $this->validateSingleRecord($request, $trackable, $normalizedPayload['data']);
        $record = $this->persistSingleRecord($trackable, $validated, $normalizedPayload['record_date'] ?? null);

        return response()->json([
            'record_uid' => $record->uid,
            'success' => true,
            'message' => 'Data successfully saved.',
        ]);
    }

    public function storeBulkRecords(Request $request): \Illuminate\Http\JsonResponse
    {
        $schema = $request->trackable->schema;
        $rawItems = $request->has('records') ? $request->input('records', []) : [$request->all()];
        $items = collect($rawItems)
            ->map(fn ($record) => $this->normalizeApiRecordPayload($record, $request->trackable))
            ->all();

        $validationRules = $this->getSingleRecordValidationRules($schema->keyBy('uid'));

        if (empty($validationRules)) {
            return response()->json([
                'success' => false,
                'message' => 'No validation rules were applied. Ensure your input matches the schema.',
            ], 400);
        }

        validator([
            'records' => $items,
        ], [
            'records' => 'required|array|min:1',
            'records.*' => 'array',
        ])->validate();

        foreach ($items as $index => $recordItem) {
            $this->validateRecordDateValue($recordItem['record_date'] ?? null);
            $recordData = $recordItem['data'];
            validator($recordData, $validationRules, [], collect($schema)->mapWithKeys(function ($field) {
                return [$field->uid => $field->alias ?: $field->name];
            })->all())->validate();
        }

        $recordsCreated =[];

        DB::transaction(function () use (&$request, &$items, &$recordsCreated) {
            foreach ($items as $recordItem) {
                $recordData = $recordItem['data'];
                // Create a record
                $record = TrackableRecord::create([
                    'trackable_uid' => $request->trackable->uid,
                    'record_date' => $this->resolveRecordDate($recordItem['record_date'] ?? null),
                ]);

                $recordsCreated[] = $record->uid;

                // Prepare TrackableData instances
                $trackableDataInstances = [];
                foreach ($recordData as $key => $value) {
                    $trackableDataInstances[] = new TrackableData([
                        'trackable_record_uid' => $record->uid,
                        'trackable_schema_uid' => $key,
                        'value' => $value,
                    ]);
                }

                // Bulk save using saveMany() to trigger Eloquent events
                if (!empty($trackableDataInstances)) {
                    $record->data()->saveMany($trackableDataInstances);
                }
            }
        });

        return response()->json([
            'record_uids' => $recordsCreated,
            'success' => true,
            'message' => count($recordsCreated).' records created successfully',
        ], 201);
    }

    public function storeSingleSchema(Request $request)
    {
        // store a single schema for a trackable

        $validated = $request->validate([
            'name' => 'required|max:80',
            'alias' => 'nullable|string|max:80',
            'field_type' => 'required',
            'enum_uid' => 'nullable',
            'calc_formula' => 'nullable',
            'validation_rule' => 'required',
        ]);

        return TrackableSchema::create([
            'trackable_uid' => $request->trackable->uid,
            'name' => $validated['name'],
            'alias' => TrackableSchema::generateUniqueAlias(
                $request->trackable->uid,
                $validated['name'],
                $validated['alias'] ?? null
            ),
            'field_type' => $validated['field_type'],
            'enum_uid' => $validated['enum_uid'] ?? null,
            'calc_formula' => $validated['calc_formula'] ?? null,
            'validation_rule' => $validated['validation_rule'],
        ]);

    }

    public function editSchema(Request $request)
    {
        // Find the model by ID
        $model = TrackableSchema::findOrFail($request->schema);

        // Define validation rules
        // https://laravel.com/docs/11.x/validation#available-validation-rules
        $rules = [
            'name' => 'sometimes|string|max:80',
            'alias' => 'sometimes|string|max:80',
            'field_type' => 'sometimes|string',
            'enum_uid' => 'sometimes|string|max:24',
            'calc_formula' => 'sometimes|',
            'validation_rule' => 'sometimes|',
        ];

        // Validate the incoming request
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update only the provided fields
        $payload = $request->only(array_keys($rules));

        if (array_key_exists('name', $payload) || array_key_exists('alias', $payload)) {
            $payload['alias'] = TrackableSchema::generateUniqueAlias(
                $model->trackable_uid,
                $payload['name'] ?? $model->name,
                $payload['alias'] ?? $model->alias,
                $model->uid
            );
        }

        $model->update($payload);

        return response()->json(['message' => 'Model updated successfully', 'data' => $model], 200);
    }

    public function show(Trackable $trackable, Request $request)
    {
        $perPageOptions = [25, 50, 100, 250];
        $sortOptions = ['record_date', 'created_at'];
        $sortBy = $request->query('sort_by', 'record_date');
        $sortDir = $request->query('sort_dir', 'desc');
        $perPage = (int) $request->query('per_page', 25);

        if (!in_array($sortBy, $sortOptions, true)) {
            $sortBy = 'record_date';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        if (!in_array($perPage, $perPageOptions, true)) {
            $perPage = 25;
        }

        $trackable->load('schema');

        // Estrai e decodifica lo schema se è JSON
        $schema = $trackable->schema;
        if (is_string($schema)) {
            $decoded = json_decode($schema, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $schema = $decoded;
            }
        }

        $schemaCollection = collect($schema);
        $schemaFieldFilters = collect($request->query('schema', []))
            ->filter(function ($value) {
                return !is_null($value) && trim((string) $value) !== '';
            })
            ->map(function ($value) {
                return trim((string) $value);
            })
            ->only($schemaCollection->pluck('uid')->all());

        $recordsQuery = $trackable->records()
            ->with('data')
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('record_date', '>=', $request->query('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('record_date', '<=', $request->query('date_to'));
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->query('q'));

                $query->whereHas('data', function ($dataQuery) use ($search) {
                    $dataQuery->where('value', 'like', '%'.$search.'%');
                });
            })
            ->when($schemaFieldFilters->isNotEmpty(), function ($query) use ($schemaCollection, $schemaFieldFilters) {
                foreach ($schemaFieldFilters as $schemaUid => $value) {
                    $field = $schemaCollection->firstWhere('uid', $schemaUid);
                    $operator = $this->usesExactSchemaFilter($field?->field_type) ? '=' : 'like';
                    $comparisonValue = $operator === '=' ? $value : '%'.$value.'%';

                    $query->whereHas('data', function ($dataQuery) use ($schemaUid, $operator, $comparisonValue) {
                        $dataQuery
                            ->where('trackable_schema_uid', $schemaUid)
                            ->where('value', $operator, $comparisonValue);
                    });
                }
            })
            ->orderBy($sortBy, $sortDir)
            ->orderBy('uid', 'desc');

        $records = $recordsQuery
            ->paginate($perPage)
            ->withQueryString();

        $schemaByUid = $schemaCollection->keyBy('uid');
        $schemaOrder = $schemaCollection->pluck('uid')->flip();
        $filters = [
            'q' => (string) $request->query('q', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'per_page' => $perPage,
            'schema' => $schemaCollection->mapWithKeys(function ($field) use ($schemaFieldFilters) {
                return [$field->uid => (string) $schemaFieldFilters->get($field->uid, '')];
            })->toArray(),
        ];
        $activeFilterCount = count(array_filter([
            $filters['q'],
            $filters['date_from'],
            $filters['date_to'],
        ])) + $schemaFieldFilters->count();

        return view('trackables.show', compact(
            'trackable',
            'schema',
            'schemaByUid',
            'schemaOrder',
            'records',
            'filters',
            'activeFilterCount',
            'sortOptions',
            'perPageOptions'
        ));
    }

    public function createTrackablePage()
    {
        return view('trackables.create-trackable');
    }

    public function storeTrackable(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'alias' => 'nullable|string|max:255',
        ]);

        $trackable = Trackable::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'alias' => Trackable::generateUniqueAlias($validated['name'], $validated['alias'] ?? null),
            'deleted' => 0,
        ]);

        return redirect()
            ->route('trackables.edit', $trackable->uid)
            ->with('status', 'Trackable created successfully.');
    }

    public function editTrackablePage(Trackable $trackable)
    {
        $trackable->loadCount('schema');
        $trackable->loadMax('records', 'record_date');

        return view('trackables.edit-trackable', compact('trackable'));
    }

    public function updateTrackable(Request $request, Trackable $trackable)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'alias' => 'nullable|string|max:255',
        ]);

        $trackable->update([
            'name' => $validated['name'],
            'alias' => Trackable::generateUniqueAlias(
                $validated['name'],
                $validated['alias'] ?? null,
                $trackable->uid
            ),
        ]);

        return redirect()
            ->route('trackables.edit', $trackable->uid)
            ->with('status', 'Trackable updated successfully.');
    }

    public function toggleTrackable(Trackable $trackable)
    {
        $trackable->update([
            'deleted' => !$trackable->deleted,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', $trackable->deleted ? 'Trackable disabled.' : 'Trackable enabled.');
    }

    public function editSchemaPage(Trackable $trackable)
    {
        $trackable->load(['schema' => fn ($query) => $query->orderBy('created_at')]);

        return view('trackables.edit-schema', [
            'trackable' => $trackable,
            'schemaFields' => $trackable->schema,
            'fieldTypeOptions' => ['int', 'float', 'json', 'string', 'bool', 'date', 'datetime', 'img', 'url', 'enum', 'calc'],
        ]);
    }

    public function statistics(Trackable $trackable)
    {
        $trackable->load([
            'schema' => fn ($query) => $query->orderBy('created_at'),
            'graphs' => fn ($query) => $query->latest(),
        ]);

        $schema = $trackable->schema;
        $graphableSchema = $trackable->schema->filter(fn ($field) => $this->isGraphableFieldType($field->field_type))->values();
        $schemaByUid = $trackable->schema->keyBy('uid');
        $graphs = $trackable->graphs->map(fn ($graph) => $this->buildGraphViewModel($trackable, $graph, $schemaByUid));
        [$graphTypeOptions, $rangeOptions, $bucketOptions, $aggregateOptions] = $this->getGraphOptionSets();
        $graphForm = $this->getGraphFormState($trackable->schema, $graphableSchema);

        return view('trackables.statistics', compact(
            'trackable',
            'schema',
            'graphs',
            'graphableSchema',
            'graphForm',
            'graphTypeOptions',
            'rangeOptions',
            'bucketOptions',
            'aggregateOptions'
        ));
    }

    public function createRecord(Trackable $trackable)
    {
        $schema = $trackable->schema()->orderBy('created_at')->get();

        return view('trackables.create-record', compact('trackable', 'schema'));
    }

    public function storeRecord(Request $request, Trackable $trackable)
    {
        $this->validateRecordDateValue($request->input('record_date'));
        $validated = $this->validateSingleRecord($request, $trackable);
        $this->persistSingleRecord($trackable, $validated, $request->input('record_date'));

        return redirect()
            ->route('trackables.show', $trackable->uid)
            ->with('status', 'Record added successfully.');
    }

    public function editRecord(Trackable $trackable, TrackableRecord $record)
    {
        $record = $this->getTrackableRecordOrFail($trackable, $record);
        $schema = $trackable->schema()->orderBy('created_at')->get();
        $recordValues = $this->getRecordValues($record);

        return view('trackables.edit-record', compact('trackable', 'record', 'schema', 'recordValues'));
    }

    public function updateRecord(Request $request, Trackable $trackable, TrackableRecord $record)
    {
        $record = $this->getTrackableRecordOrFail($trackable, $record);
        $validated = $this->validateSingleRecord($request, $trackable);
        $this->updateSingleRecord($record, $validated);

        return redirect()
            ->route('trackables.show', $trackable->uid)
            ->with('status', 'Record updated successfully.');
    }

    public function destroyRecord(Trackable $trackable, TrackableRecord $record)
    {
        $record = $this->getTrackableRecordOrFail($trackable, $record);
        $this->deleteSingleRecord($record);

        return redirect()
            ->route('trackables.show', $trackable->uid)
            ->with('status', 'Record deleted successfully.');
    }

    public function storeGraph(Request $request, Trackable $trackable)
    {
        $trackable->load('schema');
        $validated = $this->validateGraphRequest($request, $trackable);

        TrackableGraph::create($this->buildGraphPayload($trackable, $validated));

        return redirect()
            ->route('trackables.statistics', $trackable->uid)
            ->with('status', 'Graph added successfully.');
    }

    public function storeSchemaFromPage(Request $request, Trackable $trackable)
    {
        $validated = $this->validateSchemaPayload($request);

        TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => $validated['name'],
            'alias' => TrackableSchema::generateUniqueAlias(
                $trackable->uid,
                $validated['name'],
                $validated['alias'] ?? null
            ),
            'field_type' => $validated['field_type'],
            'enum_uid' => $validated['enum_uid'] ?? null,
            'calc_formula' => $validated['calc_formula'] ?? null,
            'validation_rule' => $validated['validation_rule'],
        ]);

        return redirect()
            ->route('trackables.schema.edit', $trackable->uid)
            ->with('status', 'Schema field added successfully.');
    }

    public function updateSchemaFromPage(Request $request, Trackable $trackable, TrackableSchema $schema)
    {
        abort_unless($schema->trackable_uid === $trackable->uid, 404);

        $validated = $this->validateSchemaPayload($request);

        $schema->update([
            'name' => $validated['name'],
            'alias' => TrackableSchema::generateUniqueAlias(
                $trackable->uid,
                $validated['name'],
                $validated['alias'] ?? null,
                $schema->uid
            ),
            'field_type' => $validated['field_type'],
            'enum_uid' => $validated['enum_uid'] ?? null,
            'calc_formula' => $validated['calc_formula'] ?? null,
            'validation_rule' => $validated['validation_rule'],
        ]);

        return redirect()
            ->route('trackables.schema.edit', $trackable->uid)
            ->with('status', 'Schema field updated successfully.');
    }

    public function editGraph(Trackable $trackable, TrackableGraph $graph)
    {
        $trackable->load(['schema' => fn ($query) => $query->orderBy('created_at')]);
        abort_unless($graph->trackable_uid === $trackable->uid, 404);

        $schema = $trackable->schema;
        $graphableSchema = $trackable->schema->filter(fn ($field) => $this->isGraphableFieldType($field->field_type))->values();
        [$graphTypeOptions, $rangeOptions, $bucketOptions, $aggregateOptions] = $this->getGraphOptionSets();
        $graphForm = $this->getGraphFormState($trackable->schema, $graphableSchema, $graph);

        return view('trackables.edit-graph', compact(
            'trackable',
            'schema',
            'graph',
            'graphForm',
            'graphableSchema',
            'graphTypeOptions',
            'rangeOptions',
            'bucketOptions',
            'aggregateOptions'
        ));
    }

    public function updateGraph(Request $request, Trackable $trackable, TrackableGraph $graph)
    {
        $trackable->load('schema');
        abort_unless($graph->trackable_uid === $trackable->uid, 404);

        $validated = $this->validateGraphRequest($request, $trackable);
        $graph->update($this->buildGraphPayload($trackable, $validated));

        return redirect()
            ->route('trackables.statistics', $trackable->uid)
            ->with('status', 'Graph updated successfully.');
    }

    public function destroyGraph(Trackable $trackable, TrackableGraph $graph)
    {
        abort_unless($graph->trackable_uid === $trackable->uid, 404);

        $graph->delete();

        return redirect()
            ->route('trackables.statistics', $trackable->uid)
            ->with('status', 'Graph deleted successfully.');
    }

    private function validateSingleRecord(Request $request, Trackable $trackable, ?array $payload = null): array
    {
        $schema = $trackable->schema->keyBy('uid');
        $validationRules = $this->getSingleRecordValidationRules($schema);

        if (empty($validationRules)) {
            abort(400, 'No validation rules were applied. Ensure your input matches the schema.');
        }

        return validator(
            $payload ?? $request->all(),
            $validationRules,
            [],
            $schema->mapWithKeys(function ($field) {
                return [$field->uid => $field->alias ?: $field->name];
            })->all()
        )->validate();
    }

    private function validateSchemaPayload(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:80',
            'alias' => 'nullable|string|max:80',
            'field_type' => 'required|string',
            'enum_uid' => 'nullable|string|max:24',
            'calc_formula' => 'nullable',
            'validation_rule' => 'required|string',
        ]);
    }

    private function getSingleRecordValidationRules(Collection $schema): array
    {
        return $schema->mapWithKeys(function ($item, $key) {
            return [$key => $item->validation_rule];
        })->toArray();
    }

    private function usesExactSchemaFilter(?string $fieldType): bool
    {
        return in_array($fieldType, ['int', 'float', 'bool', 'date', 'datetime'], true);
    }

    private function normalizeApiRecordPayload(array $payload, Trackable $trackable): array
    {
        $schema = $trackable->schema;
        $schemaByUid = $schema->keyBy('uid');
        $schemaByAlias = $schema->filter(fn ($field) => !empty($field->alias))->keyBy('alias');
        $normalized = [];
        $recordDate = $payload['record_date'] ?? null;

        foreach ($payload as $key => $value) {
            if ($key === 'record_date') {
                continue;
            }

            $field = $schemaByUid->get($key) ?? $schemaByAlias->get(Str::snake((string) $key));

            if (!$field) {
                continue;
            }

            $normalized[$field->uid] = $value;
        }

        return [
            'record_date' => $recordDate,
            'data' => $normalized,
        ];
    }

    private function isGraphableFieldType(?string $fieldType): bool
    {
        return in_array($fieldType, ['int', 'float', 'bool'], true);
    }

    private function getGraphOptionSets(): array
    {
        return [
            ['line' => 'Line', 'bar' => 'Bar'],
            [
                'all_time' => 'All time',
                'last_30_days' => 'Last 30 days',
                'last_6_months' => 'Last 6 months',
                'last_12_months' => 'Last 12 months',
            ],
            [
                'raw' => 'No grouping',
                'day' => 'By day',
                'week' => 'By week',
                'month' => 'By month',
            ],
            [
                'latest' => 'Latest',
                'average' => 'Average',
                'min' => 'Minimum',
                'max' => 'Maximum',
                'sum' => 'Sum',
            ],
        ];
    }

    private function getGraphFormState(Collection $schema, Collection $graphableSchema, ?TrackableGraph $graph = null): array
    {
        $storedFilters = collect($graph?->filters ?? []);

        return [
            'title' => old('title', $graph?->title ?? ''),
            'graph_type' => old('graph_type', $graph?->graph_type ?? 'line'),
            'range_type' => old('range_type', $graph?->range_type ?? 'all_time'),
            'bucket_size' => old('bucket_size', $graph?->bucket_size ?? ($graph?->sampling === 'daily_latest' ? 'day' : 'raw')),
            'aggregate' => old('aggregate', $graph?->aggregate ?? ($graph?->sampling === 'daily_latest' ? 'latest' : 'latest')),
            'schema_uids' => old('schema_uids', $graph?->schema_uids ?? []),
            'filters' => $schema->mapWithKeys(function ($field) use ($storedFilters) {
                return [$field->uid => old("filters.{$field->uid}", (string) $storedFilters->get($field->uid, ''))];
            })->toArray(),
            'graphable_schema_uids' => $graphableSchema->pluck('uid')->all(),
        ];
    }

    private function validateGraphRequest(Request $request, Trackable $trackable): array
    {
        [$graphTypeOptions, $rangeOptions, $bucketOptions, $aggregateOptions] = $this->getGraphOptionSets();
        $graphableSchemaUids = $trackable->schema
            ->filter(fn ($field) => $this->isGraphableFieldType($field->field_type))
            ->pluck('uid')
            ->all();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'graph_type' => 'required|in:'.implode(',', array_keys($graphTypeOptions)),
            'range_type' => 'required|in:'.implode(',', array_keys($rangeOptions)),
            'bucket_size' => 'required|in:'.implode(',', array_keys($bucketOptions)),
            'aggregate' => 'required|in:'.implode(',', array_keys($aggregateOptions)),
            'schema_uids' => 'required|array|min:1',
            'schema_uids.*' => 'required|string|in:'.implode(',', $graphableSchemaUids),
            'filters' => 'nullable|array',
        ]);

        $validated['filters'] = collect($validated['filters'] ?? [])
            ->filter(fn ($value) => !is_null($value) && trim((string) $value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->only($trackable->schema->pluck('uid')->all())
            ->toArray();

        return $validated;
    }

    private function buildGraphPayload(Trackable $trackable, array $validated): array
    {
        return [
            'trackable_uid' => $trackable->uid,
            'title' => $validated['title'],
            'graph_type' => $validated['graph_type'],
            'range_type' => $validated['range_type'],
            'bucket_size' => $validated['bucket_size'],
            'aggregate' => $validated['aggregate'],
            'sampling' => $validated['bucket_size'] === 'day' && $validated['aggregate'] === 'latest' ? 'daily_latest' : 'all',
            'schema_uids' => array_values(array_unique($validated['schema_uids'])),
            'filters' => $validated['filters'],
        ];
    }

    private function getTrackableRecordOrFail(Trackable $trackable, TrackableRecord $record): TrackableRecord
    {
        abort_unless($record->trackable_uid === $trackable->uid, 404);

        return $record->load('data');
    }

    private function getRecordValues(TrackableRecord $record): array
    {
        return $record->data
            ->mapWithKeys(function ($item) {
                return [$item->trackable_schema_uid => $item->value];
            })
            ->toArray();
    }

    private function persistSingleRecord(Trackable $trackable, array $validated, mixed $recordDate = null): TrackableRecord
    {
        return DB::transaction(function () use ($trackable, $validated, $recordDate) {
            $record = TrackableRecord::create([
                'trackable_uid' => $trackable->uid,
                'record_date' => $this->resolveRecordDate($recordDate),
            ]);

            foreach ($validated as $key => $value) {
                TrackableData::create([
                    'trackable_record_uid' => $record->uid,
                    'trackable_schema_uid' => $key,
                    'value' => is_array($value) ? json_encode($value) : (string) $value,
                ]);
            }

            return $record;
        });
    }

    private function resolveRecordDate(mixed $recordDate = null): Carbon
    {
        if (blank($recordDate)) {
            return now();
        }

        return Carbon::parse($recordDate);
    }

    private function validateRecordDateValue(mixed $recordDate = null): void
    {
        validator(
            ['record_date' => $recordDate],
            ['record_date' => 'nullable|date']
        )->validate();
    }

    private function updateSingleRecord(TrackableRecord $record, array $validated): void
    {
        DB::transaction(function () use ($record, $validated) {
            $record->update([
                'record_date' => now(),
            ]);

            $existingData = $record->data()->get()->keyBy('trackable_schema_uid');

            foreach ($validated as $key => $value) {
                $serializedValue = is_array($value) ? json_encode($value) : (string) $value;
                $existingField = $existingData->get($key);

                if ($existingField) {
                    $existingField->update([
                        'value' => $serializedValue,
                    ]);

                    continue;
                }

                TrackableData::create([
                    'trackable_record_uid' => $record->uid,
                    'trackable_schema_uid' => $key,
                    'value' => $serializedValue,
                ]);
            }
        });
    }

    private function deleteSingleRecord(TrackableRecord $record): void
    {
        DB::transaction(function () use ($record) {
            $record->data()->delete();
            $record->delete();
        });
    }

    private function buildGraphViewModel(Trackable $trackable, TrackableGraph $graph, Collection $schemaByUid): array
    {
        $bucketSize = $graph->bucket_size ?? ($graph->sampling === 'daily_latest' ? 'day' : 'raw');
        $aggregate = $graph->aggregate ?? 'latest';

        $records = $trackable->records()
            ->with(['data' => function ($query) use ($graph) {
                $query->whereIn('trackable_schema_uid', $graph->schema_uids ?? []);
            }])
            ->when($this->getGraphRangeStart($graph->range_type), function ($query, $startDate) {
                $query->where('record_date', '>=', $startDate);
            })
            ->when(!empty($graph->filters), function ($query) use ($schemaByUid, $graph) {
                foreach ($graph->filters as $schemaUid => $value) {
                    $field = $schemaByUid->get($schemaUid);
                    $operator = $this->usesExactSchemaFilter($field?->field_type) ? '=' : 'like';
                    $comparisonValue = $operator === '=' ? $value : '%'.$value.'%';

                    $query->whereHas('data', function ($dataQuery) use ($schemaUid, $operator, $comparisonValue) {
                        $dataQuery
                            ->where('trackable_schema_uid', $schemaUid)
                            ->where('value', $operator, $comparisonValue);
                    });
                }
            })
            ->orderBy('record_date')
            ->get();

        [$labels, $datasets] = $bucketSize === 'raw'
            ? $this->buildRawGraphSeries($records, $graph, $schemaByUid)
            : $this->buildBucketedGraphSeries($records, $graph, $schemaByUid, $bucketSize, $aggregate);

        $activeFilterCount = count($graph->filters ?? []);
        $filterSummary = collect($graph->filters ?? [])
            ->map(function ($value, $schemaUid) use ($schemaByUid) {
                return ($schemaByUid->get($schemaUid)?->name ?? $schemaUid).': '.$value;
            })
            ->values()
            ->all();

        return [
            'uid' => $graph->uid,
            'title' => $graph->title,
            'graph_type' => $graph->graph_type,
            'range_label' => $this->getRangeLabel($graph->range_type),
            'bucket_label' => $this->getBucketLabel($bucketSize),
            'aggregate_label' => $this->getAggregateLabel($aggregate),
            'series_label' => collect($graph->schema_uids)
                ->map(fn ($schemaUid) => $schemaByUid->get($schemaUid)?->name)
                ->filter()
                ->join(', '),
            'active_filter_count' => $activeFilterCount,
            'filter_summary' => $filterSummary,
            'chart' => [
                'labels' => $labels,
                'datasets' => $datasets,
            ],
        ];
    }

    private function buildRawGraphSeries(Collection $records, TrackableGraph $graph, Collection $schemaByUid): array
    {
        $labels = $records->map(fn ($record) => Carbon::parse($record->record_date)->format('Y-m-d H:i'))->values();
        $datasets = [];

        foreach ($graph->schema_uids as $schemaUid) {
            $field = $schemaByUid->get($schemaUid);

            if (!$field || !$this->isGraphableFieldType($field->field_type)) {
                continue;
            }

            $datasets[] = [
                'label' => $field->name,
                'data' => $records->map(function ($record) use ($schemaUid, $field) {
                    $dataRow = $record->data->firstWhere('trackable_schema_uid', $schemaUid);

                    if (!$dataRow) {
                        return null;
                    }

                    return $field->field_type === 'bool' ? (int) $dataRow->value : (float) $dataRow->value;
                })->values(),
            ];
        }

        return [$labels, $datasets];
    }

    private function buildBucketedGraphSeries(Collection $records, TrackableGraph $graph, Collection $schemaByUid, string $bucketSize, string $aggregate): array
    {
        $bucketedRecords = $records
            ->groupBy(fn ($record) => $this->getBucketKey(Carbon::parse($record->record_date), $bucketSize));

        $labels = $bucketedRecords->keys()->values();
        $datasets = [];

        foreach ($graph->schema_uids as $schemaUid) {
            $field = $schemaByUid->get($schemaUid);

            if (!$field || !$this->isGraphableFieldType($field->field_type)) {
                continue;
            }

            $datasets[] = [
                'label' => $field->name,
                'data' => $bucketedRecords->map(function ($bucketRecords) use ($schemaUid, $field, $aggregate) {
                    $points = $bucketRecords
                        ->map(function ($record) use ($schemaUid, $field) {
                            $dataRow = $record->data->firstWhere('trackable_schema_uid', $schemaUid);

                            if (!$dataRow) {
                                return null;
                            }

                            return [
                                'record_date' => Carbon::parse($record->record_date),
                                'value' => $field->field_type === 'bool' ? (int) $dataRow->value : (float) $dataRow->value,
                            ];
                        })
                        ->filter()
                        ->values();

                    if ($points->isEmpty()) {
                        return null;
                    }

                    return $this->aggregateGraphPoints($points, $aggregate);
                })->values(),
            ];
        }

        return [$labels, $datasets];
    }

    private function getBucketKey(Carbon $date, string $bucketSize): string
    {
        return match ($bucketSize) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->copy()->startOfWeek()->format('Y-m-d'),
            'month' => $date->format('Y-m'),
            default => $date->format('Y-m-d H:i'),
        };
    }

    private function aggregateGraphPoints(\Illuminate\Support\Collection $points, string $aggregate): float|int
    {
        return match ($aggregate) {
            'average' => round($points->avg('value'), 4),
            'min' => $points->min('value'),
            'max' => $points->max('value'),
            'sum' => round($points->sum('value'), 4),
            default => $points->sortByDesc('record_date')->first()['value'],
        };
    }

    private function getGraphRangeStart(string $rangeType): ?Carbon
    {
        return match ($rangeType) {
            'last_30_days' => now()->subDays(30),
            'last_6_months' => now()->subMonths(6),
            'last_12_months' => now()->subMonths(12),
            default => null,
        };
    }

    private function getRangeLabel(string $rangeType): string
    {
        return match ($rangeType) {
            'last_30_days' => 'Last 30 days',
            'last_6_months' => 'Last 6 months',
            'last_12_months' => 'Last 12 months',
            default => 'All time',
        };
    }

    private function getBucketLabel(string $bucketSize): string
    {
        return match ($bucketSize) {
            'day' => 'Grouped by day',
            'week' => 'Grouped by week',
            'month' => 'Grouped by month',
            default => 'Raw timeline',
        };
    }

    private function getAggregateLabel(string $aggregate): string
    {
        return match ($aggregate) {
            'average' => 'Average',
            'min' => 'Minimum',
            'max' => 'Maximum',
            'sum' => 'Sum',
            default => 'Latest',
        };
    }
}
