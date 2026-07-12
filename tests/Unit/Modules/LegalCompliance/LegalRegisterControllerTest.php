<?php

use App\Core\Numbering\NumberingService;
use App\Core\Services\ScopeService;
use App\Http\Controllers\Modules\LegalCompliance\LegalRegisterController;
use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\Modules\LegalCompliance\LegalRegister;
use App\Policies\Modules\LegalCompliance\LegalObligationPolicy;
use App\Policies\Modules\LegalCompliance\LegalRegisterPolicy;
use Tests\TestCase;

uses(TestCase::class);

it('depends on the shared core numbering service', function () {
    $parameter = (new ReflectionClass(LegalRegisterController::class))
        ->getConstructor()
        ->getParameters()[0];

    expect($parameter->getType()?->getName())->toBe(NumberingService::class);
    expect(class_exists($parameter->getType()?->getName()))->toBeTrue();
});

it('resolves Legal policies with the shared core scope service', function (string $policy) {
    $parameter = (new ReflectionClass($policy))
        ->getConstructor()
        ->getParameters()[0];

    expect($parameter->getType()?->getName())->toBe(ScopeService::class);
    expect(class_exists($parameter->getType()?->getName()))->toBeTrue();
})->with([
    LegalRegisterPolicy::class,
    LegalObligationPolicy::class,
]);

it('links Legal registers to controlled documents', function () {
    $relation = (new LegalRegister)->document();

    expect($relation->getRelated())->toBeInstanceOf(ControlledDocument::class);
});
