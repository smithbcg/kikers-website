<?php

namespace kikers;

use Craft;

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
    }
}
