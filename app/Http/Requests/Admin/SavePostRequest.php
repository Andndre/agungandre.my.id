<?php

namespace App\Http\Requests\Admin;

use App\Support\PostEmbedUrl;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Parser\MarkdownParser;

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
            'content' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value)) {
                    return;
                }

                $environment = new Environment;
                $environment->addExtension(new CommonMarkCoreExtension);
                $walker = (new MarkdownParser($environment))->parse($value)->walker();

                while ($event = $walker->next()) {
                    $node = $event->getNode();

                    if ($event->isEntering()
                        && $node instanceof FencedCode
                        && trim((string) $node->getInfo()) === 'embed'
                        && PostEmbedUrl::parts(trim($node->getLiteral())) === null) {
                        $fail('The content contains an invalid embed URL. Use a single HTTPS URL.');

                        return;
                    }
                }
            }],
            'publication' => ['required', Rule::in(['draft', 'now', 'scheduled'])],
            'published_at' => ['nullable', 'required_if:publication,scheduled', 'date', 'after:now'],
        ];
    }
}
