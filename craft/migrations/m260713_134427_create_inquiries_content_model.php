<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use RuntimeException;

/**
 * Adds the Craft-native storage used by public website forms.
 */
class m260713_134427_create_inquiries_content_model extends Migration
{
    public function safeUp(): bool
    {
        $fields = $this->createFields();
        $entries = Craft::$app->getEntries();

        $entryType = $entries->getEntryTypeByHandle('inquiry');
        if (!$entryType) {
            $entryType = new EntryType([
                'name' => 'Inquiry',
                'handle' => 'inquiry',
                'description' => 'A vehicle offer or general request submitted from the website.',
            ]);
            $entryType->setFieldLayout($this->fieldLayout($fields));
            if (!$entries->saveEntryType($entryType)) {
                throw new RuntimeException('Unable to create the Inquiry entry type.');
            }
        }

        if (!$entries->getSectionByHandle('inquiries')) {
            $site = Craft::$app->getSites()->getPrimarySite();
            $section = new Section([
                'name' => 'Inquiries',
                'handle' => 'inquiries',
                'type' => Section::TYPE_CHANNEL,
                'minAuthors' => 0,
                'maxAuthors' => 0,
                'enableVersioning' => false,
                'previewTargets' => [],
            ]);
            $section->setEntryTypes([$entryType]);
            $section->setSiteSettings([
                new Section_SiteSettings([
                    'siteId' => $site->id,
                    'enabledByDefault' => true,
                    'hasUrls' => false,
                    'uriFormat' => null,
                    'template' => null,
                ]),
            ]);

            if (!$entries->saveSection($section)) {
                throw new RuntimeException('Unable to create the Inquiries section.');
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "This migration may contain customer inquiries and cannot be safely reverted.\n";
        return false;
    }

    /**
     * @return array<string, PlainText>
     */
    private function createFields(): array
    {
        $definitions = [
            'submissionType' => ['name' => 'Request Type'],
            'submissionName' => ['name' => 'Name'],
            'submissionPhone' => ['name' => 'Phone'],
            'submissionEmail' => ['name' => 'Email'],
            'submissionVehicle' => ['name' => 'Vehicle'],
            'submissionCondition' => ['name' => 'Condition'],
            'submissionZip' => ['name' => 'ZIP Code'],
            'submissionSubject' => ['name' => 'Subject or Part Needed'],
            'submissionMessage' => [
                'name' => 'Message',
                'multiline' => true,
                'initialRows' => 5,
            ],
            'submissionSource' => ['name' => 'Source Page', 'code' => true],
            'submissionPayload' => [
                'name' => 'Complete Submission',
                'instructions' => 'Raw normalized form values retained for reference.',
                'multiline' => true,
                'initialRows' => 12,
                'code' => true,
            ],
        ];

        $fields = [];
        foreach ($definitions as $handle => $config) {
            $field = Craft::$app->getFields()->getFieldByHandle($handle);
            if (!$field) {
                $field = new PlainText(array_merge([
                    'handle' => $handle,
                    'translationMethod' => 'none',
                ], $config));
                if (!Craft::$app->getFields()->saveField($field)) {
                    $error = $field->getFirstError() ?: 'Unknown field validation error.';
                    throw new RuntimeException("Unable to create $handle: $error");
                }
            }
            $fields[$handle] = $field;
        }

        return $fields;
    }

    /**
     * @param array<string, PlainText> $fields
     */
    private function fieldLayout(array $fields): FieldLayout
    {
        $layout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab([
            'name' => 'Request',
            'layout' => $layout,
            'sortOrder' => 1,
        ]);
        $elements = [new EntryTitleField()];
        foreach ($fields as $field) {
            $elements[] = new CustomField($field);
        }
        $tab->setElements($elements);
        $layout->setTabs([$tab]);

        return $layout;
    }
}
