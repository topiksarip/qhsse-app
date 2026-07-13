<?php

namespace Database\Factories\Modules\DocumentControl;

use App\Models\Modules\DocumentControl\ControlledDocument;
use App\Models\Modules\DocumentControl\DocumentReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DocumentReview> */
class DocumentReviewFactory extends Factory
{
    protected $model = DocumentReview::class;

    public function definition(): array
    {
        return [
            'document_id' => ControlledDocument::factory(),
            'reviewer_id' => null,
            'review_date' => null,
            'review_notes' => $this->faker->optional()->paragraph(),
            'decision' => 'pending',
        ];
    }
}
