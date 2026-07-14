<?php

use App\Models\Modules\Training\TrainingRecord;

it('always creates an end date on or after the training start date', function () {
    fake()->seed(20);

    $attributes = TrainingRecord::factory()->raw();

    if ($attributes['end_date'] === null) {
        expect($attributes['end_date'])->toBeNull();

        return;
    }

    expect($attributes['end_date']->getTimestamp())
        ->toBeGreaterThanOrEqual($attributes['start_date']->getTimestamp())
        ->toBeLessThanOrEqual((clone $attributes['start_date'])->modify('+7 days')->getTimestamp());
});
