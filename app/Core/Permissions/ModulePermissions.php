<?php

namespace App\Core\Permissions;

/**
 * Permission registry for business modules.
 * Each module follows the same action vocabulary:
 * view, view.all, create, update, submit, review, approve,
 * reject, verify, close, reopen, delete, export
 */
final class ModulePermissions
{
    /** @return array<int,string> */
    public static function all(): array
    {
        $perms = [];
        foreach (self::modules() as $module) {
            foreach (self::actions() as $action) {
                $perms[] = "{$module}.{$action}";
            }
        }
        return $perms;
    }

    /** @return array<int,string> */
    public static function modules(): array
    {
        return [
            'incident-reporting',
            'investigation',
            'capa',
            'inspection',
            'audit',
            'document',
            'training',
            'permit',
            'environment',
            'security',
            'quality',
            'risk',
            'legal',
            'emergency',
            'contractor',
            'asset',
            'communication',
            'reporting',
            'admin-master-data',
        ];
    }

    /** @return array<int,string> */
    public static function actions(): array
    {
        return [
            'view',
            'view.all',
            'create',
            'update',
            'submit',
            'review',
            'approve',
            'reject',
            'verify',
            'close',
            'reopen',
            'delete',
            'export',
        ];
    }
}
