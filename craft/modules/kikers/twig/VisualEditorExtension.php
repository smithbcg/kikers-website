<?php

declare(strict_types=1);

namespace kikers\twig;

use craft\elements\Entry;
use kikers\web\SectionRenderer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VisualEditorExtension extends AbstractExtension
{
    private SectionRenderer $renderer;

    public function __construct()
    {
        $this->renderer = new SectionRenderer();
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('renderKikersSection', [$this->renderer, 'render'], ['is_safe' => ['html']]),
            new TwigFunction('kikersEditorMetadata', [$this->renderer, 'metadata']),
        ];
    }

    public function render(Entry $section): string
    {
        return $this->renderer->render($section);
    }
}
