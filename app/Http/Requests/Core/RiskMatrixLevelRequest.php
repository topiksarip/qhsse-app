<?php
namespace App\Http\Requests\Core;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class RiskMatrixLevelRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('riskMatrixLevel')?->id; return ['likelihood'=>['required','integer','min:1','max:10'],'consequence'=>['required','integer','min:1','max:10'],'score'=>['required','integer','min:1','max:100'],'level'=>['required','string','max:100'],'color'=>['required','string','max:50'],'description'=>['nullable','string','max:1000'],'is_active'=>['sometimes','boolean']]; } }
