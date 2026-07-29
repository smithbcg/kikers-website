<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/**
 * Refreshes the final web-sized homepage inventory thumbnail.
 */
class m260728_224000_refresh_home_inventory_thumbnail extends Migration
{
    public function safeUp(): bool
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';

        $page = Entry::find()
            ->section('pages')
            ->slug('home')
            ->status(null)
            ->drafts(false)
            ->revisions(false)
            ->one()
            ?? throw new RuntimeException('The homepage is unavailable.');
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
        echo "The homepage refresh cannot be safely reverted.\n";
        return false;
    }
}
