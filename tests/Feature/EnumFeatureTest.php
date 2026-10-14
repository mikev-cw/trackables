<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableData;
use App\Models\TrackableEnum;
use App\Models\TrackableEnumValue;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnumFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_reusable_list_with_stable_keys(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('enums.store'), [
            'name' => 'Order status',
            'values' => [
                ['key' => 'pending', 'label' => 'Pending', 'description' => 'Waiting to be processed'],
                ['key' => 'in progress', 'label' => 'In progress', 'description' => ''],
            ],
        ]);

        $enum = TrackableEnum::first();

        $response->assertRedirect(route('enums.edit', $enum->uid));
        $this->assertSame($user->id, $enum->user_id);
        $this->assertDatabaseHas('enum_values', [
            'enum_uid' => $enum->uid,
            'value' => 'pending',
            'label' => 'Pending',
            'description' => 'Waiting to be processed',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('enum_values', [
            'enum_uid' => $enum->uid,
            'value' => 'in_progress',
            'label' => 'In progress',
        ]);
    }

    public function test_enum_record_fields_store_only_active_value_uids(): void
    {
        [$user, $trackable, $field, $active, $retired] = $this->enumFixture();

        $page = $this->actingAs($user)->get(route('trackables.records.create', $trackable->uid));
        $page->assertOk()->assertSee('Pending')->assertDontSee('Cancelled');
        $page->assertSee('value="'.$active->uid.'"', false);

        $invalid = $this->from(route('trackables.records.create', $trackable->uid))
            ->actingAs($user)
            ->post(route('trackables.records.store', $trackable->uid), [
                $field->uid => $active->value,
            ]);
        $invalid->assertSessionHasErrors($field->uid);

        $valid = $this->actingAs($user)->post(route('trackables.records.store', $trackable->uid), [
            $field->uid => $active->uid,
        ]);
        $valid->assertRedirect(route('trackables.show', $trackable->uid));
        $this->assertDatabaseHas('trackable_data', [
            'trackable_schema_uid' => $field->uid,
            'value' => $active->uid,
        ]);

        $rejectedRetired = $this->actingAs($user)->post(route('trackables.records.store', $trackable->uid), [
            $field->uid => $retired->uid,
        ]);
        $rejectedRetired->assertSessionHasErrors($field->uid);
    }

    public function test_retired_value_remains_valid_for_the_record_that_already_uses_it(): void
    {
        [$user, $trackable, $field, , $retired] = $this->enumFixture();
        $record = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => now()->subDay(),
        ]);
        TrackableData::create([
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $field->uid,
            'value' => $retired->uid,
        ]);

        $page = $this->actingAs($user)->get(route('trackables.records.edit', [$trackable->uid, $record->uid]));
        $page->assertOk()->assertSee('Cancelled (retired)');

        $response = $this->actingAs($user)->put(route('trackables.records.update', [$trackable->uid, $record->uid]), [
            'record_date' => now()->subDay()->format('Y-m-d\TH:i'),
            $field->uid => $retired->uid,
        ]);

        $response->assertRedirect(route('trackables.show', $trackable->uid));
        $this->assertDatabaseHas('trackable_data', [
            'trackable_record_uid' => $record->uid,
            'value' => $retired->uid,
        ]);
    }

    public function test_label_changes_update_presentation_without_changing_stored_uid(): void
    {
        [$user, $trackable, $field, $active] = $this->enumFixture();
        $record = TrackableRecord::create([
            'trackable_uid' => $trackable->uid,
            'record_date' => now()->subDay(),
        ]);
        TrackableData::create([
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $field->uid,
            'value' => $active->uid,
        ]);

        $this->actingAs($user)->put(route('enums.values.update', [$active->enum_uid, $active->uid]), [
            'label' => 'Awaiting review',
            'description' => 'Updated presentation text',
        ])->assertRedirect();

        $this->actingAs($user)->get(route('trackables.show', $trackable->uid))
            ->assertOk()
            ->assertSee('Awaiting review');
        $this->assertSame($active->uid, TrackableData::first()->value);
    }

    public function test_api_exposes_enum_metadata_and_validates_value_uids(): void
    {
        [$user, $trackable, $field, $active] = $this->enumFixture();
        Sanctum::actingAs($user);

        $this->getJson('/api/trackable')
            ->assertOk()
            ->assertJsonPath('data.0.schema.0.enum.values.0.uid', $active->uid)
            ->assertJsonPath('data.0.schema.0.enum.values.0.key', 'pending')
            ->assertJsonPath('data.0.schema.0.enum.values.0.label', 'Pending');

        $this->postJson("/api/trackable/{$trackable->uid}/record", [
            $field->alias => $active->value,
        ])->assertJsonValidationErrors($field->uid);

        $this->postJson("/api/trackable/{$trackable->uid}/record", [
            $field->alias => $active->uid,
        ])->assertCreated();
    }

    public function test_schema_cannot_use_another_users_list(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $enum = TrackableEnum::create(['user_id' => $owner->id, 'name' => 'Private list']);
        $trackable = Trackable::create(['user_id' => $otherUser->id, 'name' => 'Other trackable']);

        $response = $this->actingAs($otherUser)->post(route('trackables.schema.store', $trackable->uid), [
            'name' => 'Status',
            'field_type' => 'enum',
            'enum_uid' => $enum->uid,
        ]);

        $response->assertSessionHasErrors('enum_uid');
        $this->assertDatabaseCount('trackable_schemas', 0);
    }

    private function enumFixture(): array
    {
        $user = User::factory()->create();
        $trackable = Trackable::create(['user_id' => $user->id, 'name' => 'Orders']);
        $enum = TrackableEnum::create(['user_id' => $user->id, 'name' => 'Order status']);
        $active = TrackableEnumValue::create([
            'enum_uid' => $enum->uid,
            'value' => 'pending',
            'label' => 'Pending',
            'is_active' => true,
        ]);
        $retired = TrackableEnumValue::create([
            'enum_uid' => $enum->uid,
            'value' => 'cancelled',
            'label' => 'Cancelled',
            'is_active' => false,
            'position' => 1,
        ]);
        $field = TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Status',
            'alias' => 'status',
            'field_type' => 'enum',
            'enum_uid' => $enum->uid,
            'validation_config' => ['required' => true],
        ]);

        return [$user, $trackable, $field, $active, $retired];
    }
}
