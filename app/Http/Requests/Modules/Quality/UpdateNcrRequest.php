<?php
namespace App\Http\Requests\Modules\Quality;
use Illuminate\Foundation\Http\FormRequest;
class UpdateNcrRequest extends FormRequest {
    public function authorize(): bool { return $this->user()->can('quality.ncrs.update'); }
    public function rules(): array {
        return [
            'title'=>['sometimes','string','max:255'], 'source'=>['sometimes','string','in:internal,external,customer_complaint,audit,supplier'],
            'description'=>['sometimes','string'], 'site_id'=>['sometimes','exists:sites,id'], 'department_id'=>['nullable','exists:departments,id'],
            'product_service'=>['nullable','string','max:255'], 'batch_lot'=>['nullable','string','max:100'],
            'customer_name'=>['nullable','string','max:255'], 'severity_id'=>['sometimes','exists:severities,id'],
            'status'=>['sometimes','string','in:open,under_review,in_progress,closed,rejected'],
            'root_cause'=>['required_if:status,closed','string'], 'corrective_action'=>['required_if:status,closed','string'],
            'preventive_action'=>['nullable','string'], 'capa_action_id'=>['nullable','exists:capa_actions,id'],
        ];
    }
}
