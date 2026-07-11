<?php

namespace App\Models\Modules\DocumentControl;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentReview extends Model
{
    use HasFactory;

    protected $table = 'document_reviews';

    protected $fillable = [
        'document_id', 'reviewer_id', 'review_date', 'review_notes', 'decision',
    ];

    protected function casts(): array
    {
        return ['review_date' => 'date'];
    }

    /** @return BelongsTo<ControlledDocument, self> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(ControlledDocument::class, 'document_id');
    }

    /** @return BelongsTo<User, self> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
