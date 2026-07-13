<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\fieldlayoutelements\CustomField;
use craft\fieldlayoutelements\entries\EntryTitleField;
use craft\fields\Dropdown;
use craft\fields\PlainText;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use RuntimeException;

/**
 * Creates the initial Kiker content model and seeds all migrated page routes.
 */
class m260713_133038_create_kikers_content_model extends Migration
{
    public function safeUp(): bool
    {
        $fields = $this->createFields();
        $this->createSiteSettings($fields);
        $this->createPagesSection($fields);

        return true;
    }

    public function safeDown(): bool
    {
        echo "This migration seeds editor content and cannot be safely reverted.\n";

        return false;
    }

    /**
     * @return array<string, PlainText|Dropdown>
     */
    private function createFields(): array
    {
        $definitions = [
            'businessName' => ['name' => 'Business Name'],
            'phoneNumber' => ['name' => 'Phone Number'],
            'streetAddress' => ['name' => 'Street Address'],
            'weekdayHours' => ['name' => 'Weekday Hours'],
            'saturdayHours' => ['name' => 'Saturday Hours'],
            'googleRating' => ['name' => 'Google Rating'],
            'googleReviewCount' => ['name' => 'Google Review Count'],
            'directionsUrl' => ['name' => 'Directions URL'],
            'seoTitle' => ['name' => 'SEO Title'],
            'seoDescription' => [
                'name' => 'SEO Description',
                'multiline' => true,
                'initialRows' => 3,
            ],
            'pageHeading' => ['name' => 'Page Heading'],
            'pageSummary' => [
                'name' => 'Page Summary',
                'multiline' => true,
                'initialRows' => 4,
            ],
            'legacyTemplate' => [
                'name' => 'Migration Template',
                'instructions' => 'Temporary Twig template used while this page is converted to structured content.',
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
                $this->saveField($field);
            }
            $fields[$handle] = $field;
        }

        $pageType = Craft::$app->getFields()->getFieldByHandle('pageType');
        if (!$pageType) {
            $pageType = new Dropdown([
                'name' => 'Page Type',
                'handle' => 'pageType',
                'translationMethod' => 'none',
                'options' => [
                    ['label' => 'Standard', 'value' => 'standard', 'default' => true],
                    ['label' => 'Service', 'value' => 'service', 'default' => false],
                    ['label' => 'Location', 'value' => 'location', 'default' => false],
                    ['label' => 'Vehicle', 'value' => 'vehicle', 'default' => false],
                    ['label' => 'Article', 'value' => 'article', 'default' => false],
                    ['label' => 'Concept', 'value' => 'concept', 'default' => false],
                    ['label' => 'Utility', 'value' => 'utility', 'default' => false],
                ],
            ]);
            $this->saveField($pageType);
        }
        $fields['pageType'] = $pageType;

        return $fields;
    }

    /**
     * @param array<string, PlainText|Dropdown> $fields
     */
    private function createSiteSettings(array $fields): void
    {
        $globalSet = Craft::$app->getGlobals()->getSetByHandle('siteSettings');
        if (!$globalSet) {
            $globalSet = new GlobalSet([
                'name' => 'Site Settings',
                'handle' => 'siteSettings',
            ]);
            $globalSet->setFieldLayout($this->fieldLayout(GlobalSet::class, 'Business Details', [
                $fields['businessName'],
                $fields['phoneNumber'],
                $fields['streetAddress'],
                $fields['weekdayHours'],
                $fields['saturdayHours'],
                $fields['googleRating'],
                $fields['googleReviewCount'],
                $fields['directionsUrl'],
            ]));

            if (!Craft::$app->getGlobals()->saveSet($globalSet)) {
                throw new RuntimeException('Unable to create the Site Settings global set.');
            }

            $globalSet = Craft::$app->getGlobals()->getSetByHandle('siteSettings');
        }

        if (!$globalSet) {
            throw new RuntimeException('Unable to reload the Site Settings global set.');
        }

        $globalSet->setFieldValues([
            'businessName' => "Kiker's U-Pull-It",
            'phoneNumber' => '850-435-7630',
            'streetAddress' => '3010 W. Fairfield Drive, Pensacola, FL 32505',
            'weekdayHours' => 'Mon-Fri 9 AM-4:30 PM',
            'saturdayHours' => 'Sat 8 AM-2 PM',
            'googleRating' => '4.2',
            'googleReviewCount' => '687',
            'directionsUrl' => 'https://www.google.com/maps/dir/?api=1&destination=3010+W+Fairfield+Dr+Pensacola+FL+32505',
        ]);

        if (!Craft::$app->getElements()->saveElement($globalSet)) {
            throw new RuntimeException('Unable to seed the Site Settings global set.');
        }
    }

    /**
     * @param array<string, PlainText|Dropdown> $fields
     */
    private function createPagesSection(array $fields): void
    {
        $section = Craft::$app->getEntries()->getSectionByHandle('pages');
        $entryType = Craft::$app->getEntries()->getEntryTypeByHandle('page');
        $site = Craft::$app->getSites()->getPrimarySite();
        if (!$section) {
            if (!$entryType) {
                $entryType = new EntryType([
                    'name' => 'Page',
                    'handle' => 'page',
                    'description' => 'A public Kiker website page.',
                ]);
                $entryType->setFieldLayout($this->fieldLayout(Entry::class, 'Content', [
                    new EntryTitleField(),
                    $fields['pageType'],
                    $fields['pageHeading'],
                    $fields['pageSummary'],
                    $fields['seoTitle'],
                    $fields['seoDescription'],
                    $fields['legacyTemplate'],
                ]));

                if (!Craft::$app->getEntries()->saveEntryType($entryType)) {
                    throw new RuntimeException('Unable to create the Page entry type.');
                }
            }

            $section = new Section([
                'name' => 'Pages',
                'handle' => 'pages',
                'type' => Section::TYPE_STRUCTURE,
                'maxLevels' => 2,
                'enableVersioning' => true,
                'previewTargets' => [
                    ['label' => 'Primary page', 'urlFormat' => '{url}'],
                ],
            ]);
            $section->setEntryTypes([$entryType]);
            $section->setSiteSettings([
                new Section_SiteSettings([
                    'siteId' => $site->id,
                    'enabledByDefault' => true,
                    'hasUrls' => true,
                    'uriFormat' => '{slug}',
                    'template' => 'pages/_entry',
                ]),
            ]);

            if (!Craft::$app->getEntries()->saveSection($section)) {
                throw new RuntimeException('Unable to create the Pages section.');
            }
        }

        if (!$entryType) {
            throw new RuntimeException('Unable to load the Page entry type.');
        }

        $this->seedPages($section, $entryType, $site->id);
    }

    private function seedPages(Section $section, EntryType $entryType, int $siteId): void
    {
        $pages = [
            ['Home', 'home', 'index', 'standard'],
            ['About', 'about', 'about.html', 'standard'],
            ['Blog', 'blog', 'blog.html', 'article'],
            ['Cars for Sale', 'cars-for-sale', 'cars-for-sale.html', 'vehicle'],
            ['2018 Honda Civic LX', 'cars-for-sale-vehicle', 'cars-for-sale-vehicle.html', 'vehicle'],
            ['Contact & Visit', 'contact', 'contact.html', 'standard'],
            ['Contact & Visit Concept', 'contact-visit', 'Contact-Visit.html', 'concept'],
            ['Full-Service Parts', 'full-service-parts', 'full-service-parts.html', 'service'],
            ['Pull Your Own Parts', 'u-pull-parts', 'u-pull-parts.html', 'service'],
            ['Sell Your Vehicle', 'sell-your-vehicle', 'sell-your-vehicle.html', 'service'],
            ['Sell Your Vehicle Concept', 'sell-your-vehicle-v2', 'Sell-Your-Vehicle-v2.html', 'concept'],
            ['Sell Your Car in Pensacola', 'sell-your-car-pensacola', 'sell-your-car-pensacola.html', 'location'],
            ['Sell Your Car in Cantonment', 'sell-your-car-cantonment', 'sell-your-car-cantonment.html', 'location'],
            ['Sell Your Car in Milton', 'sell-your-car-milton', 'sell-your-car-milton.html', 'location'],
            ['Sell Your Car in Pace', 'sell-your-car-pace', 'sell-your-car-pace.html', 'location'],
            ['We Buy Cars Near Pensacola', 'we-buy-cars-near-pensacola', 'we-buy-cars-near-pensacola.html', 'location'],
            ['Privacy Policy', 'privacy', 'privacy.html', 'utility'],
            ['Thank You', 'thank-you', 'thank-you.html', 'utility'],
            ['Page Not Found', '404', '404.html', 'utility'],
            ['Homepage Concept', 'kikers-home', 'Kikers-Home.html', 'concept'],
            ['Homepage Concept V2', 'kikers-home-v2', 'Kikers-Home-v2.html', 'concept'],
            ['Homepage with Photos', 'kikers-home-with-photos', 'Kikers-Home-with-Photos.html', 'concept'],
            ['Homepage Funnel', 'home-funnel', 'home-funnel.html', 'concept'],
            ['Homepage Funnel V2', 'home-funnel-2', 'home-funnel-2.html', 'concept'],
            ['Icon Comparison', 'icon-comparison', 'Icon-Comparison.html', 'concept'],
            ['Component Kit', 'component-kit', 'docs/component-kit.html', 'concept'],
        ];

        foreach ($pages as [$title, $slug, $template, $pageType]) {
            $existingEntry = Entry::find()
                ->sectionId($section->id)
                ->slug($slug)
                ->siteId($siteId)
                ->status(null)
                ->one();
            if ($existingEntry) {
                continue;
            }

            $entry = new Entry([
                'sectionId' => $section->id,
                'typeId' => $entryType->id,
                'siteId' => $siteId,
                'title' => $title,
                'slug' => $slug,
                'enabled' => true,
            ]);
            $entry->setFieldValues([
                'pageType' => $pageType,
                'pageHeading' => $title,
                'seoTitle' => $title . " | Kiker's U-Pull-It",
                'legacyTemplate' => $template,
            ]);

            if (!Craft::$app->getElements()->saveElement($entry)) {
                throw new RuntimeException("Unable to seed the page entry: $title");
            }
        }
    }

    /**
     * @param array<int, PlainText|Dropdown|EntryTitleField> $elements
     */
    private function fieldLayout(string $type, string $tabName, array $elements): FieldLayout
    {
        $layout = new FieldLayout(['type' => $type]);
        $tab = new FieldLayoutTab([
            'name' => $tabName,
            'layout' => $layout,
            'sortOrder' => 1,
        ]);
        $tab->setElements(array_map(
            fn($element) => $element instanceof PlainText || $element instanceof Dropdown
                ? new CustomField($element)
                : $element,
            $elements,
        ));
        $layout->setTabs([$tab]);

        return $layout;
    }

    private function saveField(PlainText|Dropdown $field): void
    {
        if (!Craft::$app->getFields()->saveField($field)) {
            $error = $field->getFirstError() ?: 'Unknown field validation error.';
            throw new RuntimeException("Unable to create {$field->handle}: $error");
        }
    }
}
