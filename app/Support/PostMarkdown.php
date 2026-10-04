<?php

namespace App\Support;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;

class PostMarkdown
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
        ];

        $innerEnvironment = new Environment($config);
        $innerEnvironment->addExtension(new CommonMarkCoreExtension);
        $innerEnvironment->addExtension(new GithubFlavoredMarkdownExtension);
        $innerConverter = new MarkdownConverter($innerEnvironment);

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addRenderer(FencedCode::class, new class($innerConverter) implements NodeRendererInterface
        {
            public function __construct(private MarkdownConverter $innerConverter) {}

            public function render(Node $node, ChildNodeRendererInterface $childRenderer): ?string
            {
                if (! $node instanceof FencedCode) {
                    return null;
                }

                $info = trim((string) $node->getInfo());

                if (in_array($info, ['callout:info', 'callout:warning'], true)) {
                    $type = substr($info, strlen('callout:'));
                    $body = (string) $this->innerConverter->convert($node->getLiteral());

                    return '<aside class="blog-callout blog-callout-'.$type.'" role="note">'.$body.'</aside>';
                }

                if ($info !== 'embed') {
                    return null;
                }

                $url = trim($node->getLiteral());
                $parts = PostEmbedUrl::parts($url);

                if ($parts === null) {
                    return '<p class="blog-embed-error">Invalid embed URL</p>';
                }

                $host = strtolower($parts['host']);
                $path = $parts['path'] ?? '';
                $videoId = null;

                if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true) && $path === '/watch') {
                    parse_str($parts['query'] ?? '', $query);
                    $videoId = $query['v'] ?? null;
                } elseif ($host === 'youtu.be') {
                    $videoId = ltrim($path, '/');
                }

                if (is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)) {
                    return '<div class="blog-video"><iframe src="https://www.youtube-nocookie.com/embed/'.$videoId.'" title="YouTube video" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
                }

                $safeUrl = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeHost = htmlspecialchars($host, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return '<a class="blog-link-card" href="'.$safeUrl.'" rel="noopener noreferrer" target="_blank">'.$safeHost.' <svg class="blog-link-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 7h10v10"/><path d="M7 17 17 7"/></svg></a>';
            }
        }, 10);

        $this->converter = new MarkdownConverter($environment);
    }

    public function render(string $markdown): string
    {
        return (string) $this->converter->convert($markdown);
    }
}
