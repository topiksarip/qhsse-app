<?php

namespace Database\Seeders;

use App\Core\Numbering\NumberingService;
use App\Models\Core\Numbering\NumberingFormat;
use Illuminate\Database\Seeder;

class NumberingFormatSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(NumberingService::class);

        foreach ($this->formats() as $data) {
            $format = NumberingFormat::updateOrCreate(
                ['module_name' => $data['module_name']],
                $data,
            );

            $format->update(['sample' => $service->sample($format)]);
        }
    }

    private function formats(): array
    {
        return [
            ['module_name' => 'incident', 'prefix' => 'INC', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'investigation', 'prefix' => 'INV', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'capa', 'prefix' => 'ACT', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'inspection', 'prefix' => 'INS', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'audit', 'prefix' => 'AUD', 'padding' => 5, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'document', 'prefix' => 'DOC', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'training', 'prefix' => 'TRN', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'permit', 'prefix' => 'PTW', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => true, 'is_active' => true],
            ['module_name' => 'risk', 'prefix' => 'RSK', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'environment', 'prefix' => 'ENV', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'security', 'prefix' => 'SEC', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'security_patrol', 'prefix' => 'SPL', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'quality', 'prefix' => 'NCR', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'quality_complaint', 'prefix' => 'CCR', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'legal', 'prefix' => 'LEG', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'emergency', 'prefix' => 'EMG', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'contractor', 'prefix' => 'CTR', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'asset', 'prefix' => 'AST', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
            ['module_name' => 'communication', 'prefix' => 'COM', 'padding' => 4, 'separator' => '-', 'reset_frequency' => 'yearly', 'include_year' => true, 'include_site_code' => false, 'is_active' => true],
        ];
    }
}
