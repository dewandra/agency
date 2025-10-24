<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Helpers\ResponseHelper;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => 'required|in:ADMIN,EDITOR',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.string' => 'Name must be a valid string.',
            'name.max' => 'Name cannot exceed 255 characters.',
            
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'email.max' => 'Email cannot exceed 255 characters.',
            
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            
            'role.required' => 'Role is required.',
            'role.in' => 'The selected role is invalid. Allowed values: ADMIN, EDITOR.',
            
            'is_active.boolean' => 'Active status must be true or false.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        // Flatten errors to string format
        $errors = [];
        foreach ($validator->errors()->toArray() as $field => $messages) {
            $errors[$field] = $messages[0]; // Take first error only
        }
        
        throw new HttpResponseException(
            response()->json([
                'status' => 422,
                'error' => 'Validation Error',
                'details' => $errors,
            ], 422)
        );
    }

    /**
     * Handle a failed authorization attempt.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            ResponseHelper::forbidden('You do not have permission to create users.')
        );
    }
}