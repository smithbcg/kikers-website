<?php

declare(strict_types=1);

namespace kikers\web\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class VisualEditorCpAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@kikers/resources/visual-editor';
        $this->depends = [CpAsset::class];
        $this->js = ['visual-editor-cp.js'];
        parent::init();
    }
}
