<?php
namespace App\Http\Requests\Core;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class DepartmentRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('department')?->id; return ['site_id'=>['nullable','integer','exists:sites,id'],'code'=>['required','string','max:50',Rule::unique('departments','code')->ignore($id)],'name'=>['required','string','max:255'],'is_active'=>['sometimes','boolean']]; } }
