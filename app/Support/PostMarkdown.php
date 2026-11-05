<?php

namespace App\Support;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Inline\Code;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use League\CommonMark\Parser\MarkdownParserStateInterface;
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
        $this->registerMath($innerEnvironment);
        $innerConverter = new MarkdownConverter($innerEnvironment);

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $this->registerMath($environment);
        $environment->addRenderer(FencedCode::class, new class($innerConverter) implements NodeRendererInterface
        {
            public function __construct(private MarkdownConverter $innerConverter) {}

            public function render(Node $node, ChildNodeRendererInterface $childRenderer): ?string
            {
                if (! $node instanceof FencedCode) {
                    return null;
                }

                $info = trim((string) $node->getInfo());

                if (in_array(strtolower($info), ['latex', 'math'], true)) {
                    $formula = htmlspecialchars(trim($node->getLiteral()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    return '<div class="blog-math-block" data-math="display">'.$formula.'</div>';
                }

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

    private function registerMath(Environment $environment): void
    {
        $environment->addBlockStartParser(new class implements BlockStartParserInterface
        {
            public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
            {
                if ($cursor->isIndented() || $cursor->getNextNonSpaceCharacter() !== '$') {
                    return BlockStart::none();
                }

                $remainder = ltrim($cursor->getRemainder(), " \t");

                if (preg_match('/^(\${2,})([^\r\n]+?)\1[ \t]*$/', $remainder, $singleMatch) === 1) {
                    $fence = $singleMatch[1];
                    $formula = trim($singleMatch[2]);

                    if ($formula !== '' && ! str_contains($formula, $fence)) {
                        $cursor->advanceToEnd();
                        $block = new FencedCode(strlen($fence), '$', 0);
                        $block->setInfo('latex');
                        $block->setLiteral($formula);

                        return BlockStart::of(new class($block) extends AbstractBlockContinueParser
                        {
                            public function __construct(private FencedCode $block) {}

                            public function getBlock(): FencedCode
                            {
                                return $this->block;
                            }

                            public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
                            {
                                return BlockContinue::none();
                            }
                        })->at($cursor);
                    }
                }

                if (preg_match('/^(\${2,})[ \t]*$/', $remainder, $fenceMatch) === 1) {
                    $indent = $cursor->getIndent();
                    $fenceLength = strlen($fenceMatch[1]);
                    $cursor->advanceToEnd();

                    return BlockStart::of(new class($fenceLength, $indent) extends AbstractBlockContinueParser
                    {
                        private FencedCode $block;

                        /** @var list<string> */
                        private array $lines = [];

                        public function __construct(private int $fenceLength, int $fenceOffset)
                        {
                            $this->block = new FencedCode($fenceLength, '$', $fenceOffset);
                            $this->block->setInfo('latex');
                        }

                        public function getBlock(): FencedCode
                        {
                            return $this->block;
                        }

                        public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
                        {
                            if (! $cursor->isIndented() && $cursor->getNextNonSpaceCharacter() === '$') {
                                $closing = ltrim($cursor->getRemainder(), " \t");
                                if (preg_match('/^\${'.$this->fenceLength.',}[ \t]*$/', $closing) === 1) {
                                    $cursor->advanceToEnd();

                                    return BlockContinue::finished();
                                }
                            }

                            $fenceOffset = $this->block->getOffset();
                            while ($fenceOffset > 0 && $cursor->advanceBySpaceOrTab()) {
                                $fenceOffset--;
                            }

                            return BlockContinue::at($cursor);
                        }

                        public function addLine(string $line): void
                        {
                            $this->lines[] = $line;
                        }

                        public function closeBlock(): void
                        {
                            $this->block->setLiteral(trim(implode("\n", $this->lines)));
                        }
                    })->at($cursor);
                }

                return BlockStart::none();
            }
        }, 55);

        $environment->addInlineParser(new class implements InlineParserInterface
        {
            public function getMatchDefinition(): InlineParserMatch
            {
                return InlineParserMatch::regex('\$+');
            }

            public function parse(InlineParserContext $inlineContext): bool
            {
                $delimiters = $inlineContext->getFullMatch();
                $openLength = strlen($delimiters);
                $cursor = $inlineContext->getCursor();

                if ($openLength > 2) {
                    $cursor->advanceBy($openLength);
                    $inlineContext->getContainer()->appendChild(new Text($delimiters));

                    return true;
                }

                if ($openLength === 1) {
                    $prevChar = $cursor->peek(-1);
                    if ($prevChar !== null && preg_match('/[0-9A-Za-z]/', $prevChar) === 1) {
                        $cursor->advanceBy(1);
                        $inlineContext->getContainer()->appendChild(new Text('$'));

                        return true;
                    }
                }

                $cursor->advanceBy($openLength);
                $previousState = $cursor->saveState();

                $pattern = $openLength === 2
                    ? '/\A(?:\\\\.|[^$\\\\]|\$(?!\$))+?\$\$(?!\$)/s'
                    : '/\A(?![ \t\r\n])(?:\\\\.|[^$\\\\])+?(?<![ \t\r\n])\$(?![0-9$])/s';

                $matched = $cursor->match($pattern);

                if ($matched === null) {
                    $cursor->restoreState($previousState);
                    $inlineContext->getContainer()->appendChild(new Text($delimiters));

                    return true;
                }

                $formula = trim(substr($matched, 0, -$openLength));

                if ($formula === '') {
                    $cursor->restoreState($previousState);
                    $inlineContext->getContainer()->appendChild(new Text($delimiters));

                    return true;
                }

                $code = new Code($formula);
                $code->data->set('math', $openLength === 2 ? 'display' : 'inline');
                $inlineContext->getContainer()->appendChild($code);

                return true;
            }
        }, 75);

        $environment->addRenderer(FencedCode::class, new class implements NodeRendererInterface
        {
            public function render(Node $node, ChildNodeRendererInterface $childRenderer): ?string
            {
                if (! $node instanceof FencedCode) {
                    return null;
                }

                $info = trim((string) $node->getInfo());

                if (! in_array(strtolower($info), ['latex', 'math'], true)) {
                    return null;
                }

                $formula = htmlspecialchars(trim($node->getLiteral()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                return '<div class="blog-math-block" data-math="display">'.$formula.'</div>';
            }
        }, 5);

        $environment->addRenderer(Code::class, new class implements NodeRendererInterface
        {
            public function render(Node $node, ChildNodeRendererInterface $childRenderer): ?string
            {
                if (! $node instanceof Code) {
                    return null;
                }

                if (! $node->data->has('math')) {
                    return null;
                }

                $mode = $node->data->get('math', null);

                if (! in_array($mode, ['inline', 'display'], true)) {
                    return null;
                }

                if (
                    $mode === 'inline' &&
                    $node->parent() instanceof Paragraph &&
                    $node->previous() === null &&
                    $node->next() === null
                ) {
                    $mode = 'display';
                }

                $formula = htmlspecialchars(trim($node->getLiteral()), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $class = $mode === 'display' ? 'blog-math-block' : 'blog-math-inline';

                return '<span class="'.$class.'" data-math="'.$mode.'">'.$formula.'</span>';
            }
        }, 10);
    }
}
