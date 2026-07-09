<?php
namespace App\Http\Requests\Core;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class PriorityRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('priority')?->id; return ['code'=>['required','string','max:50',Rule::unique('priorities','code')->ignore($id)],'name'=>['required','string','max:255'],'sla_days'=>['required','integer','min:0','max:3650'],'color'=>['required','string','max:50'],'is_active'=>['sometimes','boolean']]; } }
