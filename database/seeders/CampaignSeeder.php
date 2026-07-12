<?php

namespace Database\Seeders;

use App\Models\Modules\Communication\Campaign;
use App\Models\Modules\Communication\CampaignAcknowledgment;
use App\Models\Core\MasterData\Site;
use App\Models\Core\MasterData\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        echo "Seeding Communication & Campaign demo data...\n";

        $user = User::first();
        if (!$user) {
            echo "⚠️  No users found, skipping Campaign seeder.\n";
            return;
        }

        // Ensure we have org data
        $site = Site::first();
        if (!$site) {
            $site = Site::create([
                'code' => 'HQ',
                'name' => 'Head Office',
                'address' => 'Jakarta',
                'is_active' => true,
            ]);
        }

        $department = Department::first();
        if (!$department) {
            $department = Department::create([
                'code' => 'QHSSE',
                'name' => 'QHSSE Department',
                'is_active' => true,
            ]);
        }

        // Campaign 1: Safety Alert (published, all employees)
        $campaign1 = Campaign::create([
            'campaign_number' => 'COM-2026-0001',
            'title' => 'Safety Alert: Gunakan APD dengan Benar',
            'type' => 'safety_alert',
            'content' => "Kepada seluruh karyawan,\n\nKami mengingatkan pentingnya penggunaan Alat Pelindung Diri (APD) yang benar setiap saat di area kerja.\n\nAPD yang wajib digunakan:\n- Helm safety\n- Safety shoes\n- Safety vest\n- Safety glasses (di area tertentu)\n- Sarung tangan (sesuai jenis pekerjaan)\n\nPenggunaan APD yang tepat adalah tanggung jawab SETIAP INDIVIDU untuk keselamatan diri sendiri dan rekan kerja.\n\nTerima kasih atas perhatian dan kerjasamanya.\n\nQHSSE Department",
            'target_audience' => 'all',
            'site_id' => null,
            'department_id' => null,
            'target_role' => null,
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'expires_at' => null,
            'view_count' => 45,
            'author_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Campaign 2: Lesson Learned (published, specific site)
        $campaign2 = Campaign::create([
            'campaign_number' => 'COM-2026-0002',
            'title' => 'Lesson Learned: Pencegahan Kebakaran di Area Penyimpanan',
            'type' => 'lesson_learned',
            'content' => "Berdasarkan insiden yang terjadi pada tanggal 1 Juli 2026 di site lain, kami ingin berbagi pelajaran penting:\n\nKRONOLOGI:\nKebakaran kecil terjadi di area penyimpanan material akibat sambungan listrik yang tidak standar.\n\nPENYEBAB:\n- Kabel ekstensi tidak sesuai kapasitas\n- Tidak ada pemeriksaan rutin instalasi listrik\n- Material mudah terbakar disimpan terlalu dekat dengan sumber listrik\n\nTINDAKAN PENCEGAHAN:\n1. Pastikan semua instalasi listrik sesuai standar\n2. Lakukan pemeriksaan rutin bulanan\n3. Jaga jarak aman antara sumber listrik dan material mudah terbakar\n4. Pastikan APAR tersedia dan dalam kondisi baik\n\nMari kita pelajari dan terapkan di site kita.\n\nQHSSE Team",
            'target_audience' => 'specific_site',
            'site_id' => $site->id,
            'department_id' => null,
            'target_role' => null,
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'expires_at' => null,
            'view_count' => 28,
            'author_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Campaign 3: Announcement (draft)
        $campaign3 = Campaign::create([
            'campaign_number' => 'COM-2026-0003',
            'title' => 'Pengumuman: Safety Week 2026',
            'type' => 'announcement',
            'content' => "Dear All,\n\nKami dengan senang hati mengumumkan Safety Week 2026 yang akan dilaksanakan pada:\n\nTanggal: 15-19 Juli 2026\nTema: \"Safety is Our Priority\"\n\nAgenda:\n- Senin: Safety talk dan toolbox meeting\n- Selasa: Safety inspection dan audit\n- Rabu: Emergency drill\n- Kamis: Safety training dan workshop\n- Jumat: Safety award ceremony\n\nPartisipasi aktif dari seluruh karyawan sangat diharapkan.\n\nDetail agenda akan diinformasikan lebih lanjut.\n\nQHSSE Department",
            'target_audience' => 'all',
            'site_id' => null,
            'department_id' => null,
            'target_role' => null,
            'status' => 'draft',
            'published_at' => null,
            'expires_at' => now()->addDays(30),
            'view_count' => 0,
            'author_id' => $user->id,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        echo "✓ Seeded 3 campaigns\n";

        // Add some acknowledgments for campaign 1 (safety alert)
        $users = User::take(5)->get();
        foreach ($users as $u) {
            CampaignAcknowledgment::create([
                'campaign_id' => $campaign1->id,
                'user_id' => $u->id,
                'acknowledged_at' => now()->subDays(rand(1, 4)),
                'ip_address' => '127.0.0.1',
            ]);
        }

        echo "✓ Seeded acknowledgments for safety alert\n";
    }
}
