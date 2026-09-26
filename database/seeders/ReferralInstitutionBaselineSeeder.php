<?php

namespace Database\Seeders;

use App\Models\ReferralInstitution;
use Illuminate\Database\Seeder;

class ReferralInstitutionBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $institutions = [
            [
                'name' => 'Direktorat Tindak Pidana Siber Bareskrim Polri',
                'category' => 'cyber_crime',
                'rationale' => 'Kanal calon untuk laporan dugaan tindak pidana yang melibatkan sarana elektronik.',
            ],
            [
                'name' => 'Kementerian Komunikasi dan Digital',
                'category' => 'content_takedown',
                'rationale' => 'Kanal calon untuk permohonan penanganan konten elektronik yang merugikan.',
            ],
            [
                'name' => 'Otoritas Jasa Keuangan',
                'category' => 'financial_consumer_protection',
                'rationale' => 'Kanal calon untuk pengaduan terkait penyelenggara jasa keuangan dan penagihan pinjaman daring.',
            ],
            [
                'name' => 'Lembaga Perlindungan Saksi dan Korban',
                'category' => 'psychosocial_support',
                'rationale' => 'Kanal calon untuk informasi perlindungan dan dukungan bagi saksi atau korban.',
            ],
        ];

        foreach ($institutions as $institution) {
            ReferralInstitution::updateOrCreate(
                ['name' => $institution['name'], 'category' => $institution['category']],
                [
                    ...$institution,
                    'contact_channel' => null,
                    'region_scope' => 'national',
                    'status' => 'needs_verification',
                    'last_verified_date' => null,
                    'notes' => 'Baseline candidate only. Verify scope, eligibility, and official contact channel before activation or victim-facing use.',
                ]
            );
        }
    }
}