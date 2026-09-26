<?php

namespace Database\Seeders;

use App\Models\LegalCorpusEntry;
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
        LegalCorpusEntry::firstOrCreate(
            ['law_name' => 'UU ITE', 'pasal_reference' => 'Pasal 27 ayat (3)'],
            ['status' => 'active', 'metadata' => ['seeded_for' => 'stub_ai']]
        );
    }
}
