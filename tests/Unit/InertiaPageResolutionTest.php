<?php

it('has a TSX page for every literal Inertia render target', function () {
    $root = dirname(__DIR__, 2);
    $controllers = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($root.'/app/Http/Controllers')
    );
    $missing = [];
    $renderTargets = [];

    foreach ($controllers as $controller) {
        if (! $controller->isFile() || $controller->getExtension() !== 'php') {
            continue;
        }

        $contents = file_get_contents($controller->getPathname());
        preg_match_all("/Inertia::render\(\s*['\"]([^'\"]+)['\"]/", $contents, $matches);

        foreach ($matches[1] as $target) {
            $renderTargets[] = $target;
            $page = $root.'/resources/js/Pages/'.$target.'.tsx';

            if (! is_file($page)) {
                $missing[] = sprintf(
                    '%s renders %s, but %s does not exist',
                    str_replace($root.'/', '', $controller->getPathname()),
                    $target,
                    str_replace($root.'/', '', $page),
                );
            }
        }
    }

    expect($renderTargets)->not->toBeEmpty()
        ->and($missing)->toBe([], implode(PHP_EOL, $missing));
});
