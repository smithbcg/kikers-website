<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/** Rebuilds the editor-managed page sections for the 2026 visual design pass. */
class m260715_220000_apply_design_pass extends Migration
{
    public function safeUp(): bool
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';

        $templates = [
            'index',
            'about.html',
            'sell-your-vehicle.html',
            'full-service-parts.html',
            'u-pull-parts.html',
            'cars-for-sale.html',
            'contact.html',
            'blog.html',
            'we-buy-cars-near-pensacola.html',
            'sell-your-car-pensacola.html',
            'sell-your-car-pace.html',
            'sell-your-car-milton.html',
            'sell-your-car-cantonment.html',
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
        echo "The visual design pass cannot be safely reverted after editor content is rebuilt.\n";
        return false;
    }
}
