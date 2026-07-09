<?php
namespace App\Http\Requests\Core;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rule;
class SiteRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { $id=$this->route('site')?->id; return ['code'=>['required','string','max:50',Rule::unique('sites','code')->ignore($id)],'name'=>['required','string','max:255'],'address'=>['nullable','string','max:1000'],'is_active'=>['sometimes','boolean']]; } }
