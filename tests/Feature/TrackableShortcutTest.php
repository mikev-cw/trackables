<?php

namespace Tests\Feature;

use App\Models\Trackable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class TrackableShortcutTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_set_up_and_download_shortcuts_for_each_trackable(): void
    {
        $owner = User::factory()->create();
        foreach ([false, true] as $disabled) {
            $trackable = Trackable::factory()->create(['user_id' => $owner->id, 'deleted' => $disabled]);
            $this->actingAs($owner)->get(route('trackables.shortcut', $trackable))
                ->assertOk()->assertSee($trackable->uid)->assertSee(url('/api/trackable'))
                ->assertSee(route('trackables.shortcut.download', $trackable));
            $response = $this->get(route('trackables.shortcut.download', $trackable));
            $response->assertOk()->assertDownload();
            $this->assertSame(
                file_get_contents(base_path('docs/shortcuts/Trackables - New Record.shortcut')),
                file_get_contents($response->baseResponse->getFile()->getPathname())
            );
        }
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_shortcut_routes_require_authentication_and_ownership(): void
    {
        $trackable = Trackable::factory()->create(['user_id' => User::factory()->create()->id]);
        foreach (['trackables.shortcut', 'trackables.shortcut.download', 'trackables.shortcut.token'] as $route) {
            $method = $route === 'trackables.shortcut.token' ? 'post' : 'get';
            $url = route($route, $trackable);
            $this->$method($url)->assertRedirect(route('login'));
            $this->actingAs(User::factory()->create())->$method($url)->assertForbidden();
            auth()->forgetGuards();
        }
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_token_is_only_created_on_explicit_post_and_displayed_once(): void
    {
        $owner = User::factory()->create();
        $trackable = Trackable::factory()->create(['user_id' => $owner->id]);
        $this->actingAs($owner)->post(route('trackables.shortcut.token', $trackable))
            ->assertRedirect(route('trackables.shortcut', $trackable))->assertSessionHas('shortcut_token');
        $token = session('shortcut_token');
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertSame($owner->id, PersonalAccessToken::findToken($token)->tokenable_id);
        $this->get(route('trackables.shortcut', $trackable))->assertSee($token);
        $this->get(route('trackables.shortcut', $trackable))->assertDontSee($token);
    }
}
