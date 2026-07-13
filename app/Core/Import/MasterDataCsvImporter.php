<?php

namespace App\Core\Import;

use App\Core\Audit\AuditService;
use App\Models\Core\MasterData\Company;
use App\Models\Core\MasterData\Department;
use App\Models\Core\MasterData\Position;
use App\Models\Core\MasterData\Site;
use App\Models\Core\Users\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MasterDataCsvImporter
{
    private const HEADERS = [
        'sites' => ['code', 'name'],
        'departments' => ['code', 'name', 'site_code'],
        'employees' => ['employee_no', 'name'],
    ];

    public function __construct(
        private readonly CsvImportReader $reader,
        private readonly AuditService $audit,
    ) {}

    public function import(string $type, UploadedFile $file, User $actor): int
    {
        abort_unless(array_key_exists($type, self::HEADERS), 404);
        $rows = $this->reader->read($file, self::HEADERS[$type]);
        $prepared = $this->prepare($type, $rows);

        DB::transaction(function () use ($type, $prepared): void {
            foreach ($prepared as $data) {
                match ($type) {
                    'sites' => Site::create($data),
                    'departments' => Department::create($data),
                    'employees' => Employee::create($data),
                };
            }
        });

        $this->audit->log(
            'bulk_import_completed',
            actor: $actor,
            moduleName: 'core_import',
            newValues: ['type' => $type, 'rows' => count($prepared)],
            metadata: ['filename' => basename($file->getClientOriginalName())],
        );

        return count($prepared);
    }

    /** @param array<int, array{row: int, data: array<string, string>}> $rows */
    private function prepare(string $type, array $rows): array
    {
        $prepared = [];
        $errors = [];
        $seen = [];
        $seenEmails = [];

        foreach ($rows as $row) {
            try {
                $data = match ($type) {
                    'sites' => $this->siteData($row['data']),
                    'departments' => $this->departmentData($row['data']),
                    'employees' => $this->employeeData($row['data']),
                };
                $key = match ($type) {
                    'employees' => mb_strtolower($data['employee_no']),
                    default => mb_strtolower($data['code']),
                };
                if (isset($seen[$key])) {
                    throw ValidationException::withMessages(['key' => "Duplikat dengan baris {$seen[$key]}."]);
                }
                $seen[$key] = $row['row'];
                $email = mb_strtolower((string) ($data['email'] ?? ''));
                if ($email !== '' && isset($seenEmails[$email])) {
                    throw ValidationException::withMessages(['email' => "Email duplikat dengan baris {$seenEmails[$email]}."]);
                }
                if ($email !== '') {
                    $seenEmails[$email] = $row['row'];
                }
                $prepared[] = $data;
            } catch (ValidationException $exception) {
                $errors["rows.{$row['row']}"] = collect($exception->errors())->flatten()->implode(' ');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $prepared;
    }

    private function siteData(array $row): array
    {
        return $this->validated($row, [
            'code' => ['required', 'string', 'max:50', Rule::unique('sites', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', Rule::in(['', '1', '0', 'true', 'false', 'yes', 'no'])],
        ], ['code', 'name', 'address', 'is_active']);
    }

    private function departmentData(array $row): array
    {
        $data = $this->validated($row, [
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'site_code' => ['required', 'string', Rule::exists('sites', 'code')->where('is_active', true)],
            'is_active' => ['nullable', Rule::in(['', '1', '0', 'true', 'false', 'yes', 'no'])],
        ], ['code', 'name', 'site_code', 'is_active']);
        $data['site_id'] = Site::where('code', $data['site_code'])->value('id');

        return Arr::except($data, 'site_code');
    }

    private function employeeData(array $row): array
    {
        $data = $this->validated($row, [
            'employee_no' => ['required', 'string', 'max:50', Rule::unique('employees', 'employee_no')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'company_code' => ['nullable', 'string', Rule::exists('companies', 'code')->where('is_active', true)],
            'site_code' => ['nullable', 'string', Rule::exists('sites', 'code')->where('is_active', true)],
            'department_code' => ['nullable', 'string', Rule::exists('departments', 'code')->where('is_active', true)],
            'position_code' => ['nullable', 'string', Rule::exists('positions', 'code')->where('is_active', true)],
            'is_active' => ['nullable', Rule::in(['', '1', '0', 'true', 'false', 'yes', 'no'])],
        ], ['employee_no', 'name', 'email', 'phone', 'company_code', 'site_code', 'department_code', 'position_code', 'is_active']);

        $site = filled($data['site_code']) ? Site::where('code', $data['site_code'])->first() : null;
        $department = filled($data['department_code']) ? Department::where('code', $data['department_code'])->first() : null;
        $position = filled($data['position_code']) ? Position::where('code', $data['position_code'])->first() : null;

        if ($department && $site && $department->site_id !== $site->id) {
            throw ValidationException::withMessages(['department_code' => 'Department tidak berada pada site yang dipilih.']);
        }
        if ($position && $department && $position->department_id !== $department->id) {
            throw ValidationException::withMessages(['position_code' => 'Position tidak berada pada department yang dipilih.']);
        }

        return [
            'employee_no' => $data['employee_no'],
            'name' => $data['name'],
            'email' => $data['email'] ?: null,
            'phone' => $data['phone'] ?: null,
            'company_id' => filled($data['company_code']) ? Company::where('code', $data['company_code'])->value('id') : null,
            'site_id' => $site?->id,
            'department_id' => $department?->id,
            'position_id' => $position?->id,
            'department' => $department?->name,
            'position' => $position?->name,
            'is_active' => $this->boolean($data['is_active']),
        ];
    }

    private function validated(array $row, array $rules, array $keys): array
    {
        $data = array_replace(array_fill_keys($keys, ''), Arr::only($row, $keys));
        $validated = Validator::make($data, $rules)->validate();
        $validated['is_active'] = $this->boolean($validated['is_active'] ?? '');

        return $validated;
    }

    private function boolean(?string $value): bool
    {
        return ! in_array(mb_strtolower((string) $value), ['0', 'false', 'no'], true);
    }
}
