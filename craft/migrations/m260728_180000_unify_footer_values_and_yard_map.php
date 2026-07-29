<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/**
 * Retires the unused home concepts, keeps the footer universal, and rebuilds
 * the pages that now share the expanded yard map and values components.
 */
class m260728_180000_unify_footer_values_and_yard_map extends Migration
{
    public function safeUp(): bool
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';

        $this->removeRetiredPages();
        $this->removeRetiredFooterLinks();
        $this->rebuildPages();

        return true;
    }

    public function safeDown(): bool
    {
        echo "Retired pages and rebuilt editor content cannot be safely restored.\n";
        return false;
    }

    private function removeRetiredPages(): void
    {
        $entries = Entry::find()
            ->section('pages')
            ->slug(['kikers-home', 'kikers-home-with-photos'])
            ->status(null)
            ->drafts(false)
            ->revisions(false)
            ->all();

        foreach ($entries as $entry) {
            if (!Craft::$app->getElements()->deleteElement($entry, true)) {
                throw new RuntimeException("Unable to permanently remove the retired page {$entry->title}.");
            }
        }
    }

    private function removeRetiredFooterLinks(): void
    {
        $set = Craft::$app->getGlobals()->getSetByHandle('siteSettings')
            ?? throw new RuntimeException('The Site Settings global set is unavailable.');
        $rows = $set->getFieldValue('footerLinks') ?: [];
        $retiredUrls = ['/blog', '/tips-guides', '/kikers-home', '/kikers-home-with-photos'];
        $retiredLabels = [
            'blog',
            'tips & guides',
            'tips and guides',
            'home concept',
            'homepage concept',
            'home with photos',
            'homepage with photos',
        ];

        $filtered = array_values(array_filter($rows, static function(array $row) use ($retiredUrls, $retiredLabels): bool {
            $url = '/' . trim(strtolower((string)($row['url'] ?? '')), '/');
            $label = trim(strtolower((string)($row['label'] ?? '')));
            return !in_array($url, $retiredUrls, true) && !in_array($label, $retiredLabels, true);
        }));

        if (count($filtered) === count($rows)) {
            return;
        }

        $set->setFieldValue('footerLinks', $filtered);
        if (!Craft::$app->getElements()->saveElement($set)) {
            $errors = implode('; ', $set->getErrorSummary(true));
            throw new RuntimeException("Unable to clean the universal footer: $errors");
        }
    }

    private function rebuildPages(): void
    {
        $templates = ['index', 'contact.html', 'u-pull-parts.html'];
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
                throw new RuntimeException("Unable to rebuild {$page->title}: $errors");
            }
        }
    }
}
