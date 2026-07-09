<?php
namespace App\Http\Requests\Core;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class AreaRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('area')?->id; return ['site_id'=>['required','integer','exists:sites,id'],'code'=>['required','string','max:50',Rule::unique('areas','code')->ignore($id)],'name'=>['required','string','max:255'],'type'=>['nullable','string','max:100'],'is_active'=>['sometimes','boolean']]; } }
