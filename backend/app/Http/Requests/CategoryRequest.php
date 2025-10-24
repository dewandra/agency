<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $categoryId = $this->route('id');
        
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:categories,slug,' . $categoryId,
            'description' => 'nullable|string',
            'type' => 'required|in:article,video',
            'order' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ];

        // If updating, make fields optional
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['name'] = 'sometimes|string|max:255';
            $rules['type'] = 'sometimes|in:article,video';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.string' => 'Category name must be a valid string.',
            'name.max' => 'Category name cannot exceed 255 characters.',
            
            'slug.unique' => 'This slug is already in use.',
            'slug.max' => 'Slug cannot exceed 255 characters.',
            
            'type.required' => 'Category type is required.',
            'type.in' => 'Category type must be either "article" or "video".',
            
            'order.integer' => 'Order must be a number.',
            'order.min' => 'Order must be at least 0.',
            
            'is_active.boolean' => 'Active status must be true or false.',
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
                'message' => 'You do not have permission to manage categories.',
            ], 403)
        );
    }
}