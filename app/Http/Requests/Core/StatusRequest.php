<?php
namespace App\Http\Requests\Core;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class StatusRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('status')?->id; return ['module'=>['required','string','max:100'],'code'=>['required','string','max:100',Rule::unique('statuses','code')->where('module',$this->input('module'))->ignore($id)],'name'=>['required','string','max:255'],'sequence'=>['required','integer','min:0','max:9999'],'is_terminal'=>['sometimes','boolean'],'is_active'=>['sometimes','boolean']]; } }
