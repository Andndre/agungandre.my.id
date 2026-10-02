<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UploadPostImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-posts');
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:10240'],
        ];
    }
}
