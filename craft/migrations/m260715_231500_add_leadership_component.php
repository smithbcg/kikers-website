<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/** Rebuilds the Component Kit with the interactive leadership portrait pattern. */
class m260715_231500_add_leadership_component extends Migration
{
    public function safeUp(): bool
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
            if (trim((string)$page->getFieldValue('legacyTemplate')) !== 'docs/component-kit.html') {
                continue;
            }

            [$sections, $customCss, $headHtml, $bodyScripts] = $builder->buildPage($page, $pages);
            if ($sections['sortOrder'] === []) {
                throw new RuntimeException('No editable sections were found for the Component Kit.');
            }

            $page->setFieldValues([
                'pageSections' => $sections,
                'pageCustomCss' => $customCss,
                'pageHeadHtml' => $headHtml,
                'pageBodyScripts' => $bodyScripts,
            ]);
            if (!Craft::$app->getElements()->saveElement($page)) {
                $errors = implode('; ', $page->getErrorSummary(true));
                throw new RuntimeException("Unable to update the Component Kit: $errors");
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "The Component Kit update cannot be safely reverted after editor content is rebuilt.\n";
        return false;
    }
}
