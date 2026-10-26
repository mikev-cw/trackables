<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\TrackableGroup;
use App\Models\TrackableRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_trackables_are_hidden_by_default_and_can_be_recovered(): void
    {
        $user = User::factory()->create();
        Trackable::factory()->create(['user_id' => $user->id, 'name' => 'Daily steps']);
        Trackable::factory()->deleted()->create(['user_id' => $user->id, 'name' => 'Archived walks']);
        Trackable::factory()->create(['user_id' => User::factory()->create()->id, 'name' => 'Private tracker']);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Daily steps')->assertDontSee('Archived walks')->assertDontSee('Private tracker');
        $this->get(route('dashboard', ['status' => 'disabled']))->assertOk()
            ->assertSee('Archived walks')->assertDontSee('Daily steps')->assertSee('Enable trackable');
        $this->get(route('dashboard', ['status' => 'all']))->assertOk()
            ->assertSee('Daily steps')->assertSee('Archived walks')->assertDontSee('Private tracker');
    }

    public function test_search_filters_the_entire_collection_before_pagination_and_preserves_filters(): void
    {
        $user = User::factory()->create();
        Trackable::factory()->count(26)->create(['user_id' => $user->id, 'name' => 'Daily measure']);
        $target = Trackable::factory()->create(['user_id' => $user->id, 'name' => 'Unique water', 'alias' => 'hydration']);
        $this->actingAs($user)->get(route('dashboard', ['q' => 'hydration']))->assertOk()
            ->assertSee('Unique water')->assertDontSee('Daily measure')->assertViewHas('list', fn ($list) => $list->total() === 1);
        $this->get(route('dashboard', ['q' => 'Daily', 'sort' => 'name']))->assertOk()
            ->assertViewHas('list', fn ($list) => $list->total() === 26 && $list->count() === 24)
            ->assertSee('q=Daily', false)->assertSee('sort=name', false)->assertSee('page=2', false);
        $this->get(route('dashboard', ['q' => '%']))->assertOk()->assertDontSee($target->name)->assertSee('No trackables found');
    }

    public function test_group_filter_is_scoped_and_disabled_groups_are_treated_as_ungrouped(): void
    {
        $user = User::factory()->create();
        $group = TrackableGroup::factory()->create(['user_id' => $user->id, 'name' => 'Wellbeing']);
        $disabled = TrackableGroup::factory()->create(['user_id' => $user->id, 'deleted' => 1]);
        $private = TrackableGroup::factory()->create(['user_id' => User::factory()->create()->id, 'name' => 'Private group']);
        Trackable::factory()->create(['user_id' => $user->id, 'group_uid' => $group->uid, 'name' => 'Sleep quality']);
        Trackable::factory()->create(['user_id' => $user->id, 'group_uid' => $disabled->uid, 'name' => 'Old group tracker']);
        Trackable::factory()->create(['user_id' => $user->id, 'name' => 'Loose tracker']);
        $this->actingAs($user)->get(route('dashboard', ['group' => $group->uid]))->assertOk()
            ->assertSee('Sleep quality')->assertDontSee('Old group tracker')->assertDontSee('Loose tracker')->assertDontSee('Private group');
        $this->get(route('dashboard', ['group' => 'ungrouped']))->assertOk()
            ->assertDontSee('Sleep quality')->assertSee('Old group tracker')->assertSee('Loose tracker');
        $this->get(route('dashboard', ['group' => $private->uid]))->assertOk()
            ->assertViewHas('list', fn ($list) => $list->total() === 0);
    }

    public function test_sorting_by_latest_record_and_name_is_supported(): void
    {
        $user = User::factory()->create();
        $alpha = Trackable::factory()->create(['user_id' => $user->id, 'name' => 'Alpha']);
        $zebra = Trackable::factory()->create(['user_id' => $user->id, 'name' => 'Zebra']);
        TrackableRecord::create(['trackable_uid' => $zebra->uid, 'record_date' => now()]);
        $this->actingAs($user)->get(route('dashboard', ['sort' => 'record']))->assertOk()
            ->assertViewHas('list', fn ($list) => $list->first()->uid === $zebra->uid);
        $this->get(route('dashboard', ['sort' => 'name']))->assertOk()
            ->assertViewHas('list', fn ($list) => $list->first()->uid === $alpha->uid);
    }

    public function test_empty_states_distinguish_a_new_workspace_from_an_archived_collection(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Create your first trackable');
        Trackable::factory()->deleted()->create(['user_id' => $user->id]);
        $this->get(route('dashboard'))->assertOk()->assertSee('View disabled trackables');
    }

    public function test_page_size_is_bounded_and_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->getJson(route('dashboard', ['per_page' => '10000']))
            ->assertUnprocessable();
    }
}
