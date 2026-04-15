<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTrackableAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_trackable_schema_resource_exposes_alias(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Fuel log',
            'alias' => 'fuel_log',
        ]);

        TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Pump Name',
            'alias' => 'pump_name',
            'field_type' => 'string',
            'validation_rule' => 'required|string',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/trackable');

        $response->assertOk();
        $response->assertJsonPath('data.0.alias', 'fuel_log');
        $response->assertJsonPath('data.0.schema.0.alias', 'pump_name');
    }

    public function test_api_bulk_record_creation_accepts_schema_aliases(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Fuel log',
            'alias' => 'fuel_log',
        ]);

        $pumpName = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Pump Name',
            'alias' => 'pump_name',
            'field_type' => 'string',
            'validation_rule' => 'required|string',
        ]);

        $price = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Price',
            'alias' => 'price',
            'field_type' => 'float',
            'validation_rule' => 'required|numeric',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/trackable/{$trackable->alias}/record", [
            'records' => [
                [
                    'record_date' => '2026-04-10T07:45:00+02:00',
                    'pump_name' => 'Esso Via Roma 4',
                    'price' => '1.799',
                ],
            ],
        ]);

        $response->assertCreated();
        $record = TrackableRecord::first();

        $this->assertSame('2026-04-10 07:45:00', Carbon::parse($record->record_date)->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('trackable_data', [
            'trackable_schema_uid' => $pumpName->uid,
            'value' => 'Esso Via Roma 4',
        ]);
        $this->assertDatabaseHas('trackable_data', [
            'trackable_schema_uid' => $price->uid,
            'value' => '1.799',
        ]);
    }

    public function test_api_single_record_creation_accepts_record_date_and_aliases(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Fuel log',
            'alias' => 'fuel_log',
        ]);

        $pumpName = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Pump Name',
            'alias' => 'pump_name',
            'field_type' => 'string',
            'validation_rule' => 'required|string',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/trackable/{$trackable->alias}/record", [
            'record_date' => '2026-04-11T19:10:00+02:00',
            'pump_name' => 'Eni Central Station',
        ]);

        $response->assertCreated();
        $record = TrackableRecord::latest('created_at')->first();

        $this->assertSame('2026-04-11 19:10:00', Carbon::parse($record->record_date)->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('trackable_data', [
            'trackable_schema_uid' => $pumpName->uid,
            'value' => 'Eni Central Station',
        ]);
    }
}
