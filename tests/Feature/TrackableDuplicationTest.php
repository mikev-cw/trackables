<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableData;
use App\Models\TrackableEnum;
use App\Models\TrackableGraph;
use App\Models\TrackableGroup;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackableDuplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplication_copies_schema_and_statistics_without_records(): void
    {
        $user = User::factory()->create();
        $group = TrackableGroup::create(['user_id' => $user->id, 'name' => 'Health']);
        $source = Trackable::factory()->create([
            'user_id' => $user->id,
            'group_uid' => $group->uid,
            'name' => 'Daily health',
        ]);
        $number = TrackableSchema::factory()->create([
            'trackable_uid' => $source->uid,
            'name' => 'Weight',
            'alias' => 'body_weight',
            'field_type' => 'float',
            'validation_config' => ['required' => true, 'min' => 1, 'max' => 500],
        ]);
        $enum = TrackableEnum::create(['user_id' => $user->id, 'name' => 'Locations']);
        $location = TrackableSchema::factory()->create([
            'trackable_uid' => $source->uid,
            'name' => 'Location',
            'field_type' => 'enum',
            'enum_uid' => $enum->uid,
        ]);
        TrackableSchema::factory()->create([
            'trackable_uid' => $source->uid,
            'name' => 'Calculated',
            'field_type' => 'calc',
            'calc_formula' => '{"field":"body_weight"}',
        ]);
        $graph = TrackableGraph::create([
            'trackable_uid' => $source->uid,
            'title' => 'Weekly weight',
            'graph_type' => 'bar',
            'range_type' => 'last_6_months',
            'sampling' => 'all',
            'bucket_size' => 'week',
            'aggregate' => 'average',
            'schema_uids' => [$number->uid],
            'filters' => [$location->uid => 'Home', $number->uid => '75'],
        ]);
        $record = TrackableRecord::create(['trackable_uid' => $source->uid, 'record_date' => now()]);
        TrackableData::create([
            'trackable_record_uid' => $record->uid,
            'trackable_schema_uid' => $number->uid,
            'value' => '75',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()->assertSee(route('trackables.duplicate', $source), false);
        $this->get(route('trackables.edit', $source))
            ->assertOk()->assertSee(route('trackables.duplicate', $source), false);
        $this->get(route('trackables.duplicate', $source))
            ->assertOk()
            ->assertSee('Daily health (copy)')
            ->assertSee('Cancel')
            ->assertSee(route('trackables.duplicate.store', $source), false);
        $this->assertDatabaseCount('trackables', 1);
        $this->assertDatabaseCount('trackable_schemas', 3);
        $this->assertDatabaseCount('trackable_graphs', 1);

        $response = $this->post(route('trackables.duplicate.store', $source), [
            'name' => 'My new health tracker',
            'alias' => 'new_health',
            'group_uid' => $group->uid,
        ]);

        $copy = Trackable::where('uid', '!=', $source->uid)->sole();
        $response->assertRedirect(route('trackables.edit', $copy))
            ->assertSessionHas('status');
        $this->assertSame('My new health tracker', $copy->name);
        $this->assertSame('new_health', $copy->alias);
        $this->assertNotSame($source->alias, $copy->alias);
        $this->assertSame($user->id, $copy->user_id);
        $this->assertSame($group->uid, $copy->group_uid);
        $this->assertSame(0, $copy->records()->count());
        $this->assertCount(3, $copy->schema);

        foreach ($source->schema as $field) {
            $newField = $copy->schema->firstWhere('alias', $field->alias);
            $this->assertNotNull($newField);
            $this->assertNotSame($field->uid, $newField->uid);
            $this->assertSame($field->only(['name', 'alias', 'field_type', 'enum_uid', 'calc_formula', 'validation_config']),
                $newField->only(['name', 'alias', 'field_type', 'enum_uid', 'calc_formula', 'validation_config']));
        }

        $newNumber = $copy->schema->firstWhere('alias', $number->alias);
        $newLocation = $copy->schema->firstWhere('alias', $location->alias);
        $newGraph = $copy->graphs()->sole();
        $this->assertNotSame($graph->uid, $newGraph->uid);
        $this->assertSame($graph->only(['title', 'graph_type', 'range_type', 'sampling', 'bucket_size', 'aggregate']),
            $newGraph->only(['title', 'graph_type', 'range_type', 'sampling', 'bucket_size', 'aggregate']));
        $this->assertSame([$newNumber->uid], $newGraph->schema_uids);
        $this->assertSame([$newLocation->uid => 'Home', $newNumber->uid => '75'], $newGraph->filters);
        $this->assertDatabaseCount('trackable_records', 1);
        $this->assertDatabaseCount('trackable_data', 1);
        $this->assertDatabaseCount('enums', 1);
        $this->assertSame([$number->uid], $graph->fresh()->schema_uids);
        $this->get(route('trackables.statistics', $copy))->assertOk()->assertSee('Weekly weight');
        $this->get(route('trackables.show', $copy))->assertOk();
    }

    public function test_empty_disabled_trackable_can_be_copied_repeatedly_with_unique_aliases(): void
    {
        $user = User::factory()->create();
        $source = Trackable::factory()->deleted()->create([
            'user_id' => $user->id,
            'name' => str_repeat('A', 255),
        ]);
        $this->actingAs($user);

        for ($i = 0; $i < 2; $i++) {
            $page = $this->get(route('trackables.duplicate', $source))->assertOk();
            $draft = $page->viewData('trackable');
            $this->assertFalse($draft->exists);
            $this->post(route('trackables.duplicate.store', $source), [
                'name' => $draft->name,
                'alias' => $draft->alias,
            ])->assertRedirect();
        }

        $copies = Trackable::where('uid', '!=', $source->uid)->get();
        $this->assertCount(2, $copies);
        $this->assertCount(2, $copies->pluck('alias')->unique());
        foreach ($copies as $copy) {
            $this->assertSame(255, mb_strlen($copy->name));
            $this->assertStringEndsWith(' (copy)', $copy->name);
            $this->assertSame(0, $copy->deleted);
            $this->assertNull($copy->group_uid);
            $this->assertSame(0, $copy->schema()->count());
            $this->assertSame(0, $copy->graphs()->count());
            $this->assertSame(0, $copy->records()->count());
        }
        $this->assertSame(1, $source->fresh()->deleted);
    }

    public function test_cancel_and_invalid_submissions_do_not_create_a_copy(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $source = Trackable::factory()->create(['user_id' => $user->id]);
        $otherGroup = TrackableGroup::create(['user_id' => $other->id, 'name' => 'Private']);
        $url = route('trackables.duplicate', $source);

        $this->actingAs($user)->get($url)->assertOk()
            ->assertSee('href="'.route('dashboard').'"', false);
        $this->get(route('dashboard'))->assertOk();
        $this->assertDatabaseCount('trackables', 1);

        $this->from($url)->post(route('trackables.duplicate.store', $source), ['name' => ''])
            ->assertRedirect($url)->assertSessionHasErrors('name');
        $this->from($url)->post(route('trackables.duplicate.store', $source), [
            'name' => 'Unsaved name',
            'alias' => 'unsaved_alias',
            'group_uid' => $otherGroup->uid,
        ])->assertRedirect($url)->assertSessionHasErrors('group_uid');
        $this->get($url)->assertOk()->assertSee('Unsaved name')->assertSee('unsaved_alias');
        $this->assertDatabaseCount('trackables', 1);
        $this->assertDatabaseCount('trackable_schemas', 0);
        $this->assertDatabaseCount('trackable_graphs', 0);
    }

    public function test_duplication_requires_authentication_and_ownership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $source = Trackable::factory()->create(['user_id' => $owner->id]);

        $this->get(route('trackables.duplicate', $source))->assertRedirect(route('login'));
        $this->post(route('trackables.duplicate.store', $source), ['name' => 'Copy'])->assertRedirect(route('login'));
        $this->actingAs($other)->get(route('trackables.duplicate', $source))->assertForbidden();
        $this->post(route('trackables.duplicate.store', $source), ['name' => 'Copy'])->assertForbidden();
        $this->assertDatabaseCount('trackables', 1);
    }
}
