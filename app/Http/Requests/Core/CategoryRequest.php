<?php
namespace App\Http\Requests\Core;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class CategoryRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('category')?->id; return ['parent_id'=>['nullable','integer','exists:categories,id'],'module'=>['required','string','max:100'],'code'=>['required','string','max:100',Rule::unique('categories','code')->where('module',$this->input('module'))->ignore($id)],'name'=>['required','string','max:255'],'is_active'=>['sometimes','boolean']]; } }
