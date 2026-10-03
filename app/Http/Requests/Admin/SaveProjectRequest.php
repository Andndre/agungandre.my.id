<?php

namespace App\Http\Requests\Admin;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing([
            'is_featured' => false,
            'is_published' => false,
            'tech_stack' => [],
        ]);
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'slug' => ['required', 'string', 'max:255', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', Rule::unique(Project::class, 'slug')->ignore($project)],
            'cover_image' => [$project ? 'nullable' : 'required', 'image', 'max:2048'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'max:2048'],
            'tech_stack' => ['nullable', 'array', 'max:20'],
            'tech_stack.*' => ['string', 'max:50'],
            'live_url' => ['nullable', 'url', 'max:500'],
            'repo_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
            'is_featured' => ['boolean'],
            'is_published' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'slug.unique' => 'Slug sudah digunakan oleh proyek lain.',
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'cover_image.image' => 'Cover harus berupa gambar.',
            'cover_image.max' => 'Ukuran cover maksimal 2 MB.',
            'images.max' => 'Galeri maksimal 10 gambar.',
            'images.*.image' => 'Setiap berkas galeri harus berupa gambar.',
            'images.*.max' => 'Ukuran setiap gambar maksimal 2 MB.',
            'tech_stack.max' => 'Maksimal 20 teknologi per proyek.',
            'is_featured.boolean' => 'Pilihan unggulan tidak valid.',
            'is_published.boolean' => 'Pilihan publikasi tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Judul', 'description' => 'Deskripsi', 'slug' => 'Slug', 'cover_image' => 'Cover'];
    }
}
