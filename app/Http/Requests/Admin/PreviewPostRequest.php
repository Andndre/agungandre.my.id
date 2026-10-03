<?php

namespace App\Http\Requests\Admin;

use App\Rules\ValidPostEmbeds;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class PreviewPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-posts');
    }

    public function rules(): array
    {
        return ['content' => ['required', 'string', new ValidPostEmbeds]];
    }
}
