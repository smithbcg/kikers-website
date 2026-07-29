<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/** Rebuilds the primary pages with the approved Kiker's yard and team photography. */
class m260721_160000_add_site_photography extends Migration
{
    public function safeUp(): bool
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';

        self::indexSiteAssets();

        $templates = [
            'index',
            'about.html',
            'sell-your-vehicle.html',
            'u-pull-parts.html',
            'full-service-parts.html',
            'contact.html',
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

    public static function indexSiteAssets(): void
    {
        $volume = Craft::$app->getVolumes()->getVolumeByHandle('siteAssets');
        if (!$volume) {
            throw new RuntimeException('The Site Assets volume is unavailable.');
        }

        $indexer = Craft::$app->getAssetIndexer();
        $session = $indexer->startIndexingSession([$volume->id], false);
        while ($session->processedEntries < $session->totalEntries && !$session->forceStop) {
            $indexer->processIndexSession($session);
        }
        $indexer->stopIndexingSession($session);

        if ($session->forceStop) {
            throw new RuntimeException('Craft could not finish indexing the Site Assets volume.');
        }
    }

    public function safeDown(): bool
    {
        echo "The photography update cannot be safely reverted after editor content is rebuilt.\n";
        return false;
    }
}
