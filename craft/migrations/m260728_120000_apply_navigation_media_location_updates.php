<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/**
 * Applies the approved navigation, photography, funnel, and location-page updates.
 */
class m260728_120000_apply_navigation_media_location_updates extends Migration
{
    public function safeUp(): bool
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';
        require_once __DIR__ . '/m260721_160000_add_site_photography.php';

        m260721_160000_add_site_photography::indexSiteAssets();
        $this->removeRetiredPages();

        $templates = [
            'index',
            'about.html',
            'contact.html',
            'full-service-parts.html',
            'u-pull-parts.html',
            'sell-your-vehicle.html',
            'sell-your-car-pensacola.html',
            'sell-your-car-milton.html',
            'sell-your-car-pace.html',
            'sell-your-car-cantonment.html',
            'we-buy-cars-near-pensacola.html',
            'home-funnel-2.html',
        ];
        $pages = Entry::find()
            ->section('pages')
            ->status(null)
            ->drafts(false)
            ->revisions(false)
            ->orderBy(['lft' => SORT_ASC])
            ->all();
        $builder = new m260713_151000_seed_page_builder();

        foreach ($pages as $page) {
            $template = trim((string)$page->getFieldValue('legacyTemplate'));
            if (!in_array($template, $templates, true)) {
                continue;
            }

            [$sections, $customCss, $headHtml, $bodyScripts] = $builder->buildPage($page, $pages);
            if ($sections['sortOrder'] === []) {
                throw new RuntimeException("No editable sections were found for {$page->title}.");
            }

            $page->setFieldValues([
                'pageSections' => $sections,
                'pageCustomCss' => $customCss,
                'pageHeadHtml' => $headHtml,
                'pageBodyScripts' => $bodyScripts,
            ]);
            if (!Craft::$app->getElements()->saveElement($page)) {
                $errors = implode('; ', $page->getErrorSummary(true));
                throw new RuntimeException("Unable to update {$page->title}: $errors");
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "Retired pages and rebuilt editor content cannot be safely restored.\n";
        return false;
    }

    private function removeRetiredPages(): void
    {
        $retiredSlugs = ['home-funnel', 'contact-visit', 'icon-comparison'];
        $entries = Entry::find()
            ->section('pages')
            ->slug($retiredSlugs)
            ->status(null)
            ->drafts(false)
            ->revisions(false)
            ->all();

        foreach ($entries as $entry) {
            if (!Craft::$app->getElements()->deleteElement($entry, true)) {
                throw new RuntimeException("Unable to remove the retired page {$entry->title}.");
            }
        }
    }
}
