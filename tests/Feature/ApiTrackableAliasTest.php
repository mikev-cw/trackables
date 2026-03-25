<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableSchema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                    'pump_name' => 'Esso Via Roma 4',
                    'price' => '1.799',
                ],
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('trackable_data', [
            'trackable_schema_uid' => $pumpName->uid,
            'value' => 'Esso Via Roma 4',
        ]);
        $this->assertDatabaseHas('trackable_data', [
            'trackable_schema_uid' => $price->uid,
            'value' => '1.799',
        ]);
    }
}
