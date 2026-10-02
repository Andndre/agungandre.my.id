<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class DeletePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-posts');
    }

    public function rules(): array
    {
        return [];
    }
}
