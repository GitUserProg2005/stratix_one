<?php

namespace App\Services\N8N\Handles;

use App\Enums\ScraperCompressionLevel;
use App\Services\N8N\BaseNode;
use DOMDocument;
use DOMElement;
use DOMNode;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use League\HTMLToMarkdown\HtmlConverter;
use Throwable;

class PageLoader extends BaseNode
{
    /** Подстроки class/id для удаления шума (extended, text_only) */
    private const NOISE_FRAGMENTS = [
        'sidebar', 'menu', 'preloader', 'header', 'footer',
        'cookie', 'banner', 'popup', 'modal',
    ];

    /** Теги основного контента после очистки DOM */
    private const CONTENT_TAGS = ['main', 'article'];

    /** Паттерн class основного контента, если нет CONTENT_TAGS */
    private const CONTENT_CLASS_PATTERN = '/(?:main-content|page-content|article-content|post-content|entry-content)/';

    public static function inputSchema(): array
    {
        return [];
    }

    public static function outputSchema(): array
    {
        return self::field('md_page', 'string', true);
    }

    public function handle(): array
    {
        $url = trim((string) $this->getConfig('url_source', ''));
        $level = ScraperCompressionLevel::tryFrom((string) $this->getConfig('compression_level', ''))
            ?? ScraperCompressionLevel::ScriptStyle;

        if ($url === '') {
            return $this->error('URL не указан');
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->error('Некорректный URL');
        }

        try {
            ['doc' => $doc, 'html' => $html] = $this->initializeBrowser($url);
            $this->domCompression($doc, $level);
            $markdown = $this->getContent($doc, $html, $level);
        } catch (Throwable $e) {
            return $this->error('Ошибка при сжатии верстки: '.$e->getMessage());
        }

        return $this->success([
            'md_page' => $markdown,
        ]);
    }

    /**
     * Headless Chrome: загрузка страницы и парсинг HTML в DOM.
     *
     * @return array{doc: DOMDocument, html: string}
     */
    private function initializeBrowser(string $url): array
    {
        $chrome = (new BrowserFactory())->createBrowser([
            'headless' => true,
            'noSandbox' => true,
        ]);

        try {
            $page = $chrome->createPage();
            $page->navigate($url)->waitForNavigation(Page::NETWORK_IDLE, 30000);
            $html = $page->getHtml();
        } finally {
            $chrome->close();
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();

        return ['doc' => $doc, 'html' => $html];
    }

    /**
     * Удаление тегов и DOM-шума по уровню сжатия.
     */
    private function domCompression(DOMDocument $doc, ScraperCompressionLevel $level): void
    {
        foreach ($level->tagsToRemove() as $tag) {
            $nodes = [];
            foreach ($doc->getElementsByTagName($tag) as $element) {
                $nodes[] = $element;
            }
            foreach ($nodes as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        if ($level->stripsNoiseByClass()) {
            $nodes = [];
            foreach ($doc->getElementsByTagName('*') as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }

                $class = strtolower($node->getAttribute('class'));
                $id = strtolower($node->getAttribute('id'));

                foreach (self::NOISE_FRAGMENTS as $fragment) {
                    if (str_contains($class, $fragment) || str_contains($id, $fragment)) {
                        $nodes[] = $node;
                        break;
                    }
                }
            }

            foreach ($nodes as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        if ($level->isTextOnly()) {
            $links = [];

            foreach ($doc->getElementsByTagName('a') as $link) {
                $links[] = $link;
            }

            foreach ($links as $link) {
                $parent = $link->parentNode;
                if (! $parent) {
                    continue;
                }
                while ($link->firstChild) {
                    $parent->insertBefore($link->firstChild, $link);
                }
                $parent->removeChild($link);
            }
        }
    }

    /**
     * Извлечение текста или markdown из очищенного DOM.
     */
    private function getContent(DOMDocument $doc, string $html, ScraperCompressionLevel $level): string
    {
        $body = $doc->getElementsByTagName('body')->item(0);
        $html = $body instanceof DOMNode
            ? ($doc->saveHTML($body) ?: $html)
            : ($doc->saveHTML() ?: $html);

        if ($level->isTextOnly()) {
            $source = null;

            foreach (self::CONTENT_TAGS as $tag) {
                $candidate = $doc->getElementsByTagName($tag)->item(0);
                if ($candidate instanceof DOMNode && trim($candidate->textContent) !== '') {
                    $source = $candidate;
                    break;
                }
            }

            if (! $source) {
                foreach ($doc->getElementsByTagName('*') as $node) {
                    if (! $node instanceof DOMElement) {
                        continue;
                    }

                    $role = strtolower($node->getAttribute('role'));
                    $class = strtolower($node->getAttribute('class'));

                    if ($role === 'main' || preg_match(self::CONTENT_CLASS_PATTERN, $class)) {
                        if (trim($node->textContent) !== '') {
                            $source = $node;
                            break;
                        }
                    }
                }
            }

            $text = ($source ?? $body) instanceof DOMNode
                ? ($source ?? $body)->textContent
                : $doc->textContent;

            $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/[ \t]+/u', ' ', $text) ?? $text;
            $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
            $text = preg_replace('/\n{3,}/u', "\n\n", $text) ?? $text;

            return trim($text);
        }

        return trim((new HtmlConverter(['hard_break' => true]))->convert($html));
    }
}
