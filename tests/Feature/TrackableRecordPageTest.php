<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableData;
use App\Models\TrackableGraph;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrackableRecordPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owned_trackable_record_create_page_is_accessible(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Vitals',
        ]);

        TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Weight',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);

        $response = $this->actingAs($user)->get(route('trackables.records.create', $trackable->uid));

        $response->assertOk();
        $response->assertSee('Add data to Vitals');
        $response->assertSee('Measurement date and time');
        $response->assertSee('Weight');
    }

    public function test_owned_trackable_show_page_is_accessible(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Vitals',
        ]);

        TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Weight',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);

        $response = $this->actingAs($user)->get(route('trackables.show', $trackable->uid));

        $response->assertOk();
        $response->assertSee('Vitals');
        $response->assertSee('Weight');
    }

    public function test_posting_record_data_creates_record_and_values(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Vitals',
        ]);

        $weight = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Weight',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);

        $note = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Note',
            'field_type' => 'string',
            'validation_rule' => 'nullable|string|max:255',
        ]);

        $response = $this->actingAs($user)->post(route('trackables.records.store', $trackable->uid), [
            'record_date' => '2026-04-10T07:45',
            $weight->uid => '72.4',
            $note->uid => 'Morning check-in',
        ]);

        $response->assertRedirect(route('trackables.show', $trackable->uid));
        $response->assertSessionHas('status', 'Record added successfully.');

        $record = TrackableRecord::first();

        $this->assertNotNull($record);
        $this->assertSame($trackable->uid, $record->trackable_uid);
        $this->assertSame('2026-04-10 07:45:00', Carbon::parse($record->record_date)->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('trackable_data', [
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $weight->uid,
            'value' => '72.4',
        ]);
        $this->assertDatabaseHas('trackable_data', [
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $note->uid,
            'value' => 'Morning check-in',
        ]);
        $this->assertSame(2, TrackableData::count());
    }

    public function test_owned_trackable_record_edit_page_is_accessible(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Vitals',
        ]);
        $weight = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Weight',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);
        $record = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-01 08:00:00'),
        ]);
        TrackableData::create([
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $weight->uid,
            'value' => '72.4',
        ]);

        $response = $this->actingAs($user)->get(route('trackables.records.edit', [$trackable->uid, $record->uid]));

        $response->assertOk();
        $response->assertSee('Edit data in Vitals');
        $response->assertSee('Measurement date and time');
        $response->assertSee('72.4');
    }

    public function test_updating_record_data_replaces_existing_values(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Vitals',
        ]);

        $weight = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Weight',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);
        $note = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Note',
            'field_type' => 'string',
            'validation_rule' => 'nullable|string|max:255',
        ]);

        $record = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-01 08:00:00'),
        ]);
        $weightData = TrackableData::create([
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $weight->uid,
            'value' => '72.4',
        ]);
        $noteData = TrackableData::create([
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $note->uid,
            'value' => 'Morning check-in',
        ]);

        $response = $this->actingAs($user)->put(route('trackables.records.update', [$trackable->uid, $record->uid]), [
            'record_date' => '2026-02-25T18:30',
            $weight->uid => '74.1',
            $note->uid => 'Evening check-in',
        ]);

        $response->assertRedirect(route('trackables.show', $trackable->uid));
        $response->assertSessionHas('status', 'Record updated successfully.');

        $this->assertSame(2, TrackableData::count());
        $this->assertDatabaseHas('trackable_data', [
            'uid' => $weightData->uid,
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $weight->uid,
            'value' => '74.1',
        ]);
        $this->assertDatabaseHas('trackable_data', [
            'uid' => $noteData->uid,
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $note->uid,
            'value' => 'Evening check-in',
        ]);
        $record->refresh();
        $this->assertSame('2026-02-25 18:30:00', Carbon::parse($record->record_date)->format('Y-m-d H:i:s'));
    }

    public function test_posting_record_data_rejects_future_measurement_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 10:00:00'));

        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Vitals',
        ]);

        $weight = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Weight',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);

        $response = $this->from(route('trackables.records.create', $trackable->uid))
            ->actingAs($user)
            ->post(route('trackables.records.store', $trackable->uid), [
                'record_date' => '2026-04-16T07:45',
                $weight->uid => '72.4',
            ]);

        $response->assertRedirect(route('trackables.records.create', $trackable->uid));
        $response->assertSessionHasErrors('record_date');
        $this->assertDatabaseCount('trackable_records', 0);

        Carbon::setTestNow();
    }

    public function test_deleting_record_removes_record_and_related_values(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Vitals',
        ]);

        $weight = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Weight',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);

        $record = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-01 08:00:00'),
        ]);

        TrackableData::create([
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $weight->uid,
            'value' => '72.4',
        ]);

        $response = $this->actingAs($user)->delete(route('trackables.records.destroy', [$trackable->uid, $record->uid]));

        $response->assertRedirect(route('trackables.show', $trackable->uid));
        $response->assertSessionHas('status', 'Record deleted successfully.');
        $this->assertDatabaseMissing('trackable_records', [
            'uid' => $record->uid,
        ]);
        $this->assertDatabaseMissing('trackable_data', [
            'trackable_record_uid' => $record->uid,
        ]);
    }

    public function test_show_page_applies_filters_sort_and_pagination(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Vitals',
        ]);

        $note = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Note',
            'field_type' => 'string',
            'validation_rule' => 'nullable|string|max:255',
        ]);

        $firstRecord = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-01-01 08:00:00'),
        ]);
        $secondRecord = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-02-15 08:00:00'),
        ]);
        $thirdRecord = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-20 08:00:00'),
        ]);

        TrackableData::create([
            'trackable_record_uid' => $firstRecord->uid,
            'trackable_schema_uid' => $note->uid,
            'value' => 'alpha',
        ]);
        TrackableData::create([
            'trackable_record_uid' => $secondRecord->uid,
            'trackable_schema_uid' => $note->uid,
            'value' => 'beta target',
        ]);
        TrackableData::create([
            'trackable_record_uid' => $thirdRecord->uid,
            'trackable_schema_uid' => $note->uid,
            'value' => 'gamma',
        ]);

        $response = $this->actingAs($user)->get(route('trackables.show', [
            'trackable' => $trackable->uid,
            'q' => 'target',
            'date_from' => '2026-02-01',
            'date_to' => '2026-02-28',
            'sort_by' => 'record_date',
            'sort_dir' => 'asc',
            'per_page' => 25,
        ]));

        $response->assertOk();
        $response->assertSee('beta target');

        $records = $response->viewData('records');

        $this->assertSame(1, $records->total());
        $this->assertSame(25, $records->perPage());
        $this->assertSame($secondRecord->uid, $records->items()[0]->uid);
        $this->assertSame('beta target', $records->items()[0]->data->first()->value);
    }

    public function test_show_page_filters_by_specific_schema_field(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Fuel price log',
        ]);

        $fuelType = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Fuel Type ID',
            'field_type' => 'int',
            'validation_rule' => 'required|integer',
        ]);

        $pumpId = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Pump ID',
            'field_type' => 'int',
            'validation_rule' => 'required|integer',
        ]);

        $dieselRecord = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-01 08:00:00'),
        ]);
        $petrolRecord = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-02 08:00:00'),
        ]);

        TrackableData::create([
            'trackable_record_uid' => $dieselRecord->uid,
            'trackable_schema_uid' => $fuelType->uid,
            'value' => '2',
        ]);
        TrackableData::create([
            'trackable_record_uid' => $dieselRecord->uid,
            'trackable_schema_uid' => $pumpId->uid,
            'value' => '2349',
        ]);
        TrackableData::create([
            'trackable_record_uid' => $petrolRecord->uid,
            'trackable_schema_uid' => $fuelType->uid,
            'value' => '1',
        ]);
        TrackableData::create([
            'trackable_record_uid' => $petrolRecord->uid,
            'trackable_schema_uid' => $pumpId->uid,
            'value' => '9999',
        ]);

        $response = $this->actingAs($user)->get(route('trackables.show', [
            'trackable' => $trackable->uid,
            'schema' => [
                $fuelType->uid => '2',
                $pumpId->uid => '2349',
            ],
        ]));

        $response->assertOk();
        $response->assertSee('2');
        $response->assertSee('2349');
        $response->assertDontSee('9999');

        $records = $response->viewData('records');

        $this->assertSame(1, $records->total());
        $this->assertSame($dieselRecord->uid, $records->items()[0]->uid);
    }

    public function test_show_page_respects_per_page_selection(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Vitals',
        ]);

        for ($i = 1; $i <= 30; $i++) {
            TrackableRecord::create([
                'trackable_uid' => $trackable->uid,
                'record_date' => Carbon::parse('2026-01-01 08:00:00')->addDays($i),
            ]);
        }

        $response = $this->actingAs($user)->get(route('trackables.show', [
            'trackable' => $trackable->uid,
            'per_page' => 25,
        ]));

        $response->assertOk();

        $records = $response->viewData('records');

        $this->assertSame(25, $records->perPage());
        $this->assertCount(25, $records->items());
        $this->assertSame(30, $records->total());
    }

    public function test_statistics_page_is_accessible_and_builds_graph_data(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-24 12:00:00'));

        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Sensor data',
        ]);

        $temperature = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Temperature',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);
        $humidity = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Humidity',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);

        $oldRecord = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2025-08-01 08:00:00'),
        ]);
        $dayOneMorning = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-20 08:00:00'),
        ]);
        $dayOneEvening = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-20 20:00:00'),
        ]);

        TrackableData::create([
            'trackable_record_uid' => $oldRecord->uid,
            'trackable_schema_uid' => $temperature->uid,
            'value' => '10.5',
        ]);
        TrackableData::create([
            'trackable_record_uid' => $dayOneMorning->uid,
            'trackable_schema_uid' => $temperature->uid,
            'value' => '19.5',
        ]);
        TrackableData::create([
            'trackable_record_uid' => $dayOneMorning->uid,
            'trackable_schema_uid' => $humidity->uid,
            'value' => '60.1',
        ]);
        TrackableData::create([
            'trackable_record_uid' => $dayOneEvening->uid,
            'trackable_schema_uid' => $temperature->uid,
            'value' => '21.0',
        ]);
        TrackableData::create([
            'trackable_record_uid' => $dayOneEvening->uid,
            'trackable_schema_uid' => $humidity->uid,
            'value' => '58.4',
        ]);

        TrackableGraph::create([
            'trackable_uid' => $trackable->uid,
            'title' => 'Daily temperature',
            'graph_type' => 'line',
            'range_type' => 'last_6_months',
            'bucket_size' => 'day',
            'aggregate' => 'latest',
            'sampling' => 'daily_latest',
            'schema_uids' => [$temperature->uid],
            'filters' => [],
        ]);

        $response = $this->actingAs($user)->get(route('trackables.statistics', $trackable->uid));

        $response->assertOk();
        $response->assertSee('Daily temperature');

        $graphs = $response->viewData('graphs');

        $this->assertCount(1, $graphs);
        $this->assertSame(['2026-03-20'], $graphs[0]['chart']['labels']->all());
        $this->assertSame([21.0], $graphs[0]['chart']['datasets'][0]['data']->all());

        Carbon::setTestNow();
    }

    public function test_posting_graph_definition_creates_saved_graph(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Sensor data',
        ]);
        $temperature = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Temperature',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);
        $humidity = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Humidity',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);

        $response = $this->actingAs($user)->post(route('trackables.statistics.graphs.store', $trackable->uid), [
            'title' => 'Environment overview',
            'graph_type' => 'line',
            'range_type' => 'all_time',
            'bucket_size' => 'raw',
            'aggregate' => 'latest',
            'schema_uids' => [$temperature->uid, $humidity->uid],
            'filters' => [],
        ]);

        $response->assertRedirect(route('trackables.statistics', $trackable->uid));
        $response->assertSessionHas('status', 'Graph added successfully.');
        $this->assertDatabaseHas('trackable_graphs', [
            'trackable_uid' => $trackable->uid,
            'title' => 'Environment overview',
            'graph_type' => 'line',
            'range_type' => 'all_time',
            'bucket_size' => 'raw',
            'aggregate' => 'latest',
        ]);
    }

    public function test_statistics_page_applies_graph_filters_and_aggregation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-24 12:00:00'));

        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Fuel prices',
        ]);

        $price = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Price',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);
        $pumpId = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Pump ID',
            'field_type' => 'int',
            'validation_rule' => 'required|integer',
        ]);
        $fuelType = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Fuel Type',
            'field_type' => 'string',
            'validation_rule' => 'required|string',
        ]);

        $firstRecord = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-20 08:00:00'),
        ]);
        $secondRecord = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-20 18:00:00'),
        ]);
        $ignoredRecord = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-20 12:00:00'),
        ]);

        foreach ([
            [$firstRecord, $price, '1.70'],
            [$firstRecord, $pumpId, '2349'],
            [$firstRecord, $fuelType, 'diesel'],
            [$secondRecord, $price, '1.80'],
            [$secondRecord, $pumpId, '2349'],
            [$secondRecord, $fuelType, 'diesel'],
            [$ignoredRecord, $price, '1.50'],
            [$ignoredRecord, $pumpId, '1111'],
            [$ignoredRecord, $fuelType, 'petrol'],
        ] as [$record, $schema, $value]) {
            TrackableData::create([
                'trackable_record_uid' => $record->uid,
                'trackable_schema_uid' => $schema->uid,
                'value' => $value,
            ]);
        }

        TrackableGraph::create([
            'trackable_uid' => $trackable->uid,
            'title' => 'Diesel at pump 2349',
            'graph_type' => 'line',
            'range_type' => 'all_time',
            'bucket_size' => 'day',
            'aggregate' => 'average',
            'sampling' => 'all',
            'schema_uids' => [$price->uid],
            'filters' => [
                $pumpId->uid => '2349',
                $fuelType->uid => 'diesel',
            ],
        ]);

        $response = $this->actingAs($user)->get(route('trackables.statistics', $trackable->uid));

        $response->assertOk();
        $response->assertSee('Diesel at pump 2349');
        $response->assertSee('Pump ID: 2349');

        $graphs = $response->viewData('graphs');

        $this->assertSame(['2026-03-20'], $graphs[0]['chart']['labels']->all());
        $this->assertSame([1.75], $graphs[0]['chart']['datasets'][0]['data']->all());

        Carbon::setTestNow();
    }

    public function test_updating_graph_definition_changes_saved_configuration(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Sensor data',
        ]);
        $temperature = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Temperature',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);
        $note = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Location',
            'field_type' => 'string',
            'validation_rule' => 'required|string',
        ]);

        $graph = TrackableGraph::create([
            'trackable_uid' => $trackable->uid,
            'title' => 'Original',
            'graph_type' => 'line',
            'range_type' => 'all_time',
            'bucket_size' => 'raw',
            'aggregate' => 'latest',
            'sampling' => 'all',
            'schema_uids' => [$temperature->uid],
            'filters' => [],
        ]);

        $response = $this->actingAs($user)->put(route('trackables.statistics.graphs.update', [$trackable->uid, $graph->uid]), [
            'title' => 'Updated',
            'graph_type' => 'bar',
            'range_type' => 'last_30_days',
            'bucket_size' => 'week',
            'aggregate' => 'max',
            'schema_uids' => [$temperature->uid],
            'filters' => [
                $note->uid => 'warehouse',
            ],
        ]);

        $response->assertRedirect(route('trackables.statistics', $trackable->uid));
        $response->assertSessionHas('status', 'Graph updated successfully.');
        $this->assertDatabaseHas('trackable_graphs', [
            'uid' => $graph->uid,
            'title' => 'Updated',
            'graph_type' => 'bar',
            'range_type' => 'last_30_days',
            'bucket_size' => 'week',
            'aggregate' => 'max',
        ]);
    }
}
