<?php

namespace App\Http\Requests\Admin;

use App\Rules\StaticWebImage;
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
            'image' => ['bail', 'required', 'file', 'max:10240', new StaticWebImage],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Pilih gambar untuk diunggah.',
            'image.file' => 'Pilih berkas gambar JPEG, PNG, atau WebP statis.',
            'image.max' => 'Ukuran gambar maksimal 10 MB.',
        ];
    }
}
