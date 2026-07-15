<?php

use App\Models\Core\Audit\AuditLog;
use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\User;
use Database\Factories\Modules\DocumentControl\ControlledDocumentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

uses(RefreshDatabase::class)->beforeEach(function () {
    $this->seed([
        \Database\Seeders\RolesAndPermissionsSeeder::class,
        \Database\Seeders\NumberingFormatSeeder::class,
        \Database\Seeders\NotificationTemplateSeeder::class,
    ]);
});

it('blocks controlled document deletion for user without delete permission', function () {
    $role = Role::firstOrCreate(['name' => 'Employee', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role); // no document.control.delete + no scope
    $doc = ControlledDocumentFactory::new()->create(['status' => 'draft']);

    actingAs($user);
    delete(route('document.control.destroy', $doc))->assertForbidden();

    expect(ControlledDocument::find($doc->id))->not->toBeNull();
});

it('deletes controlled document + writes audit when authorized', function () {
    $manager = User::factory()->create();
    $manager->assignRole('QHSSE Manager'); // core.scope.all + document.control.delete via $documentFull
    $doc = ControlledDocumentFactory::new()->create(['status' => 'draft']);

    actingAs($manager);
    delete(route('document.control.destroy', $doc))->assertRedirect(route('document.control.index'));

    expect(ControlledDocument::find($doc->id))->toBeNull();
    expect(AuditLog::where('event', 'deleted')->where('module_name', 'document')->where('reference_id', $doc->id)->exists())->toBeTrue();
});
