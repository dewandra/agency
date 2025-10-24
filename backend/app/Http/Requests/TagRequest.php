<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->isEditor());
    }

    public function rules(): array
    {
        $tagId = $this->route('id');
        
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:tags,slug,' . $tagId,
            'color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];

        // If updating, make name optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'sometimes|string|max:255';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tag name is required.',
            'name.string' => 'Tag name must be a valid string.',
            'name.max' => 'Tag name cannot exceed 255 characters.',
            
            'slug.unique' => 'This slug is already in use.',
            'slug.max' => 'Slug cannot exceed 255 characters.',
            
            'color.regex' => 'Color must be a valid hex color code (e.g., #3B82F6).',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = [];
        foreach ($validator->errors()->toArray() as $field => $messages) {
            $errors[$field] = $messages[0];
        }
        
        throw new HttpResponseException(
            response()->json([
                'status' => 422,
                'error' => 'Validation Error',
                'details' => $errors,
            ], 422)
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 403,
                'error' => 'Forbidden',
                'message' => 'You do not have permission to manage tags.',
            ], 403)
        );
    }
}