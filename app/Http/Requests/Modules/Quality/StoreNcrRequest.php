<?php
namespace App\Http\Requests\Modules\Quality;
use Illuminate\Foundation\Http\FormRequest;
class StoreNcrRequest extends FormRequest {
    public function authorize(): bool { return $this->user()->can('quality.ncrs.create'); }
    public function rules(): array {
        return [
            'title'=>['required','string','max:255'], 'source'=>['required','string','in:internal,external,customer_complaint,audit,supplier'],
            'description'=>['required','string'], 'site_id'=>['required','exists:sites,id'], 'department_id'=>['nullable','exists:departments,id'],
            'product_service'=>['nullable','string','max:255'], 'batch_lot'=>['nullable','string','max:100'],
            'customer_name'=>['nullable','string','max:255'], 'severity_id'=>['required','exists:severities,id'],
        ];
    }
}
