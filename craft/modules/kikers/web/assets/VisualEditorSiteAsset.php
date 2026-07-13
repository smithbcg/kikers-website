<?php

declare(strict_types=1);

namespace kikers\web\assets;

use craft\web\AssetBundle;

class VisualEditorSiteAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@kikers/resources/visual-editor';
        $this->css = ['visual-editor.css'];
        $this->js = ['visual-editor.js'];
        parent::init();
    }
}
