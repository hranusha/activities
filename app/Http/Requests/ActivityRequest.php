<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       $isUpdate = $this->route('activity') || $this->has('activity_id');

       $imageRule = $isUpdate ? 'sometimes|image|max:2048' : 'required|image|max:2048';

       return [
           'title' => 'required|string|max:255',
           'description' => 'required|string',
           'image_url' => $imageRule,  
           'date' => 'required|date',
       ];
    }
}
