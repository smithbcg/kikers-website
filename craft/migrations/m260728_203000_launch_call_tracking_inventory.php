<?php

declare(strict_types=1);

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\elements\Entry;
use RuntimeException;

/**
 * Applies the launch call-first hierarchy, live inventory integrations, and
 * notification updates to the editor-managed page builder.
 */
class m260728_203000_launch_call_tracking_inventory extends Migration
{
    public function safeUp(): bool
    {
        require_once __DIR__ . '/m260713_151000_seed_page_builder.php';

        $this->setCanonicalPhoneNumber();
        $this->removeRetiredPages();
        $this->rebuildLaunchPages();

        return true;
    }

    public function safeDown(): bool
    {
        echo "Launch content and retired pages cannot be safely restored.\n";
        return false;
    }

    private function setCanonicalPhoneNumber(): void
    {
        $set = Craft::$app->getGlobals()->getSetByHandle('siteSettings')
            ?? throw new RuntimeException('The Site Settings global set is unavailable.');
        $set->setFieldValue('phoneNumber', '850-435-7630');
        if (!Craft::$app->getElements()->saveElement($set)) {
            $errors = implode('; ', $set->getErrorSummary(true));
            throw new RuntimeException("Unable to set the canonical phone number: $errors");
        }
    }

    private function removeRetiredPages(): void
    {
        $entries = Entry::find()
            ->section('pages')
            ->slug([
                'home-funnel',
                'contact-visit',
                'icon-comparison',
                'kikers-home',
                'kikers-home-v2',
                'kikers-home-with-photos',
            ])
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

    private function rebuildLaunchPages(): void
    {
        $templates = [
            'index',
            'about.html',
            'blog.html',
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
                throw new RuntimeException("Unable to rebuild {$page->title}: $errors");
            }
        }
    }
}
