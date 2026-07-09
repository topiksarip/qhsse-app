<?php
namespace App\Http\Requests\Core;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class PositionRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('position')?->id; return ['department_id'=>['nullable','integer','exists:departments,id'],'code'=>['required','string','max:50',Rule::unique('positions','code')->ignore($id)],'name'=>['required','string','max:255'],'is_active'=>['sometimes','boolean']]; } }
