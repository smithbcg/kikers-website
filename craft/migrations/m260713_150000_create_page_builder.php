<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\base\Field;
use craft\db\Migration;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\fieldlayoutelements\assets\AltField;
use craft\fieldlayoutelements\assets\AssetTitleField;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Assets;
use craft\fields\Dropdown;
use craft\fields\Lightswitch;
use craft\fields\Matrix;
use craft\fields\PlainText;
use craft\fields\Range;
use craft\fs\Local;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Volume;
use RuntimeException;

/**
 * Creates a nested-entry page builder while preserving the approved front end.
 */
class m260713_150000_create_page_builder extends Migration
{
    public function safeUp(): bool
    {
        $volume = $this->createAssetVolume();
        $this->indexExistingAssets($volume);

        $fields = $this->createFields($volume);
        $tokenType = $this->createContentTokenType($fields);
        $sectionContent = $this->createMatrixField(
            'Section Content',
            'sectionContent',
            'Every editable text value, link destination, and image in this section.',
            [$tokenType],
            Matrix::VIEW_MODE_CARDS,
            'Add content item',
        );
        $fields['sectionContent'] = $sectionContent;

        $sectionType = $this->createPageSectionType($fields);
        $pageSections = $this->createMatrixField(
            'Page Sections',
            'pageSections',
            'Reorder, disable, duplicate, or edit complete page sections.',
            [$sectionType],
            Matrix::VIEW_MODE_BLOCKS,
            'Add section',
        );

        $this->addBuilderToPageType($pageSections, $fields['pageCustomCss']);

        return true;
    }

    public function safeDown(): bool
    {
        echo "The page builder contains editor content and cannot be safely reverted.\n";
        return false;
    }

    private function createAssetVolume(): Volume
    {
        $fsService = Craft::$app->getFs();
        $fs = $fsService->getFilesystemByHandle('siteAssetsFs');
        if (!$fs) {
            $fs = new Local([
                'name' => 'Site Assets',
                'handle' => 'siteAssetsFs',
                'path' => '@webroot/assets',
                'hasUrls' => true,
                'url' => '$PRIMARY_SITE_URL/assets',
            ]);
            if (!$fsService->saveFilesystem($fs)) {
                throw new RuntimeException('Unable to create the Site Assets filesystem.');
            }
        }

        $volumes = Craft::$app->getVolumes();
        $volume = $volumes->getVolumeByHandle('siteAssets');
        if ($volume) {
            return $volume;
        }

        $volume = new Volume([
            'name' => 'Site Assets',
            'handle' => 'siteAssets',
            'fsHandle' => 'siteAssetsFs',
        ]);
        $layout = new FieldLayout(['type' => Asset::class]);
        $tab = new FieldLayoutTab([
            'name' => 'Content',
            'layout' => $layout,
            'sortOrder' => 1,
        ]);
        $tab->setElements([new AssetTitleField(), new AltField()]);
        $layout->setTabs([$tab]);
        $volume->setFieldLayout($layout);

        if (!$volumes->saveVolume($volume)) {
            throw new RuntimeException('Unable to create the Site Assets volume.');
        }

        return Craft::$app->getVolumes()->getVolumeByHandle('siteAssets')
            ?? throw new RuntimeException('Unable to reload the Site Assets volume.');
    }

    private function indexExistingAssets(Volume $volume): void
    {
        $indexer = Craft::$app->getAssetIndexer();
        $session = $indexer->createIndexingSession([$volume], false, true, false);
        foreach ($indexer->getIndexListOnVolume($volume) as $listing) {
            if ($listing->getIsDir()) {
                $indexer->indexFolderByListing($volume, $listing, $session->id, true);
            } else {
                $indexer->indexFileByListing($volume, $listing, $session->id, false, true);
            }
        }
        $indexer->stopIndexingSession($session);
    }

    /** @return array<string, Field> */
    private function createFields(Volume $volume): array
    {
        $fields = [];
        $plainText = [
            'pageCustomCss' => [
                'name' => 'Page-Specific CSS',
                'instructions' => 'Advanced styles retained from the approved page design.',
                'multiline' => true,
                'initialRows' => 12,
                'code' => true,
            ],
            'sectionAnchor' => [
                'name' => 'Section Anchor',
                'instructions' => 'Optional HTML id used for in-page links, without the # character.',
                'code' => true,
            ],
            'sectionCssClass' => [
                'name' => 'Additional CSS Classes',
                'instructions' => 'Advanced space-separated classes applied to the section wrapper.',
                'code' => true,
            ],
            'sectionMarkup' => [
                'name' => 'Section Layout Markup',
                'instructions' => 'Advanced layout template. Content values are managed in Section Content.',
                'multiline' => true,
                'initialRows' => 16,
                'code' => true,
            ],
            'contentLabel' => [
                'name' => 'Editor Label',
                'instructions' => 'Identifies where this value appears in the section.',
            ],
            'contentValue' => [
                'name' => 'Content Value',
                'instructions' => 'Edit the text, destination, alternative text, or fallback image URL.',
                'multiline' => true,
                'initialRows' => 4,
            ],
        ];

        foreach ($plainText as $handle => $config) {
            $fields[$handle] = $this->plainText($handle, $config);
        }

        $fields['sectionVariant'] = $this->dropdown('Section Type', 'sectionVariant', [
            ['label' => 'Preserve current layout', 'value' => 'preserve', 'default' => true],
            ['label' => 'Hero', 'value' => 'hero', 'default' => false],
            ['label' => 'Standard content', 'value' => 'standard', 'default' => false],
            ['label' => 'Cards or grid', 'value' => 'cards', 'default' => false],
            ['label' => 'Split content and media', 'value' => 'split', 'default' => false],
            ['label' => 'Form', 'value' => 'form', 'default' => false],
            ['label' => 'Call to action', 'value' => 'cta', 'default' => false],
            ['label' => 'Utility or divider', 'value' => 'utility', 'default' => false],
        ]);
        $fields['sectionTheme'] = $this->dropdown('Color Theme', 'sectionTheme', [
            ['label' => 'Preserve current colors', 'value' => 'inherit', 'default' => true],
            ['label' => 'Light', 'value' => 'light', 'default' => false],
            ['label' => 'Soft gray', 'value' => 'soft', 'default' => false],
            ['label' => 'Dark', 'value' => 'dark', 'default' => false],
            ['label' => 'Kiker red', 'value' => 'red', 'default' => false],
        ]);
        $fields['sectionWidth'] = $this->dropdown('Container Width', 'sectionWidth', [
            ['label' => 'Preserve current width', 'value' => 'inherit', 'default' => true],
            ['label' => 'Full width', 'value' => 'full', 'default' => false],
            ['label' => 'Standard', 'value' => 'standard', 'default' => false],
            ['label' => 'Narrow', 'value' => 'narrow', 'default' => false],
        ]);
        $fields['sectionSpacing'] = $this->dropdown('Vertical Spacing', 'sectionSpacing', [
            ['label' => 'Preserve current spacing', 'value' => 'inherit', 'default' => true],
            ['label' => 'None', 'value' => 'none', 'default' => false],
            ['label' => 'Compact', 'value' => 'compact', 'default' => false],
            ['label' => 'Standard', 'value' => 'standard', 'default' => false],
            ['label' => 'Spacious', 'value' => 'spacious', 'default' => false],
        ]);
        $fields['contentKind'] = $this->dropdown('Content Type', 'contentKind', [
            ['label' => 'Text', 'value' => 'text', 'default' => true],
            ['label' => 'Button or link destination', 'value' => 'url', 'default' => false],
            ['label' => 'Image', 'value' => 'image', 'default' => false],
            ['label' => 'HTML attribute', 'value' => 'attribute', 'default' => false],
        ]);

        $fields['sectionUseContainer'] = $this->field(new Lightswitch([
            'name' => 'Add Layout Container',
            'handle' => 'sectionUseContainer',
            'instructions' => 'Constrains newly-created section content to the selected width.',
            'default' => false,
            'onLabel' => 'Contained',
            'offLabel' => 'Use existing layout',
            'translationMethod' => 'none',
        ]));
        $fields['sectionOverlay'] = $this->field(new Range([
            'name' => 'Background Overlay',
            'handle' => 'sectionOverlay',
            'instructions' => 'Darkens the selected section background image.',
            'min' => 0,
            'max' => 90,
            'step' => 5,
            'defaultValue' => 0,
            'suffix' => '%',
            'translationMethod' => 'none',
        ]));
        $fields['sectionBackground'] = $this->assetField(
            'Background Image',
            'sectionBackground',
            'Optional background image for the complete section.',
            $volume,
        );
        $fields['contentAsset'] = $this->assetField(
            'Managed Image',
            'contentAsset',
            'Select or upload the image used at this location.',
            $volume,
        );

        return $fields;
    }

    /** @param array<string, Field> $fields */
    private function createContentTokenType(array $fields): EntryType
    {
        $entries = Craft::$app->getEntries();
        $type = $entries->getEntryTypeByHandle('sectionContentItem');
        if ($type) {
            return $type;
        }

        $type = new EntryType([
            'name' => 'Section Content Item',
            'handle' => 'sectionContentItem',
            'description' => 'One editable text value, destination, attribute, or image within a page section.',
        ]);
        $type->setFieldLayout($this->layout('Content', [
            new EntryTitleField(),
            $fields['contentLabel'],
            $fields['contentKind'],
            $fields['contentValue'],
            $fields['contentAsset'],
        ]));
        if (!$entries->saveEntryType($type)) {
            throw new RuntimeException('Unable to create the Section Content Item entry type.');
        }

        return $entries->getEntryTypeByHandle('sectionContentItem')
            ?? throw new RuntimeException('Unable to reload the Section Content Item entry type.');
    }

    /** @param array<string, Field> $fields */
    private function createPageSectionType(array $fields): EntryType
    {
        $entries = Craft::$app->getEntries();
        $type = $entries->getEntryTypeByHandle('pageSection');
        if ($type) {
            return $type;
        }

        $layout = new FieldLayout(['type' => Entry::class]);
        $contentTab = new FieldLayoutTab(['name' => 'Section', 'layout' => $layout, 'sortOrder' => 1]);
        $contentTab->setElements($this->elements([
            new EntryTitleField(),
            $fields['sectionVariant'],
            $fields['sectionTheme'],
            $fields['sectionWidth'],
            $fields['sectionSpacing'],
            $fields['sectionUseContainer'],
            $fields['sectionBackground'],
            $fields['sectionOverlay'],
            $fields['sectionAnchor'],
            $fields['sectionContent'],
        ]));
        $advancedTab = new FieldLayoutTab(['name' => 'Advanced', 'layout' => $layout, 'sortOrder' => 2]);
        $advancedTab->setElements($this->elements([
            $fields['sectionCssClass'],
            $fields['sectionMarkup'],
        ]));
        $layout->setTabs([$contentTab, $advancedTab]);

        $type = new EntryType([
            'name' => 'Page Section',
            'handle' => 'pageSection',
            'description' => 'A complete reorderable section of a public page.',
        ]);
        $type->setFieldLayout($layout);
        if (!$entries->saveEntryType($type)) {
            throw new RuntimeException('Unable to create the Page Section entry type.');
        }

        return $entries->getEntryTypeByHandle('pageSection')
            ?? throw new RuntimeException('Unable to reload the Page Section entry type.');
    }

    private function createMatrixField(
        string $name,
        string $handle,
        string $instructions,
        array $entryTypes,
        string $viewMode,
        string $buttonLabel,
    ): Matrix {
        $existing = Craft::$app->getFields()->getFieldByHandle($handle);
        if ($existing instanceof Matrix) {
            return $existing;
        }

        $field = new Matrix([
            'name' => $name,
            'handle' => $handle,
            'instructions' => $instructions,
            'viewMode' => $viewMode,
            'createButtonLabel' => $buttonLabel,
            'translationMethod' => 'none',
        ]);
        $field->setEntryTypes($entryTypes);
        return $this->field($field);
    }

    private function addBuilderToPageType(Matrix $pageSections, PlainText $pageCustomCss): void
    {
        $entries = Craft::$app->getEntries();
        $type = $entries->getEntryTypeByHandle('page')
            ?? throw new RuntimeException('The Page entry type is unavailable.');
        $layout = $type->getFieldLayout();

        foreach ($layout->getTabs() as $tab) {
            foreach ($tab->getElements() as $element) {
                if ($element instanceof CustomField && $element->getField()->handle === 'pageSections') {
                    return;
                }
            }
        }

        $builderTab = new FieldLayoutTab([
            'name' => 'Page Builder',
            'layout' => $layout,
            'sortOrder' => count($layout->getTabs()) + 1,
        ]);
        $builderTab->setElements([
            new CustomField($pageSections),
            new CustomField($pageCustomCss),
        ]);
        $layout->setTabs([...$layout->getTabs(), $builderTab]);
        $type->setFieldLayout($layout);

        if (!$entries->saveEntryType($type)) {
            throw new RuntimeException('Unable to add the Page Builder fields to pages.');
        }
    }

    private function plainText(string $handle, array $config): PlainText
    {
        $existing = Craft::$app->getFields()->getFieldByHandle($handle);
        if ($existing instanceof PlainText) {
            return $existing;
        }
        return $this->field(new PlainText(array_merge([
            'handle' => $handle,
            'translationMethod' => 'none',
        ], $config)));
    }

    private function dropdown(string $name, string $handle, array $options): Dropdown
    {
        $existing = Craft::$app->getFields()->getFieldByHandle($handle);
        if ($existing instanceof Dropdown) {
            return $existing;
        }
        return $this->field(new Dropdown([
            'name' => $name,
            'handle' => $handle,
            'options' => $options,
            'translationMethod' => 'none',
        ]));
    }

    private function assetField(string $name, string $handle, string $instructions, Volume $volume): Assets
    {
        $existing = Craft::$app->getFields()->getFieldByHandle($handle);
        if ($existing instanceof Assets) {
            return $existing;
        }
        return $this->field(new Assets([
            'name' => $name,
            'handle' => $handle,
            'instructions' => $instructions,
            'translationMethod' => 'none',
            'restrictLocation' => true,
            'restrictedLocationSource' => "volume:$volume->uid",
            'allowSubfolders' => true,
            'restrictFiles' => true,
            'allowedKinds' => ['image'],
            'maxRelations' => 1,
            'viewMode' => 'cards',
        ]));
    }

    private function field(Field $field): Field
    {
        if (!Craft::$app->getFields()->saveField($field)) {
            $error = $field->getFirstError() ?: 'Unknown validation error.';
            throw new RuntimeException("Unable to create {$field->handle}: $error");
        }
        return $field;
    }

    private function layout(string $tabName, array $items): FieldLayout
    {
        $layout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => $tabName, 'layout' => $layout, 'sortOrder' => 1]);
        $tab->setElements($this->elements($items));
        $layout->setTabs([$tab]);
        return $layout;
    }

    private function elements(array $items): array
    {
        return array_map(fn($item) => $item instanceof Field ? new CustomField($item) : $item, $items);
    }
}
