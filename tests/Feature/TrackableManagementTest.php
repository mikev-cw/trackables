<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableGroup;
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
        $group = TrackableGroup::create([
            'user_id' => $user->id,
            'name' => 'Transport',
        ]);

        $createResponse = $this->actingAs($user)->post(route('trackables.store'), [
            'name' => 'Office climate',
            'alias' => 'office_climate',
            'group_uid' => $group->uid,
        ]);

        $trackable = Trackable::first();

        $createResponse->assertRedirect(route('trackables.edit', $trackable->uid));
        $this->assertSame('Office climate', $trackable->name);
        $this->assertSame('office_climate', $trackable->alias);
        $this->assertSame($group->uid, $trackable->group_uid);
        $this->assertSame(0, $trackable->deleted);

        $updateResponse = $this->actingAs($user)->put(route('trackables.update', $trackable->uid), [
            'name' => 'Office climate sensors',
            'alias' => 'office_climate_sensors',
            'group_uid' => null,
        ]);

        $updateResponse->assertRedirect(route('trackables.edit', $trackable->uid));
        $this->assertDatabaseHas('trackables', [
            'uid' => $trackable->uid,
            'name' => 'Office climate sensors',
            'alias' => 'office_climate_sensors',
            'group_uid' => null,
        ]);

        $toggleResponse = $this->actingAs($user)->patch(route('trackables.toggle', $trackable->uid));

        $toggleResponse->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('trackables', [
            'uid' => $trackable->uid,
            'deleted' => 1,
        ]);
    }

    public function test_dashboard_arranges_trackables_by_group_when_groups_are_configured(): void
    {
        $user = User::factory()->create();
        $transport = TrackableGroup::create([
            'user_id' => $user->id,
            'name' => 'Transport',
            'description' => 'Trips and vehicle costs',
        ]);

        $fuel = Trackable::create([
            'user_id' => $user->id,
            'group_uid' => $transport->uid,
            'name' => 'Fuel prices',
        ]);
        Trackable::create([
            'user_id' => $user->id,
            'group_uid' => $transport->uid,
            'name' => 'Planes taken',
        ]);
        $sleep = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Sleep',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Transport');
        $response->assertSee('Trips and vehicle costs');
        $response->assertSee('Fuel prices');
        $response->assertSee('Planes taken');
        $response->assertSeeInOrder(['Transport', 'Ungrouped', 'Sleep']);
        $response->assertSee('data-bs-target="#dashboard-group-'.$transport->uid.'"', false);
        $response->assertSee('data-dashboard-group-key="group-'.$transport->uid.'"', false);
        $response->assertSee('id="dashboard-group-ungrouped"', false);
        $response->assertSee('data-dashboard-group-key="ungrouped"', false);
        $response->assertSee('trackables.dashboard.group.', false);
        $response->assertDontSee('>Toggle<', false);
        $response->assertSee(route('trackables.edit', $fuel->uid), false);
        $response->assertSee(route('trackables.edit', $sleep->uid), false);
    }

    public function test_grouped_dashboard_paginates_trackables(): void
    {
        $user = User::factory()->create();
        $group = TrackableGroup::create([
            'user_id' => $user->id,
            'name' => 'Transport',
        ]);

        for ($index = 1; $index <= 13; $index++) {
            Trackable::create([
                'user_id' => $user->id,
                'group_uid' => $group->uid,
                'name' => 'Transport metric '.$index,
            ]);
        }

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $this->assertSame(12, substr_count($response->getContent(), 'Open records'));
        $response->assertSee('page=2', false);
    }

    public function test_groups_can_be_created_updated_and_disabled_from_web_pages(): void
    {
        $user = User::factory()->create();

        $createResponse = $this->actingAs($user)->post(route('trackable-groups.store'), [
            'name' => 'Transport',
            'description' => 'Fuel, refueling, and flights',
        ]);

        $group = TrackableGroup::first();

        $createResponse->assertRedirect(route('trackable-groups.index'));
        $this->assertSame('Transport', $group->name);
        $this->assertSame('Fuel, refueling, and flights', $group->description);
        $this->assertSame(0, $group->deleted);

        $updateResponse = $this->actingAs($user)->put(route('trackable-groups.update', $group->uid), [
            'name' => 'Mobility',
            'description' => 'Movement and travel',
        ]);

        $updateResponse->assertRedirect(route('trackable-groups.edit', $group->uid));
        $this->assertDatabaseHas('trackable_groups', [
            'uid' => $group->uid,
            'name' => 'Mobility',
            'description' => 'Movement and travel',
        ]);

        $toggleResponse = $this->actingAs($user)->patch(route('trackable-groups.toggle', $group->uid));

        $toggleResponse->assertRedirect(route('trackable-groups.index'));
        $this->assertDatabaseHas('trackable_groups', [
            'uid' => $group->uid,
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
        ]);
        $this->assertSame('nullable|string|max:255', $field->fresh()->validation_rule);
    }

    public function test_schema_page_generates_validation_rules_from_structured_config(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Fuel prices',
        ]);

        $createResponse = $this->actingAs($user)->post(route('trackables.schema.store', $trackable->uid), [
            'name' => 'Liters',
            'field_type' => 'float',
            'validation_config' => [
                'required' => '1',
                'min' => '0',
                'max' => '200',
            ],
        ]);

        $field = TrackableSchema::first();

        $createResponse->assertRedirect(route('trackables.schema.edit', $trackable->uid));
        $field = $field->fresh();

        $this->assertSame('required|numeric|min:0|max:200', $field->validation_rule);
        $this->assertSame([
            'required' => true,
            'min' => 0,
            'max' => 200,
            'max_length' => null,
            'format' => null,
        ], $field->validation_config);
    }

    public function test_schema_page_can_add_seeded_schema_presets(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Places',
        ]);

        $response = $this->actingAs($user)->post(route('trackables.schema.presets.store', $trackable->uid), [
            '_schema_form' => 'preset',
            'preset' => 'location_coordinates',
        ]);

        $response->assertRedirect(route('trackables.schema.edit', $trackable->uid));
        $this->assertDatabaseHas('trackable_schemas', [
            'trackable_uid' => $trackable->uid,
            'name' => 'Latitude',
            'alias' => 'latitude',
            'field_type' => 'float',
        ]);
        $this->assertDatabaseHas('trackable_schemas', [
            'trackable_uid' => $trackable->uid,
            'name' => 'Longitude',
            'alias' => 'longitude',
            'field_type' => 'float',
        ]);

        $latitude = TrackableSchema::where('trackable_uid', $trackable->uid)->where('alias', 'latitude')->first();
        $longitude = TrackableSchema::where('trackable_uid', $trackable->uid)->where('alias', 'longitude')->first();

        $this->assertSame('nullable|numeric|min:-90|max:90', $latitude->validation_rule);
        $this->assertSame('nullable|numeric|min:-180|max:180', $longitude->validation_rule);
    }

    public function test_schema_preset_fails_when_any_preset_alias_already_exists(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create([
            'user_id' => $user->id,
            'name' => 'Places',
        ]);

        TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Existing Latitude',
            'alias' => 'latitude',
            'field_type' => 'float',
            'validation_rule' => 'nullable|numeric',
        ]);

        $response = $this->actingAs($user)
            ->from(route('trackables.schema.edit', $trackable->uid))
            ->post(route('trackables.schema.presets.store', $trackable->uid), [
                '_schema_form' => 'preset',
                'preset' => 'location_coordinates',
            ]);

        $response->assertRedirect(route('trackables.schema.edit', $trackable->uid));
        $response->assertSessionHasErrors('preset');

        $this->assertDatabaseMissing('trackable_schemas', [
            'trackable_uid' => $trackable->uid,
            'alias' => 'longitude',
        ]);
        $this->assertSame(1, TrackableSchema::where('trackable_uid', $trackable->uid)->count());
    }
}
