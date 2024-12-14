<?php

namespace Database\Seeders;

use App\Models\Trackable;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Mike',
            'email' => 'me@mikev.it',
            'password' => '$2y$12$w/gS1FkEg6WLUHNG2x1viewFOiA3wuRugIzwaA98PzrUyfakK0.zK'
        ]);

        User::factory(4)->create();

        Trackable::factory(18)->create();
        Trackable::factory()->count(2)->deleted()->create();
        Trackable::factory()->count(2)->otherUsers()->create();
        Trackable::factory()->count(2)->deleted()->otherUsers()->create();
    }
}
