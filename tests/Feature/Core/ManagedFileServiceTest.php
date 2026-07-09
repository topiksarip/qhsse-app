<?php

use App\Models\Core\Files\ManagedFile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('local');
});

function fileServiceAdmin(): User
{
    $user = User::factory()->create();
    $user->assignRole('Super Admin');

    return $user;
}

it('uploads files with module reference metadata to private storage', function () {
    $admin = fileServiceAdmin();

    $this->actingAs($admin)->post(route('core.files.store'), [
        'module_name' => 'core.test',
        'reference_id' => 123,
        'collection' => 'evidence',
        'file' => UploadedFile::fake()->create('evidence.pdf', 64, 'application/pdf'),
    ])->assertRedirect(route('core.files.index'));

    $file = ManagedFile::firstOrFail();

    expect($file->module_name)->toBe('core.test')
        ->and($file->reference_id)->toBe(123)
        ->and($file->collection)->toBe('evidence')
        ->and($file->original_name)->toBe('evidence.pdf')
        ->and($file->uploaded_by)->toBe($admin->id);

    Storage::disk('local')->assertExists($file->path);
});

it('downloads files through authorized endpoint only', function () {
    $admin = fileServiceAdmin();

    $this->actingAs($admin)->post(route('core.files.store'), [
        'module_name' => 'core.test',
        'reference_id' => 456,
        'file' => UploadedFile::fake()->createWithContent('note.txt', 'secure note'),
    ]);

    $file = ManagedFile::firstOrFail();

    $this->actingAs($admin)
        ->get(route('core.files.download', $file))
        ->assertOk()
        ->assertHeader('content-disposition');

    $plainUser = User::factory()->create();

    $this->actingAs($plainUser)
        ->get(route('core.files.download', $file))
        ->assertForbidden();
});

it('rejects invalid extensions and oversized files', function () {
    $admin = fileServiceAdmin();

    $this->actingAs($admin)->post(route('core.files.store'), [
        'module_name' => 'core.test',
        'reference_id' => 789,
        'file' => UploadedFile::fake()->create('script.exe', 1, 'application/octet-stream'),
    ])->assertSessionHasErrors('file');

    $this->actingAs($admin)->post(route('core.files.store'), [
        'module_name' => 'core.test',
        'reference_id' => 789,
        'file' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf'),
    ])->assertSessionHasErrors('file');
});

it('marks files deleted without exposing deleted downloads', function () {
    $admin = fileServiceAdmin();

    $this->actingAs($admin)->post(route('core.files.store'), [
        'module_name' => 'core.test',
        'reference_id' => 321,
        'file' => UploadedFile::fake()->create('photo.jpg', 10, 'image/jpeg'),
    ]);

    $file = ManagedFile::firstOrFail();

    $this->actingAs($admin)
        ->delete(route('core.files.destroy', $file))
        ->assertRedirect(route('core.files.index'));

    $file->refresh();

    expect($file->deleted_at)->not->toBeNull()
        ->and($file->deleted_by)->toBe($admin->id);

    $this->actingAs($admin)
        ->get(route('core.files.download', $file))
        ->assertNotFound();
});
