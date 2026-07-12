<?php

namespace Database\Seeders\Modules\Reporting;

use App\Models\Modules\Reporting\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'test@example.com')->first();
        
        if (!$admin) {
            $this->command->warn('Admin user not found. Skipping report template seeding.');
            return;
        }

        $templates = [
            [
                'name' => 'Ringkasan Insiden',
                'type' => 'incident_summary',
                'description' => 'Laporan ringkasan insiden per periode. Menampilkan jumlah insiden by severity, by type, by site, trend bulanan, dan status distribution.',
                'is_predefined' => true,
                'config' => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan Eksekutif', 'enabled' => true],
                        ['key' => 'by_severity', 'label' => 'Insiden by Severity', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'by_type', 'label' => 'Insiden by Type', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'by_site', 'label' => 'Insiden by Site', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'trend', 'label' => 'Trend Bulanan', 'enabled' => true, 'data_source' => 'incident'],
                    ],
                    'default_parameters' => ['date_range' => 'last_month', 'format' => 'pdf'],
                ],
            ],
            [
                'name' => 'Ringkasan CAPA',
                'type' => 'capa_summary',
                'description' => 'Laporan status CAPA. Menampilkan total open, in_progress, waiting_verification, closed, rejected, overdue count, closure rate, by source module, dan by priority.',
                'is_predefined' => true,
                'config' => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan Status', 'enabled' => true],
                        ['key' => 'by_status', 'label' => 'CAPA by Status', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'by_priority', 'label' => 'CAPA by Priority', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'overdue', 'label' => 'CAPA Overdue', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'closure_rate', 'label' => 'Closure Rate', 'enabled' => true, 'data_source' => 'capa'],
                    ],
                    'default_parameters' => ['date_range' => 'last_month', 'format' => 'pdf'],
                ],
            ],
            [
                'name' => 'Ringkasan Inspection',
                'type' => 'inspection_summary',
                'description' => 'Laporan hasil inspection. Menampilkan total inspection, pass/fail rate, findings by category, dan compliance rate per site.',
                'is_predefined' => true,
                'config' => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan', 'enabled' => true],
                        ['key' => 'pass_fail', 'label' => 'Pass/Fail Rate', 'enabled' => true, 'data_source' => 'inspection'],
                        ['key' => 'findings', 'label' => 'Findings by Category', 'enabled' => true, 'data_source' => 'inspection'],
                        ['key' => 'compliance', 'label' => 'Compliance Rate', 'enabled' => true, 'data_source' => 'inspection'],
                    ],
                    'default_parameters' => ['date_range' => 'last_month', 'format' => 'pdf'],
                ],
            ],
            [
                'name' => 'Ringkasan Audit',
                'type' => 'audit_summary',
                'description' => 'Laporan audit findings. Menampilkan total audit, findings by severity, status distribution, dan closure rate.',
                'is_predefined' => true,
                'config' => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan', 'enabled' => true],
                        ['key' => 'by_severity', 'label' => 'Findings by Severity', 'enabled' => true, 'data_source' => 'audit'],
                        ['key' => 'by_status', 'label' => 'Status Distribution', 'enabled' => true, 'data_source' => 'audit'],
                        ['key' => 'closure_rate', 'label' => 'Closure Rate', 'enabled' => true, 'data_source' => 'audit'],
                    ],
                    'default_parameters' => ['date_range' => 'last_month', 'format' => 'pdf'],
                ],
            ],
            [
                'name' => 'Kepatuhan Training',
                'type' => 'training_compliance',
                'description' => 'Laporan status training. Menampilkan enrollment count, completion rate, overdue training, dan compliance per departemen.',
                'is_predefined' => true,
                'config' => [
                    'sections' => [
                        ['key' => 'summary', 'label' => 'Ringkasan', 'enabled' => true],
                        ['key' => 'completion', 'label' => 'Completion Rate', 'enabled' => true, 'data_source' => 'training'],
                        ['key' => 'overdue', 'label' => 'Overdue Training', 'enabled' => true, 'data_source' => 'training'],
                        ['key' => 'by_department', 'label' => 'Compliance by Department', 'enabled' => true, 'data_source' => 'training'],
                    ],
                    'default_parameters' => ['date_range' => 'last_month', 'format' => 'pdf'],
                ],
            ],
            [
                'name' => 'Laporan Bulanan QHSSE',
                'type' => 'monthly_qhsse',
                'description' => 'Laporan komprehensif bulanan. Menampilkan insiden, CAPA, inspection, audit, training, permit, environment, security, dan quality dalam satu laporan.',
                'is_predefined' => true,
                'config' => [
                    'sections' => [
                        ['key' => 'executive_summary', 'label' => 'Ringkasan Eksekutif', 'enabled' => true],
                        ['key' => 'incidents', 'label' => 'Statistik Insiden', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'capa', 'label' => 'Status CAPA', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'inspection', 'label' => 'Hasil Inspection', 'enabled' => true, 'data_source' => 'inspection'],
                        ['key' => 'audit', 'label' => 'Audit Findings', 'enabled' => true, 'data_source' => 'audit'],
                        ['key' => 'training', 'label' => 'Training Compliance', 'enabled' => true, 'data_source' => 'training'],
                    ],
                    'default_parameters' => ['date_range' => 'last_month', 'format' => 'pdf', 'include_charts' => true],
                ],
            ],
            [
                'name' => 'Laporan Tahunan QHSSE',
                'type' => 'annual_qhsse',
                'description' => 'Laporan komprehensif tahunan dengan tren 12 bulan, benchmarking, dan analisis. Menampilkan semua aspek QHSSE dengan detail tahunan.',
                'is_predefined' => true,
                'config' => [
                    'sections' => [
                        ['key' => 'executive_summary', 'label' => 'Ringkasan Eksekutif', 'enabled' => true],
                        ['key' => 'annual_trends', 'label' => 'Tren Tahunan', 'enabled' => true],
                        ['key' => 'incidents', 'label' => 'Analisis Insiden', 'enabled' => true, 'data_source' => 'incident'],
                        ['key' => 'capa', 'label' => 'Analisis CAPA', 'enabled' => true, 'data_source' => 'capa'],
                        ['key' => 'inspection', 'label' => 'Analisis Inspection', 'enabled' => true, 'data_source' => 'inspection'],
                        ['key' => 'audit', 'label' => 'Analisis Audit', 'enabled' => true, 'data_source' => 'audit'],
                        ['key' => 'training', 'label' => 'Analisis Training', 'enabled' => true, 'data_source' => 'training'],
                    ],
                    'default_parameters' => ['date_range' => 'last_year', 'format' => 'pdf', 'include_charts' => true],
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            ReportTemplate::updateOrCreate(
                ['type' => $templateData['type']],
                [
                    ...$templateData,
                    'is_active' => true,
                    'created_by' => $admin->id,
                    'updated_by' => $admin->id,
                ]
            );
        }

        $this->command->info('✅ Report templates seeded successfully.');
    }
}
