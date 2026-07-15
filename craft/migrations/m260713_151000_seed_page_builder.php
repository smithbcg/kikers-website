<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use craft\db\Migration;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\web\View;
use RuntimeException;

/** Converts the approved Twig pages into nested, editor-managed page sections. */
class m260713_151000_seed_page_builder extends Migration
{
    /** @var array<string, string> */
    private array $internalUrls = [];

    /** @var array<string, Asset|null> */
    private array $assets = [];

    public function safeUp(): bool
    {
        $pages = Entry::find()
            ->section('pages')
            ->status(null)
            ->drafts(false)
            ->revisions(false)
            ->orderBy(['lft' => SORT_ASC])
            ->all();

        $this->internalUrls = $this->internalUrlMap($pages);

        foreach ($pages as $page) {
            $existing = $page->getFieldValue('pageSections');
            if ($existing && $existing->status(null)->count() > 0) {
                continue;
            }

            $template = trim((string)$page->getFieldValue('legacyTemplate'));
            if ($template === '') {
                continue;
            }

            $view = Craft::$app->getView();
            $originalMode = $view->getTemplateMode();
            $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
            try {
                $html = $view->renderTemplate($template, ['entry' => $page]);
            } finally {
                $view->setTemplateMode($originalMode);
            }
            [$sections, $customCss, $headHtml, $bodyScripts] = $this->convertPage($html, $page);
            if ($sections['sortOrder'] === []) {
                throw new RuntimeException("No editable sections were found for {$page->title}.");
            }

            $page->setFieldValues([
                'pageSections' => $sections,
                'pageCustomCss' => $customCss,
                'pageHeadHtml' => $headHtml,
                'pageBodyScripts' => $bodyScripts,
            ]);
            if (!Craft::$app->getElements()->saveElement($page)) {
                $errors = implode('; ', $page->getErrorSummary(true));
                throw new RuntimeException("Unable to build {$page->title}: $errors");
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "The page builder contains migrated editor content and cannot be safely reverted.\n";
        return false;
    }

    /** @param Entry[] $pages
     *  @return array{array<string, mixed>, string, string, string}
     */
    public function buildPage(Entry $page, array $pages): array
    {
        $this->internalUrls = $this->internalUrlMap($pages);

        $template = trim((string)$page->getFieldValue('legacyTemplate'));
        if ($template === '') {
            throw new RuntimeException("No legacy template is set for {$page->title}.");
        }

        $view = Craft::$app->getView();
        $originalMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
        try {
            $html = $view->renderTemplate($template, ['entry' => $page]);
        } finally {
            $view->setTemplateMode($originalMode);
        }

        return $this->convertPage($html, $page);
    }

    /** @param Entry[] $pages */
    private function internalUrlMap(array $pages): array
    {
        $map = [
            'index.html' => '/home',
            './index.html' => '/home',
        ];
        foreach ($pages as $page) {
            $template = trim((string)$page->getFieldValue('legacyTemplate'));
            if ($template === '') {
                continue;
            }
            $path = str_ends_with($template, '.html') ? $template : "$template.html";
            $map[strtolower($path)] = '/' . ltrim((string)$page->uri, '/');
            $map[strtolower(basename($path))] = '/' . ltrim((string)$page->uri, '/');
        }
        return $map;
    }

    /** @return array{array<string, mixed>, string, string, string} */
    private function convertPage(string $html, Entry $page): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        $customCss = [];
        foreach ($xpath->query('//head/style') ?: [] as $style) {
            $customCss[] = $style->textContent;
        }

        $headHtml = [];
        foreach ($xpath->query('//head/link[@rel="stylesheet" or @rel="preload"] | //head/script') ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $href = $node->getAttribute('href');
                if (str_contains($href, 'kikers.css') || str_contains($href, 'fonts.googleapis.com')) {
                    continue;
                }
            }
            $headHtml[] = trim((string)$document->saveHTML($node));
        }

        $body = $xpath->query('//body')->item(0);
        if (!$body instanceof DOMElement) {
            throw new RuntimeException("The rendered page {$page->title} has no body element.");
        }

        $sectionOrder = [];
        $sectionEntries = [];
        $bodyScripts = [];
        $nodes = iterator_to_array($body->childNodes);
        $sectionNumber = 0;
        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement || $this->isWhitespaceOrSharedChrome($node)) {
                continue;
            }
            if (strtolower($node->tagName) === 'script') {
                if (!$this->isSharedScript($node)) {
                    $bodyScripts[] = trim((string)$document->saveHTML($node));
                }
                continue;
            }

            $sectionNumber++;
            $key = "new:$sectionNumber";
            $title = $this->sectionTitle($node, $sectionNumber);
            $contentItems = [];
            $contentCounter = 0;

            $this->tokenizeAttributes($node, $contentItems, $contentCounter);
            $this->tokenizeText($node, $contentItems, $contentCounter);
            $this->addEditableHeroImage($node, $contentItems, $contentCounter);

            $markup = trim((string)$document->saveHTML($node));
            $sectionOrder[] = $key;
            $sectionEntries[$key] = [
                'type' => 'pageSection',
                'enabled' => true,
                'title' => $title,
                'fields' => [
                    'sectionVariant' => $this->sectionVariant($node),
                    'sectionTheme' => 'inherit',
                    'sectionWidth' => 'inherit',
                    'sectionSpacing' => 'inherit',
                    'sectionUseContainer' => false,
                    'sectionBackground' => [],
                    'sectionOverlay' => 0,
                    'sectionAnchor' => '',
                    'sectionContent' => $this->contentMatrix($contentItems),
                    'sectionCssClass' => '',
                    'sectionMarkup' => $markup,
                ],
            ];
        }

        return [[
            'sortOrder' => $sectionOrder,
            'entries' => $sectionEntries,
        ], implode("\n\n", $customCss), implode("\n", $headHtml), implode("\n", $bodyScripts)];
    }

    private function isWhitespaceOrSharedChrome(DOMElement $node): bool
    {
        if (str_contains($node->textContent, 'YII-BLOCK-')) {
            return true;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['header', 'footer'], true)) {
            return true;
        }

        $id = $node->getAttribute('id');
        if (in_array($id, ['nav', 'scrim', 'drawer', 'toastHost'], true)) {
            return true;
        }

        $classes = preg_split('/\s+/', trim($node->getAttribute('class'))) ?: [];
        return array_intersect($classes, ['ustrip', 'scrim', 'drawer', 'toast-host']) !== [];
    }

    private function isSharedScript(DOMElement $node): bool
    {
        $src = $node->getAttribute('src');
        return $src !== '' && str_contains($src, 'kikers.js');
    }

    /** @param array<int, array<string, mixed>> $items */
    private function tokenizeAttributes(DOMElement $root, array &$items, int &$counter): void
    {
        $elements = [$root];
        foreach ($root->getElementsByTagName('*') as $element) {
            if ($element instanceof DOMElement) {
                $elements[] = $element;
            }
        }

        foreach ($elements as $element) {
            if (in_array(strtolower($element->tagName), ['script', 'style'], true)) {
                continue;
            }
            $attributes = [];
            foreach ($element->attributes as $attribute) {
                $attributes[$attribute->name] = $attribute->value;
            }
            foreach ($attributes as $name => $value) {
                if ($value === '' || !$this->editableAttribute($element, $name)) {
                    continue;
                }
                $kind = $this->attributeKind($element, $name);
                $storedValue = $kind === 'url' ? $this->canonicalUrl($value) : $value;
                $label = $this->attributeLabel($element, $name);
                $key = $this->appendItem($items, $counter, $label, $kind, $storedValue);
                $element->setAttribute($name, "CMS_TOKEN_$key");
            }
        }
    }

    private function editableAttribute(DOMElement $element, string $name): bool
    {
        $tag = strtolower($element->tagName);
        if ($name === 'href' && $tag === 'a') {
            return true;
        }
        if ($name === 'src' && in_array($tag, ['img', 'iframe', 'source', 'video'], true)) {
            return true;
        }
        if ($name === 'poster' && $tag === 'video') {
            return true;
        }
        if (in_array($name, ['alt', 'placeholder', 'aria-label', 'title'], true)) {
            return true;
        }
        return $name === 'value' && $tag === 'input' && strtolower($element->getAttribute('type')) !== 'hidden';
    }

    private function attributeKind(DOMElement $element, string $name): string
    {
        $tag = strtolower($element->tagName);
        if (($name === 'src' && in_array($tag, ['img', 'source'], true)) || $name === 'poster') {
            return 'image';
        }
        return in_array($name, ['href', 'src'], true) ? 'url' : 'attribute';
    }

    private function attributeLabel(DOMElement $element, string $name): string
    {
        $text = $this->excerpt($element->textContent);
        if ($name === 'href') {
            $button = str_contains(' ' . $element->getAttribute('class') . ' ', ' btn ');
            return ($button ? 'Button destination' : 'Link destination') . ($text !== '' ? ": $text" : '');
        }
        if ($name === 'src' && strtolower($element->tagName) === 'img') {
            return 'Image' . ($element->getAttribute('alt') !== '' ? ': ' . $this->excerpt($element->getAttribute('alt')) : '');
        }
        return ucfirst($name) . ($text !== '' ? ": $text" : '');
    }

    /** @param array<int, array<string, mixed>> $items */
    private function tokenizeText(DOMNode $node, array &$items, int &$counter): void
    {
        if ($node instanceof DOMElement && in_array(strtolower($node->tagName), ['script', 'style'], true)) {
            return;
        }

        $children = iterator_to_array($node->childNodes);
        foreach ($children as $child) {
            if ($child instanceof DOMText && trim($child->nodeValue ?? '') !== '') {
                $value = $child->nodeValue ?? '';
                $label = $this->textLabel($child, $value);
                preg_match('/^(\s*)(.*?)(\s*)$/us', $value, $parts);
                $leading = $parts[1] ?? '';
                $editable = $parts[2] ?? $value;
                $trailing = $parts[3] ?? '';
                $key = $this->appendItem($items, $counter, $label, 'text', $editable);
                $child->nodeValue = $leading . "CMS_TOKEN_$key" . $trailing;
                continue;
            }
            if ($child->hasChildNodes()) {
                $this->tokenizeText($child, $items, $counter);
            }
        }
    }

    private function textLabel(DOMText $text, string $value): string
    {
        $parent = $text->parentNode instanceof DOMElement ? $text->parentNode : null;
        $tag = $parent ? strtolower($parent->tagName) : 'text';
        $role = match ($tag) {
            'h1' => 'Main heading',
            'h2', 'h3', 'h4', 'h5' => 'Heading',
            'a' => str_contains(' ' . $parent->getAttribute('class') . ' ', ' btn ') ? 'Button label' : 'Link label',
            'button' => 'Button label',
            'label' => 'Form label',
            'option' => 'Form option',
            'p' => 'Paragraph',
            'li' => 'List item',
            default => ucfirst($tag) . ' text',
        };
        return "$role: " . $this->excerpt($value);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function addEditableHeroImage(DOMElement $root, array &$items, int &$counter): void
    {
        $hero = null;
        foreach ($root->getElementsByTagName('div') as $div) {
            if ($div instanceof DOMElement && str_contains(' ' . $div->getAttribute('class') . ' ', ' hero-photo--yard ')) {
                $hero = $div;
                break;
            }
        }
        if (!$hero) {
            return;
        }

        $key = $this->appendItem(
            $items,
            $counter,
            'Hero background image',
            'image',
            '/assets/kikers-yard-hero.jpg',
        );
        $style = trim($hero->getAttribute('style'));
        $style .= ($style !== '' && !str_ends_with($style, ';') ? ';' : '') . "background-image:url('CMS_TOKEN_$key')";
        $hero->setAttribute('style', $style);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function appendItem(
        array &$items,
        int &$counter,
        string $label,
        string $kind,
        string $value,
    ): string {
        $counter++;
        $key = sprintf('c%03d', $counter);
        $asset = $kind === 'image' ? $this->assetForUrl($value) : null;
        $items[] = [
            'key' => $key,
            'label' => $label,
            'kind' => $kind,
            'value' => $value,
            'assetId' => $asset?->id,
        ];
        return $key;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function contentMatrix(array $items): array
    {
        $order = [];
        $entries = [];
        foreach ($items as $index => $item) {
            $entryKey = 'new:' . ($index + 1);
            $order[] = $entryKey;
            $entries[$entryKey] = [
                'type' => 'sectionContentItem',
                'enabled' => true,
                'title' => $item['label'],
                'fields' => [
                    'contentLabel' => $item['label'],
                    'contentKind' => $item['kind'],
                    'contentValue' => $item['value'],
                    'contentAsset' => $item['assetId'] ? [$item['assetId']] : [],
                    'contentKey' => $item['key'],
                ],
            ];
        }
        return ['sortOrder' => $order, 'entries' => $entries];
    }

    private function assetForUrl(string $url): ?Asset
    {
        $path = (string)(parse_url($url, PHP_URL_PATH) ?: $url);
        $filename = basename($path);
        if ($filename === '') {
            return null;
        }
        if (!array_key_exists($filename, $this->assets)) {
            $this->assets[$filename] = Asset::find()
                ->volume('siteAssets')
                ->filename($filename)
                ->status(null)
                ->one();
        }
        return $this->assets[$filename];
    }

    private function canonicalUrl(string $url): string
    {
        if ($url === '' || str_starts_with($url, '#') || str_contains($url, '://') || str_starts_with($url, 'tel:') || str_starts_with($url, 'mailto:')) {
            return $url;
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? $url;
        $lookup = strtolower(ltrim($path, '/'));
        if (!isset($this->internalUrls[$lookup])) {
            return $url;
        }
        $suffix = isset($parts['query']) ? '?' . $parts['query'] : '';
        $suffix .= isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return $this->internalUrls[$lookup] . $suffix;
    }

    private function sectionTitle(DOMElement $node, int $number): string
    {
        foreach (['h1', 'h2', 'h3'] as $tag) {
            $heading = $node->getElementsByTagName($tag)->item(0);
            if ($heading && trim($heading->textContent) !== '') {
                return $this->excerpt($heading->textContent, 78);
            }
        }
        if ($node->getAttribute('id') !== '') {
            return ucwords(str_replace(['-', '_'], ' ', $node->getAttribute('id')));
        }
        $class = trim($node->getAttribute('class'));
        if ($class !== '') {
            return ucwords(str_replace(['-', '_'], ' ', explode(' ', $class)[0]));
        }
        return 'Section ' . $number;
    }

    private function sectionVariant(DOMElement $node): string
    {
        $classes = ' ' . strtolower($node->getAttribute('class')) . ' ';
        if (str_contains($classes, ' hero ')) {
            return 'hero';
        }
        if (str_contains($classes, ' finalcta ') || str_contains($classes, ' cta ')) {
            return 'cta';
        }
        if ($node->getElementsByTagName('form')->length > 0) {
            return 'form';
        }
        if (str_contains($classes, ' cards') || str_contains($classes, ' grid')) {
            return 'cards';
        }
        if (str_contains($classes, ' split')) {
            return 'split';
        }
        if (in_array(strtolower($node->tagName), ['hr'], true) || str_contains($classes, 'rule') || str_contains($classes, 'hazard')) {
            return 'utility';
        }
        return 'preserve';
    }

    private function excerpt(string $value, int $limit = 58): string
    {
        $value = trim((string)preg_replace('/\s+/u', ' ', $value));
        if (mb_strlen($value) <= $limit) {
            return $value;
        }
        return rtrim(mb_substr($value, 0, $limit - 1)) . '…';
    }
}
