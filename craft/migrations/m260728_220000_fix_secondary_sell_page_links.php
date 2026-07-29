<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/**
 * Replaces the remaining placeholder links on the secondary sell page with
 * real location and contact destinations.
 */
class m260728_220000_fix_secondary_sell_page_links extends Migration
{
    public function safeUp(): bool
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';

        $page = Entry::find()
            ->section('pages')
            ->slug('sell-your-vehicle-v2')
            ->status(null)
            ->drafts(false)
            ->revisions(false)
            ->one()
            ?? throw new RuntimeException('The secondary Sell Your Vehicle page is unavailable.');
        $pages = Entry::find()
            ->section('pages')
            ->status(null)
            ->drafts(false)
            ->revisions(false)
            ->orderBy(['lft' => SORT_ASC])
            ->all();
        $builder = new m260713_151000_seed_page_builder();
        [$sections, $customCss, $headHtml, $bodyScripts] = $builder->buildPage($page, $pages);

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

        return true;
    }

    public function safeDown(): bool
    {
        echo "The repaired location links cannot be safely reverted.\n";
        return false;
    }
}
