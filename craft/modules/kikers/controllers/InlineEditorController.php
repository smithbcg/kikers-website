<?php

declare(strict_types=1);

namespace kikers\controllers;

use Craft;
use craft\elements\Entry;
use craft\web\Controller;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class InlineEditorController extends Controller
{
    public function actionSave(): Response
    {
        $this->requireCpRequest();
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $elementId = (int)$request->getRequiredBodyParam('elementId');
        $siteId = (int)($request->getBodyParam('siteId') ?: Craft::$app->getSites()->getCurrentSite()->id);
        $value = (string)$request->getBodyParam('value', '');

        if (mb_strlen($value) > 20000) {
            throw new BadRequestHttpException('The inline value is too long.');
        }

        $entry = Entry::find()
            ->id($elementId)
            ->siteId($siteId)
            ->status(null)
            ->drafts(null)
            ->revisions(null)
            ->one();

        if (!$entry || $entry->getType()->handle !== 'sectionContentItem') {
            throw new NotFoundHttpException('The editable content item could not be found.');
        }

        $user = Craft::$app->getUser()->getIdentity();
        if (!$user || !$entry->canSave($user)) {
            throw new ForbiddenHttpException('You do not have permission to edit this content.');
        }

        $kindValue = $entry->getFieldValue('contentKind');
        $kind = is_object($kindValue) && isset($kindValue->value)
            ? (string)$kindValue->value
            : (string)$kindValue;
        if ($kind !== 'text') {
            throw new BadRequestHttpException('Only text values can be saved directly in the preview.');
        }

        $entry->setFieldValue('contentValue', $value);
        if (!Craft::$app->getElements()->saveElement($entry)) {
            return $this->asFailure('The text could not be saved.', [
                'errors' => $entry->getErrorSummary(true),
            ]);
        }

        return $this->asSuccess('Text updated.', [
            'elementId' => $entry->id,
            'value' => $value,
        ]);
    }
}
