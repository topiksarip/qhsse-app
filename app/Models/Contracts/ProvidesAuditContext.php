<?php

namespace App\Models\Contracts;

interface ProvidesAuditContext
{
    /** @return array{module_name: string, reference_id: int} */
    public function auditContext(): array;
}
