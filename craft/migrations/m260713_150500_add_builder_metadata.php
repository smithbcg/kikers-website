<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\PlainText;
use RuntimeException;

/** Adds stable content keys and retained head/body code to the page builder. */
class m260713_150500_add_builder_metadata extends Migration
{
    public function safeUp(): bool
    {
        $contentKey = $this->plainText(
            'Content Key',
            'contentKey',
            'Stable internal key used by the section layout. Do not change this value.',
            false,
            1,
        );
        $pageHeadHtml = $this->plainText(
            'Page Head HTML',
            'pageHeadHtml',
            'Advanced stylesheet and script tags retained from this page design.',
            true,
            8,
        );
        $pageBodyScripts = $this->plainText(
            'Page Body Scripts',
            'pageBodyScripts',
            'Advanced structured data and scripts retained from this page design.',
            true,
            10,
        );

        $this->appendFieldToEntryType('sectionContentItem', $contentKey, 'Content');
        $this->appendFieldToEntryType('page', $pageHeadHtml, 'Page Builder');
        $this->appendFieldToEntryType('page', $pageBodyScripts, 'Page Builder');

        return true;
    }

    public function safeDown(): bool
    {
        echo "The metadata fields may contain migrated page content and cannot be safely reverted.\n";
        return false;
    }

    private function plainText(
        string $name,
        string $handle,
        string $instructions,
        bool $multiline,
        int $rows,
    ): PlainText {
        $existing = Craft::$app->getFields()->getFieldByHandle($handle);
        if ($existing instanceof PlainText) {
            return $existing;
        }

        $field = new PlainText([
            'name' => $name,
            'handle' => $handle,
            'instructions' => $instructions,
            'multiline' => $multiline,
            'initialRows' => $rows,
            'code' => true,
            'translationMethod' => 'none',
        ]);
        if (!Craft::$app->getFields()->saveField($field)) {
            throw new RuntimeException("Unable to create $handle.");
        }

        return $field;
    }

    private function appendFieldToEntryType(string $typeHandle, PlainText $field, string $tabName): void
    {
        $entries = Craft::$app->getEntries();
        $type = $entries->getEntryTypeByHandle($typeHandle)
            ?? throw new RuntimeException("Entry type $typeHandle is unavailable.");
        $layout = $type->getFieldLayout();

        foreach ($layout->getTabs() as $tab) {
            foreach ($tab->getElements() as $element) {
                if ($element instanceof CustomField && $element->getField()->handle === $field->handle) {
                    return;
                }
            }
        }

        foreach ($layout->getTabs() as $tab) {
            if ($tab->name !== $tabName) {
                continue;
            }
            $tab->setElements([...$tab->getElements(), new CustomField($field)]);
            $type->setFieldLayout($layout);
            if (!$entries->saveEntryType($type)) {
                throw new RuntimeException("Unable to add {$field->handle} to $typeHandle.");
            }
            return;
        }

        throw new RuntimeException("The $tabName tab is unavailable on $typeHandle.");
    }
}
