<?php

namespace kikers;

use Craft;
use kikers\twig\VisualEditorExtension;
use kikers\web\assets\VisualEditorCpAsset;

class Module extends \yii\base\Module
{
    public function init(): void
    {
        Craft::setAlias('@kikers', __DIR__);

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'kikers\\console\\controllers';
        } else {
            $this->controllerNamespace = 'kikers\\controllers';
        }

        parent::init();

        Craft::$app->getView()->registerTwigExtension(new VisualEditorExtension());

        $request = Craft::$app->getRequest();
        if (!$request->getIsConsoleRequest() && $request->getIsCpRequest()) {
            Craft::$app->getView()->registerAssetBundle(VisualEditorCpAsset::class);
        }
    }
}
