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
        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $admin->assignRole('admin');
        $this->call([
            LegalCorpusBaselineSeeder::class,
            ReferralInstitutionBaselineSeeder::class,
        ]);
    }
}
