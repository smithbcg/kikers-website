<?php

namespace kikers\controllers;

use Craft;
use craft\elements\Entry;
use craft\helpers\App;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use Throwable;
use yii\web\Response;

class SubmissionsController extends Controller
{
    protected array|bool|int $allowAnonymous = ['save'];

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $redirect = UrlHelper::siteUrl('thank-you');

        // Honeypot submissions receive a normal response but are not stored.
        if (trim((string)$request->getBodyParam('website')) !== '') {
            return $this->asSuccess('Thanks. We received your request.', redirect: $redirect);
        }

        $section = Craft::$app->getEntries()->getSectionByHandle('inquiries');
        $entryType = Craft::$app->getEntries()->getEntryTypeByHandle('inquiry');
        if (!$section || !$entryType) {
            Craft::error('The Inquiries content model is unavailable.', __METHOD__);
            return $this->asFailure('We could not save your request. Please call the yard.');
        }

        $payloadJson = (string)$request->getBodyParam('submissionPayload', '{}');
        try {
            $payload = Json::decode($payloadJson);
        } catch (Throwable) {
            $payload = [];
        }
        if (!is_array($payload)) {
            $payload = [];
        }

        $type = $this->clean($request->getBodyParam('submissionType', 'message'), 30);
        $name = $this->clean($payload['name'] ?? '', 120);
        $phone = $this->clean($payload['phone'] ?? '', 40);
        $email = $this->clean($payload['email'] ?? '', 160);
        $vehicle = $this->clean($payload['vehicle'] ?? '', 240);
        $condition = $this->clean($payload['condition'] ?? '', 120);
        $zip = $this->clean($payload['zip'] ?? '', 20);
        $subject = $this->clean($payload['subject'] ?? '', 240);
        $message = $this->clean($payload['message'] ?? '', 4000);
        $source = $this->clean($request->getBodyParam('submissionSource', '/'), 240);

        if ($phone === '' && $email === '') {
            return $this->asFailure('Please include a phone number or email address.');
        }

        $label = $type === 'vehicle' ? 'Vehicle offer' : 'Website request';
        $identity = $name ?: ($phone ?: $email);
        $entry = new Entry([
            'sectionId' => $section->id,
            'typeId' => $entryType->id,
            'siteId' => Craft::$app->getSites()->getCurrentSite()->id,
            'title' => StringHelper::truncate("$label - $identity - " . date('M j, Y g:i A'), 255),
            'enabled' => true,
        ]);
        $entry->setFieldValues([
            'submissionType' => $type,
            'submissionName' => $name,
            'submissionPhone' => $phone,
            'submissionEmail' => $email,
            'submissionVehicle' => $vehicle,
            'submissionCondition' => $condition,
            'submissionZip' => $zip,
            'submissionSubject' => $subject,
            'submissionMessage' => $message,
            'submissionSource' => $source,
            'submissionPayload' => Json::encode($payload, JSON_PRETTY_PRINT),
        ]);

        if (!Craft::$app->getElements()->saveElement($entry)) {
            Craft::error(['inquiryErrors' => $entry->getErrors()], __METHOD__);
            return $this->asFailure('We could not save your request. Please call the yard.');
        }

        $this->sendNotification($entry);

        return $this->asSuccess(
            'Thanks. We received your request.',
            ['entryId' => $entry->id],
            $redirect,
        );
    }

    private function sendNotification(Entry $entry): void
    {
        $recipient = App::env('KIKERS_NOTIFICATION_EMAIL') ?: 'sales@kikersautoparts.com';
        $body = [
            'Type: ' . $entry->submissionType,
            'Name: ' . $entry->submissionName,
            'Phone: ' . $entry->submissionPhone,
            'Email: ' . $entry->submissionEmail,
            'Vehicle: ' . $entry->submissionVehicle,
            'Condition: ' . $entry->submissionCondition,
            'ZIP: ' . $entry->submissionZip,
            'Subject: ' . $entry->submissionSubject,
            'Message: ' . $entry->submissionMessage,
            'Source: ' . $entry->submissionSource,
        ];

        try {
            Craft::$app->getMailer()
                ->compose()
                ->setTo($recipient)
                ->setSubject($entry->title)
                ->setTextBody(implode("\n", $body))
                ->send();
        } catch (Throwable $e) {
            // The entry is authoritative; a mail transport failure must not lose it.
            Craft::warning('Inquiry saved, but notification email failed: ' . $e->getMessage(), __METHOD__);
        }
    }

    private function clean(mixed $value, int $limit): string
    {
        $value = preg_replace('/\s+/', ' ', trim((string)$value)) ?: '';
        return StringHelper::truncate($value, $limit, '');
    }
}
