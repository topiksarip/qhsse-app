<?php

use Tests\TestCase;

uses(TestCase::class);

it('loads Inertia pages through the application entrypoint', function () {
    $template = file_get_contents(resource_path('views/app.blade.php'));

    expect($template)->toContain("@vite('resources/js/app.tsx')");
    expect($template)->not->toContain('$page[\'component\']');
});
