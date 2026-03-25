<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TrackableManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_last_record_date_and_management_links(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Fuel prices',
            'deleted' => 0,
        ]);

        TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => Carbon::parse('2026-03-24 10:30:00'),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Fuel prices');
        $response->assertSee('Last record:');
        $response->assertSee(route('trackables.edit', $trackable->uid), false);
        $response->assertSee(route('trackables.schema.edit', $trackable->uid), false);
    }

    public function test_trackable_can_be_created_updated_and_toggled_from_web_pages(): void
    {
        $user = User::factory()->create();

        $createResponse = $this->actingAs($user)->post(route('trackables.store'), [
            'name' => 'Office climate',
        ]);

        $trackable = Trackable::first();

        $createResponse->assertRedirect(route('trackables.edit', $trackable->uid));
        $this->assertSame('Office climate', $trackable->name);
        $this->assertSame(0, $trackable->deleted);

        $updateResponse = $this->actingAs($user)->put(route('trackables.update', $trackable->uid), [
            'name' => 'Office climate sensors',
        ]);

        $updateResponse->assertRedirect(route('trackables.edit', $trackable->uid));
        $this->assertDatabaseHas('trackables', [
            'uid' => $trackable->uid,
            'name' => 'Office climate sensors',
        ]);

        $toggleResponse = $this->actingAs($user)->patch(route('trackables.toggle', $trackable->uid));

        $toggleResponse->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('trackables', [
            'uid' => $trackable->uid,
            'deleted' => 1,
        ]);
    }

    public function test_schema_page_allows_adding_and_updating_schema_fields(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Fuel prices',
        ]);

        $createResponse = $this->actingAs($user)->post(route('trackables.schema.store', $trackable->uid), [
            'name' => 'Pump Name',
            'alias' => 'pump_name',
            'field_type' => 'string',
            'validation_rule' => 'required|string',
        ]);

        $field = TrackableSchema::first();

        $createResponse->assertRedirect(route('trackables.schema.edit', $trackable->uid));
        $this->assertSame('Pump Name', $field->name);
        $this->assertSame('pump_name', $field->alias);

        $updateResponse = $this->actingAs($user)->put(route('trackables.schema.update', [$trackable->uid, $field->uid]), [
            'name' => 'Pump Label',
            'alias' => 'pump_label',
            'field_type' => 'string',
            'validation_rule' => 'nullable|string|max:255',
        ]);

        $updateResponse->assertRedirect(route('trackables.schema.edit', $trackable->uid));
        $this->assertDatabaseHas('trackable_schemas', [
            'uid' => $field->uid,
            'name' => 'Pump Label',
            'alias' => 'pump_label',
            'validation_rule' => 'nullable|string|max:255',
        ]);
    }
}
