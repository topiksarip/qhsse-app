<?php

namespace App\Http\Requests\Modules\Communication;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $campaign = $this->route('campaign');
        return $this->user()->can('acknowledge', $campaign);
    }

    public function rules(): array
    {
        return [
            // No additional fields required - acknowledgment is implicit
        ];
    }
}
