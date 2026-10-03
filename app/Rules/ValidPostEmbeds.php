<?php

namespace App\Rules;

use App\Support\PostEmbedUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Parser\MarkdownParser;

class ValidPostEmbeds implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
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
                $fail('Konten berisi URL embed yang tidak valid. Gunakan satu URL HTTPS.');

                return;
            }
        }
    }
}
