<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/** Rebuilds the final peripheral pages found in the owner-copy accuracy sweep. */
class m260715_191500_finish_owner_copy_sweep extends Migration
{
    public function safeUp(): bool
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';

        $templates = [
            'Sell-Your-Vehicle-v2.html',
            'Kikers-Home.html',
            'Kikers-Home-v2.html',
            'Kikers-Home-with-Photos.html',
            'privacy.html',
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
        echo "Owner-approved copy changes cannot be safely reverted.\n";
        return false;
    }
}
