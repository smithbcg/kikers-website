<?php

declare(strict_types=1);

namespace kikers\web;

use Craft;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use craft\base\NestedElementInterface;
use craft\elements\Entry;
use kikers\web\assets\VisualEditorSiteAsset;

class SectionRenderer
{
    /** @var array<string, array{placeholder:string,value:string,meta:array<string,mixed>}> */
    private array $items = [];

    public function render(Entry $section): string
    {
        $markup = (string)$section->getFieldValue('sectionMarkup');
        $this->items = [];

        $contentQuery = $section->getFieldValue('sectionContent');
        foreach ($contentQuery->all() as $index => $item) {
            $key = trim((string)$item->getFieldValue('contentKey'));
            if ($key === '') {
                continue;
            }

            $kindValue = $item->getFieldValue('contentKind');
            $kind = is_object($kindValue) && isset($kindValue->value)
                ? (string)$kindValue->value
                : (string)$kindValue;
            $value = (string)$item->getFieldValue('contentValue');
            if ($kind === 'image') {
                $asset = $item->getFieldValue('contentAsset')->one();
                if ($asset) {
                    $value = (string)$asset->getUrl();
                }
            }

            $placeholder = sprintf('KIKERS_VISUAL_TOKEN_%d_X', $index + 1);
            $markup = str_replace([
                "[[cms:$key]]",
                "%5B%5Bcms:$key%5D%5D",
                "CMS_TOKEN_$key",
            ], $placeholder, $markup);

            $this->items[$placeholder] = [
                'placeholder' => $placeholder,
                'value' => $value,
                'meta' => $this->metadata(
                    $item,
                    (string)($item->getFieldValue('contentLabel') ?: $item->title),
                    $kind,
                ),
            ];
        }

        if (!$this->isVisualPreview()) {
            return $this->replacePlainTokens($markup);
        }

        Craft::$app->getView()->registerAssetBundle(VisualEditorSiteAsset::class);
        return $this->annotateMarkup($markup);
    }

    /** @return array<string, mixed> */
    public function metadata(Entry $entry, ?string $label = null, string $kind = 'section'): array
    {
        $elementId = $entry->isProvisionalDraft ? $entry->getCanonicalId() : $entry->id;

        return array_filter([
            'elementId' => $elementId,
            'draftId' => $entry->isProvisionalDraft ? null : $entry->draftId,
            'revisionId' => $entry->revisionId,
            'siteId' => $entry->siteId,
            'ownerId' => $entry instanceof NestedElementInterface ? $entry->getOwnerId() : null,
            'label' => $label ?: $entry->title,
            'kind' => $kind,
        ], static fn(mixed $value): bool => $value !== null && $value !== '');
    }

    private function isVisualPreview(): bool
    {
        $request = Craft::$app->getRequest();
        return $request->getIsSiteRequest() && $request->getIsPreview();
    }

    private function replacePlainTokens(string $markup): string
    {
        foreach ($this->items as $item) {
            $markup = str_replace(
                $item['placeholder'],
                htmlspecialchars($item['value'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $markup,
            );
        }
        return $markup;
    }

    private function annotateMarkup(string $markup): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="kikers-visual-fragment">' . $markup . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING | LIBXML_NOERROR,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('kikers-visual-fragment');
        if (!$root instanceof DOMElement) {
            return $this->replacePlainTokens($markup);
        }

        $this->annotateNode($root, $document);

        $html = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $html .= $document->saveHTML($child);
        }
        return $html;
    }

    private function annotateNode(DOMNode $node, DOMDocument $document): void
    {
        if ($node instanceof DOMElement) {
            $attributeItems = [];
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $value = $attribute->value;
                foreach ($this->items as $item) {
                    if (!str_contains($value, $item['placeholder'])) {
                        continue;
                    }
                    $value = str_replace($item['placeholder'], $item['value'], $value);
                    $attributeItems[(string)$item['meta']['elementId']] = $item['meta'];
                }
                $node->setAttribute($attribute->name, $value);
            }
            if ($attributeItems !== []) {
                $this->setEditorItems($node, array_values($attributeItems));
            }
        }

        $children = iterator_to_array($node->childNodes);
        foreach ($children as $child) {
            if ($child instanceof DOMText) {
                $this->replaceTextNode($child, $document);
            } else {
                $this->annotateNode($child, $document);
            }
        }
    }

    private function replaceTextNode(DOMText $textNode, DOMDocument $document): void
    {
        $value = $textNode->nodeValue ?? '';
        $matches = [];
        foreach ($this->items as $item) {
            if (str_contains($value, $item['placeholder'])) {
                $matches[$item['placeholder']] = $item;
            }
        }
        if ($matches === []) {
            return;
        }

        $parent = $textNode->parentNode;
        if (!$parent instanceof DOMElement) {
            return;
        }

        $tag = strtolower($parent->tagName);
        if (in_array($tag, ['option', 'textarea', 'title', 'script', 'style'], true)) {
            $metadata = [];
            foreach ($matches as $item) {
                $value = str_replace($item['placeholder'], $item['value'], $value);
                $metadata[] = $item['meta'];
            }
            $textNode->nodeValue = $value;
            $this->setEditorItems($parent, $metadata);
            return;
        }

        $pattern = '/(' . implode('|', array_map('preg_quote', array_keys($matches))) . ')/';
        $parts = preg_split($pattern, $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return;
        }

        foreach ($parts as $part) {
            if (isset($matches[$part])) {
                $item = $matches[$part];
                $span = $document->createElement('span');
                $span->setAttribute('class', 'kve-editable-text');
                $this->setEditorItems($span, [$item['meta']]);
                $span->appendChild($document->createTextNode($item['value']));
                $parent->insertBefore($span, $textNode);
            } elseif ($part !== '') {
                $parent->insertBefore($document->createTextNode($part), $textNode);
            }
        }
        $parent->removeChild($textNode);
    }

    /** @param array<int, array<string,mixed>> $items */
    private function setEditorItems(DOMElement $element, array $items): void
    {
        $existing = $element->getAttribute('data-kve-items');
        if ($existing !== '') {
            $decoded = json_decode($existing, true);
            if (is_array($decoded)) {
                $items = array_merge($decoded, $items);
            }
        }

        $unique = [];
        foreach ($items as $item) {
            $unique[(string)$item['elementId']] = $item;
        }

        $element->setAttribute(
            'data-kve-items',
            (string)json_encode(array_values($unique), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }
}
