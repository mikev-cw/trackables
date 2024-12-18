<?php

namespace Database\Seeders;

use App\Models\Trackable;
use App\Models\TrackableData;
use App\Models\TrackableRecord;
use App\Models\TrackableSchema;
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
        // Users

        User::factory()->create([
            'name' => 'Mike',
            'email' => 'me@mikev.it',
            'password' => '$2y$12$w/gS1FkEg6WLUHNG2x1viewFOiA3wuRugIzwaA98PzrUyfakK0.zK'
        ]);

        User::factory(4)->create();

        // Items
        $trackables = Trackable::factory(18)->create();

        Trackable::factory()->count(2)->deleted()->create();
        Trackable::factory()->count(2)->otherUsers()->create();
        Trackable::factory()->count(2)->deleted()->otherUsers()->create();

        // Pick a random one
        $reference = $trackables->random()->uid;

        // Record
        $record = TrackableRecord::factory(1)->create([
            'trackable_uid' => $reference,
        ]);

        $recordUid = $record->first()->uid;

        // Schemas
        $schemas = TrackableSchema::factory(3)->create([
            'trackable_uid' => $reference,
        ]);

        // Data
        foreach ($schemas as $schema) {
            $method = match ($schema->field_type) {
                'int' => 'int',
                'float' => 'float',
                'string' => 'string',
                default => null,
            };

            if ($method) {
                TrackableData::factory()
                    ->count(1)
                    ->{$method}()
                    ->create([
                        'trackable_schema_uid' => $schema->uid,
                        'trackable_record_uid' => $recordUid,
                    ]);
            }
        }


    }
}
