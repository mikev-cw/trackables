<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableData;
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
            $weight->uid => '72.4',
            $note->uid => 'Morning check-in',
        ]);

        $response->assertRedirect(route('trackables.show', $trackable->uid));
        $response->assertSessionHas('status', 'Record added successfully.');

        $record = TrackableRecord::first();

        $this->assertNotNull($record);
        $this->assertSame($trackable->uid, $record->trackable_uid);
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
        $response->assertDontSee('alpha');
        $response->assertDontSee('gamma');

        $records = $response->viewData('records');

        $this->assertSame(1, $records->total());
        $this->assertSame(25, $records->perPage());
        $this->assertSame($secondRecord->uid, $records->items()[0]->uid);
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
}
