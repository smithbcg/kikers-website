<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/** Applies Hunter Kiker's approved copy changes to editable page-builder content. */
class m260715_180000_apply_owner_copy_feedback extends Migration
{
    public function safeUp(): bool
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';

        $templates = [
            'index',
            'about.html',
            'contact.html',
            'full-service-parts.html',
            'cars-for-sale.html',
            'sell-your-vehicle.html',
            'Sell-Your-Vehicle-v2.html',
            'we-buy-cars-near-pensacola.html',
            'sell-your-car-pensacola.html',
            'sell-your-car-pace.html',
            'sell-your-car-milton.html',
            'sell-your-car-cantonment.html',
            'Kikers-Home.html',
            'Kikers-Home-v2.html',
            'Kikers-Home-with-Photos.html',
            'home-funnel.html',
            'home-funnel-2.html',
        ];

        $pages = Entry::find()
            ->section('pages')
            ->status(null)
            ->drafts(false)
            ->revisions(false)
            ->orderBy(['lft' => SORT_ASC])
            ->all();

        $seedPath = Craft::getAlias('@config/page-seeds.json');
        $seeds = json_decode((string)file_get_contents($seedPath), true, flags: JSON_THROW_ON_ERROR);
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

            $values = [
                'pageSections' => $sections,
                'pageCustomCss' => $customCss,
                'pageHeadHtml' => $headHtml,
                'pageBodyScripts' => $bodyScripts,
            ];
            if (isset($seeds[$template])) {
                $values = array_merge($values, $seeds[$template]);
            }
            $page->setFieldValues($values);

            if (!Craft::$app->getElements()->saveElement($page)) {
                $errors = implode('; ', $page->getErrorSummary(true));
                throw new RuntimeException("Unable to update {$page->title}: $errors");
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "Owner-approved copy changes cannot be safely reverted.\n";
        return false;
    }
}
