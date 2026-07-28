<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\base\Field;
use craft\db\Migration;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\fields\Assets;
use craft\fields\Table;
use craft\models\FieldLayoutTab;
use craft\models\Volume;
use RuntimeException;

/**
 * Adds footer controls, indexes the supplied brand assets, and refreshes
 * page-builder content so icons and new photography are CMS-managed.
 */
class m260728_160000_add_footer_controls_and_refresh_visual_editor extends Migration
{
    public function safeUp(): bool
    {
        $volume = Craft::$app->getVolumes()->getVolumeByHandle('siteAssets')
            ?? throw new RuntimeException('The Site Assets volume is unavailable.');
        $this->indexAssets($volume);

        $footerLogo = $this->footerLogoField($volume);
        $footerLinks = $this->footerLinksField();
        $this->addFooterTab($footerLogo, $footerLinks);
        $this->seedFooter($footerLogo, $footerLinks);
        $this->rebuildPages();

        return true;
    }

    public function safeDown(): bool
    {
        echo "Footer editor content and rebuilt page content cannot be safely reverted.\n";
        return false;
    }

    private function footerLogoField(Volume $volume): Assets
    {
        $existing = Craft::$app->getFields()->getFieldByHandle('footerLogo');
        if ($existing instanceof Assets) {
            return $existing;
        }

        return $this->saveField(new Assets([
            'name' => 'Footer Logo',
            'handle' => 'footerLogo',
            'instructions' => 'Choose the transparent logo shown on the dark site footer.',
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

    private function footerLinksField(): Table
    {
        $existing = Craft::$app->getFields()->getFieldByHandle('footerLinks');
        if ($existing instanceof Table) {
            return $existing;
        }

        return $this->saveField(new Table([
            'name' => 'Footer Links',
            'handle' => 'footerLinks',
            'instructions' => 'Add, remove, rename, reorder, or regroup footer links. Keep rows with the same group name together.',
            'translationMethod' => 'none',
            'addRowLabel' => 'Add a footer link',
            'minRows' => 0,
            'columns' => [
                'col1' => [
                    'heading' => 'Group',
                    'handle' => 'group',
                    'type' => 'singleline',
                ],
                'col2' => [
                    'heading' => 'Link label',
                    'handle' => 'label',
                    'type' => 'singleline',
                ],
                'col3' => [
                    'heading' => 'URL',
                    'handle' => 'url',
                    'type' => 'singleline',
                ],
            ],
            'defaults' => [],
        ]));
    }

    private function addFooterTab(Assets $footerLogo, Table $footerLinks): void
    {
        $globals = Craft::$app->getGlobals();
        $set = $globals->getSetByHandle('siteSettings')
            ?? throw new RuntimeException('The Site Settings global set is unavailable.');
        $layout = $set->getFieldLayout();

        foreach ($layout->getTabs() as $tab) {
            foreach ($tab->getElements() as $element) {
                if ($element instanceof CustomField && $element->getField()->handle === 'footerLinks') {
                    return;
                }
            }
        }

        $tab = new FieldLayoutTab([
            'name' => 'Footer',
            'layout' => $layout,
            'sortOrder' => count($layout->getTabs()) + 1,
        ]);
        $tab->setElements([
            new CustomField($footerLogo),
            new CustomField($footerLinks),
        ]);
        $layout->setTabs([...$layout->getTabs(), $tab]);
        $set->setFieldLayout($layout);

        if (!$globals->saveSet($set)) {
            throw new RuntimeException('Unable to add the Footer tab to Site Settings.');
        }
    }

    private function seedFooter(Assets $footerLogo, Table $footerLinks): void
    {
        $set = Craft::$app->getGlobals()->getSetByHandle('siteSettings')
            ?? throw new RuntimeException('The Site Settings global set is unavailable.');
        $logo = Asset::find()
            ->volume('siteAssets')
            ->filename('kikers-horizontal-white.svg')
            ->status(null)
            ->one();

        $values = [];
        if ($set->getFieldValue($footerLogo->handle)->count() === 0 && $logo) {
            $values[$footerLogo->handle] = [$logo->id];
        }
        if (!$set->getFieldValue($footerLinks->handle)) {
            $values[$footerLinks->handle] = [
                ['group' => 'Main', 'label' => 'Home', 'url' => '/home'],
                ['group' => 'Main', 'label' => 'About', 'url' => '/about'],
                ['group' => 'Main', 'label' => 'Contact', 'url' => '/contact'],
                ['group' => 'Main', 'label' => 'Privacy Policy', 'url' => '/privacy'],
                ['group' => 'Services', 'label' => 'Sell Your Vehicle', 'url' => '/sell-your-vehicle'],
                ['group' => 'Services', 'label' => 'We Buy Cars Near Pensacola', 'url' => '/we-buy-cars-near-pensacola'],
                ['group' => 'Services', 'label' => 'Pull Your Own Parts', 'url' => '/u-pull-parts'],
                ['group' => 'Services', 'label' => 'Full-Service Parts', 'url' => '/full-service-parts'],
                ['group' => 'Local Pages', 'label' => 'Sell Your Car in Pensacola', 'url' => '/sell-your-car-pensacola'],
                ['group' => 'Local Pages', 'label' => 'Sell Your Car in Pace', 'url' => '/sell-your-car-pace'],
                ['group' => 'Local Pages', 'label' => 'Sell Your Car in Milton', 'url' => '/sell-your-car-milton'],
                ['group' => 'Local Pages', 'label' => 'Sell Your Car in Cantonment', 'url' => '/sell-your-car-cantonment'],
            ];
        }

        if ($values === []) {
            return;
        }
        $set->setFieldValues($values);
        if (!Craft::$app->getElements()->saveElement($set)) {
            $errors = implode('; ', $set->getErrorSummary(true));
            throw new RuntimeException("Unable to seed footer settings: $errors");
        }
    }

    private function rebuildPages(): void
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';

        $pages = Entry::find()
            ->section('pages')
            ->status(null)
            ->drafts(false)
            ->revisions(false)
            ->orderBy(['lft' => SORT_ASC])
            ->all();
        $builder = new m260713_151000_seed_page_builder();

        foreach ($pages as $page) {
            if (in_array((string)$page->slug, ['home-funnel', 'contact-visit', 'icon-comparison'], true)) {
                continue;
            }
            if (trim((string)$page->getFieldValue('legacyTemplate')) === '') {
                continue;
            }

            [$sections, $customCss, $headHtml, $bodyScripts] = $builder->buildPage($page, $pages);
            $page->setFieldValues([
                'pageSections' => $sections,
                'pageCustomCss' => $customCss,
                'pageHeadHtml' => $headHtml,
                'pageBodyScripts' => $bodyScripts,
            ]);
            if (!Craft::$app->getElements()->saveElement($page)) {
                $errors = implode('; ', $page->getErrorSummary(true));
                throw new RuntimeException("Unable to refresh {$page->title}: $errors");
            }
        }
    }

    private function indexAssets(Volume $volume): void
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

    /** @template T of Field
     * @param T $field
     * @return T
     */
    private function saveField(Field $field): Field
    {
        if (!Craft::$app->getFields()->saveField($field)) {
            $errors = implode('; ', $field->getErrorSummary(true));
            throw new RuntimeException("Unable to create {$field->handle}: $errors");
        }
        return $field;
    }
}
