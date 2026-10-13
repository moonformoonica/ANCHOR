<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (['admin', 'legal_reviewer'] as $role) {
            Role::findOrCreate($role);
        }
        // firstOrCreate keeps `db:seed` re-runnable. Building the attributes from
        // the factory preserves the same password/verification defaults the factory
        // sets, without inserting a duplicate row on a second run.
        $admin = User::firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->raw(['name' => 'Test User', 'email' => 'test@example.com']),
        );
        $admin->assignRole('admin');
        $this->call([
            LegalCorpusBaselineSeeder::class,
            ReferralInstitutionBaselineSeeder::class,
        ]);
    }
}
