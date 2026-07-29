<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

class m260713_141500_seed_page_content extends Migration
{
    public function safeUp(): bool
    {
        $seedPath = Craft::getAlias('@config/page-seeds.json');
        $seeds = json_decode((string)file_get_contents($seedPath), true, flags: JSON_THROW_ON_ERROR);

        $entries = Entry::find()
            ->section('pages')
            ->status(null)
            ->site('*')
            ->unique()
            ->all();

        foreach ($entries as $entry) {
            $template = (string)$entry->getFieldValue('legacyTemplate');
            if (!isset($seeds[$template])) {
                continue;
            }

            $entry->setFieldValues($seeds[$template]);
            if (!Craft::$app->getElements()->saveElement($entry)) {
                throw new RuntimeException("Unable to seed page content for {$entry->title}.");
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260713_141500_seed_page_content cannot be reverted.\n";
        return false;
    }
}
