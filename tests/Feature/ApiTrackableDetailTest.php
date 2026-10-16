<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableEnum;
use App\Models\TrackableEnumValue;
use App\Models\TrackableSchema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTrackableDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_accepts_uid_and_alias_and_returns_updated_enum_values(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create(['user_id' => $user->id, 'name' => 'Orders']);
        $enum = TrackableEnum::create(['user_id' => $user->id, 'name' => 'Status']);
        TrackableSchema::create([
            'trackable_uid' => $trackable->uid,
            'name' => 'Status',
            'field_type' => 'enum',
            'enum_uid' => $enum->uid,
        ]);
        $value = TrackableEnumValue::create([
            'enum_uid' => $enum->uid,
            'value' => 'pending',
            'label' => 'Pending',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/trackable/{$trackable->uid}")
            ->assertOk()
            ->assertJsonPath('data.uid', $trackable->uid)
            ->assertJsonPath('data.alias', $trackable->alias)
            ->assertJsonPath('data.schema.0.enum.uid', $enum->uid)
            ->assertJsonPath('data.schema.0.enum.values.0.uid', $value->uid)
            ->assertJsonPath('data.schema.0.enum.values.0.key', 'pending')
            ->assertJsonPath('data.schema.0.enum.values.0.label', 'Pending');

        $value->update(['label' => 'Awaiting review', 'is_active' => false]);
        TrackableEnumValue::create([
            'enum_uid' => $enum->uid,
            'value' => 'approved',
            'label' => 'Approved',
            'is_active' => true,
            'position' => 1,
        ]);

        $this->getJson("/api/trackable/{$trackable->alias}")
            ->assertOk()
            ->assertJsonPath('data.uid', $trackable->uid)
            ->assertJsonCount(2, 'data.schema.0.enum.values')
            ->assertJsonPath('data.schema.0.enum.values.0.label', 'Awaiting review')
            ->assertJsonPath('data.schema.0.enum.values.0.is_active', false)
            ->assertJsonPath('data.schema.0.enum.values.1.key', 'approved');
    }

    public function test_detail_requires_authentication(): void
    {
        $this->getJson('/api/trackable/orders')->assertUnauthorized();
    }

    public function test_detail_rejects_another_users_trackable(): void
    {
        $trackable = Trackable::create(['user_id' => User::factory()->create()->id, 'name' => 'Orders']);
        Sanctum::actingAs(User::factory()->create());

        foreach ([$trackable->uid, $trackable->alias] as $identifier) {
            $this->getJson("/api/trackable/{$identifier}")->assertForbidden();
        }
    }

    public function test_detail_returns_not_found_for_deleted_or_missing_trackables(): void
    {
        $user = User::factory()->create();
        $trackable = Trackable::create(['user_id' => $user->id, 'name' => 'Orders', 'deleted' => 1]);
        Sanctum::actingAs($user);

        foreach ([$trackable->uid, $trackable->alias, 'missing'] as $identifier) {
            $this->getJson("/api/trackable/{$identifier}")->assertNotFound();
        }
    }
}
