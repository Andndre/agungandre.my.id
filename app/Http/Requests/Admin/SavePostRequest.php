<?php

namespace App\Http\Requests\Admin;

use App\Rules\ValidPostEmbeds;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SavePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-posts');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['required', 'string', 'max:1000'],
            'content' => ['required', 'string', new ValidPostEmbeds],
            'publication' => ['required', Rule::in(['draft', 'now', 'scheduled'])],
            'published_at' => ['nullable', 'required_if:publication,scheduled', 'date', 'after:now'],
        ];
    }
}
