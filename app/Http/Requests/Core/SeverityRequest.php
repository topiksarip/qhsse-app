<?php
namespace App\Http\Requests\Core;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class SeverityRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('severity')?->id; return ['code'=>['required','string','max:50',Rule::unique('severities','code')->ignore($id)],'name'=>['required','string','max:255'],'level'=>['required','integer','min:1','max:99'],'color'=>['required','string','max:50'],'description'=>['nullable','string','max:1000'],'is_active'=>['sometimes','boolean']]; } }
