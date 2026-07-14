<?php

namespace Database\Seeders;

use App\Models\Core\MasterData\Category;
use App\Models\Core\MasterData\Priority;
use App\Models\Core\MasterData\RiskMatrixLevel;
use App\Models\Core\MasterData\Severity;
use App\Models\Core\MasterData\Status;
use Illuminate\Database\Seeder;

class QhsseMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['LOW', 'Low', 1, 'green'],
            ['MEDIUM', 'Medium', 2, 'yellow'],
            ['HIGH', 'High', 3, 'orange'],
            ['CRITICAL', 'Critical', 4, 'red'],
        ] as [$code, $name, $level, $color]) {
            Severity::updateOrCreate(['code' => $code], compact('name', 'level', 'color') + ['description' => $name.' severity', 'is_active' => true]);
        }

        foreach ([
            ['LOW', 'Low', 30, 'green'],
            ['MEDIUM', 'Medium', 14, 'yellow'],
            ['HIGH', 'High', 7, 'orange'],
            ['URGENT', 'Urgent', 1, 'red'],
        ] as [$code, $name, $sla_days, $color]) {
            Priority::updateOrCreate(['code' => $code], compact('name', 'sla_days', 'color') + ['is_active' => true]);
        }

        foreach ([
            ['DRAFT', 'Draft', 1, false],
            ['SUBMITTED', 'Submitted', 2, false],
            ['UNDER_REVIEW', 'Under Review', 3, false],
            ['INVESTIGATION', 'Investigation', 4, false],
            ['ACTION_OPEN', 'Action Open', 5, false],
            ['CLOSED', 'Closed', 90, true],
            ['REJECTED', 'Rejected', 99, true],
        ] as [$code, $name, $sequence, $is_terminal]) {
            Status::updateOrCreate(['module' => 'incident', 'code' => $code], compact('name', 'sequence', 'is_terminal') + ['is_active' => true]);
        }

        foreach ([
            ['incident', 'ACCIDENT', 'Accident'],
            ['incident', 'INCIDENT', 'Incident'],
            ['incident', 'NEAR_MISS', 'Near Miss'],
            ['incident', 'UNSAFE_ACT', 'Unsafe Act'],
            ['incident', 'UNSAFE_CONDITION', 'Unsafe Condition'],
            ['incident', 'ENVIRONMENTAL_SPILL', 'Environmental Spill'],
            ['incident', 'SECURITY_BREACH', 'Security Breach'],
            ['action', 'CORRECTIVE', 'Corrective Action'],
            ['action', 'PREVENTIVE', 'Preventive Action'],
        ] as [$module, $code, $name]) {
            Category::updateOrCreate(['module' => $module, 'code' => $code], compact('name') + ['parent_id' => null, 'is_active' => true]);
        }

        foreach (range(1, 5) as $likelihood) {
            foreach (range(1, 5) as $consequence) {
                $score = $likelihood * $consequence;
                [$level, $color] = match (true) {
                    $score <= 4 => ['Low', 'green'],
                    $score <= 9 => ['Medium', 'yellow'],
                    $score <= 16 => ['High', 'orange'],
                    default => ['Extreme', 'red'],
                };

                RiskMatrixLevel::updateOrCreate(
                    ['likelihood' => $likelihood, 'consequence' => $consequence],
                    compact('score', 'level', 'color') + ['description' => "{$level} risk", 'is_active' => true]
                );
            }
        }
    }
}
