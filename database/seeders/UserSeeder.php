<?php

namespace Eclipse\Core\Database\Seeders;

use Eclipse\Core\Models\Site;
use Eclipse\Core\Models\User;
use Eclipse\Core\Models\User\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // If the site/tenant ID is not set, default to the first one
        if (empty(getPermissionsTeamId())) {
            setPermissionsTeamId(Site::first()->id);
        }

        // Create users from provided presets
        foreach (config('eclipse.seed.users.presets') as $preset) {

            $data = $preset['data'];

            if (! isset($data['first_name'])) {
                $data['first_name'] = fake()->firstName;
            }

            if (! isset($data['last_name'])) {
                $data['last_name'] = fake()->lastName;
            }

            $data['password'] = Hash::make($data['password'] ?? fake()->password);

            // Create user
            $user = User::create($data);

            // Assign user to all sites/tenants
            foreach (Site::all() as $site) {
                $user->sites()->attach($site);

                Address::factory()->create([
                    'user_id' => $user->id,
                ]);

                if (isset($preset['role'])) {
                    setPermissionsTeamId($site->id);
                    $user->assignRole($preset['role'])->save();
                }
            }
        }

        // Reset tenant ID, in case it was changed
        setPermissionsTeamId(Site::first()->id);

        // Create an additional batch of random users, if required
        if (config('eclipse.seed.users.count') > 0) {
            User::factory()
                ->count(config('eclipse.seed.users.count'))
                ->create();
        }
    }
}
