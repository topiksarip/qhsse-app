<?php

namespace App\Core\Numbering;

use App\Models\Core\Numbering\GeneratedNumber;
use App\Models\Core\Numbering\NumberingCounter;
use App\Models\Core\Numbering\NumberingFormat;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class NumberingService
{
    public function generate(
        string $moduleName,
        ?User $actor = null,
        ?string $siteCode = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $metadata = [],
    ): GeneratedNumber {
        return DB::transaction(function () use ($moduleName, $actor, $siteCode, $referenceType, $referenceId, $metadata): GeneratedNumber {
            $format = NumberingFormat::query()
                ->where('module_name', $moduleName)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $format) {
                throw new RuntimeException("No active numbering format configured for [{$moduleName}].");
            }

            $year = $format->reset_frequency === 'yearly' ? (int) now()->year : null;
            $normalizedSiteCode = $format->include_site_code ? strtoupper((string) $siteCode) : '';

            if ($format->include_site_code && $normalizedSiteCode === '') {
                throw new RuntimeException("Site code is required for numbering format [{$moduleName}].");
            }

            $counter = NumberingCounter::query()
                ->where('module_name', $moduleName)
                ->where('site_code', $normalizedSiteCode)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = NumberingCounter::create([
                    'module_name' => $moduleName,
                    'site_code' => $normalizedSiteCode,
                    'year' => $year,
                    'current_number' => 0,
                ]);
                $counter->refresh();
            }

            $sequence = $counter->current_number + 1;
            $number = $this->formatNumber($format, $sequence, $year, $normalizedSiteCode);

            $counter->update(['current_number' => $sequence]);

            return GeneratedNumber::create([
                'module_name' => $moduleName,
                'number' => $number,
                'site_code' => $normalizedSiteCode,
                'year' => $year,
                'sequence' => $sequence,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'generated_by' => $actor?->id,
                'metadata' => $metadata,
            ]);
        });
    }

    public function formatNumber(NumberingFormat $format, int $sequence, ?int $year = null, string $siteCode = ''): string
    {
        $parts = [$format->prefix];

        if ($format->include_site_code) {
            $parts[] = strtoupper($siteCode);
        }

        if ($format->include_year) {
            $parts[] = (string) ($year ?? now()->year);
        }

        $parts[] = str_pad((string) $sequence, $format->padding, '0', STR_PAD_LEFT);

        return implode($format->separator, $parts);
    }

    public function sample(NumberingFormat $format): string
    {
        return $this->formatNumber(
            $format,
            1,
            $format->reset_frequency === 'yearly' ? (int) now()->year : null,
            $format->include_site_code ? 'SITE' : '',
        );
    }
}
