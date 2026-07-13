# Kiker's Craft CMS

This directory contains the Craft CMS 5 conversion of the Kiker's website. DDEV uses OrbStack's Docker engine for PHP 8.3, MySQL 8.0, Nginx, and local email capture.

## Start locally

From this directory:

```bash
ddev start
ddev composer install
ddev craft up --interactive=0
```

Open:

- Site: `http://kikers-craft.ddev.site`
- Control panel: `http://kikers-craft.ddev.site/admin`
- Mailpit: `http://kikers-craft.ddev.site:8025`

The local administrator username is `admin`. The development password is intentionally not committed. Reset it at any time with:

```bash
ddev craft users/set-password admin
```

Stop the environment with `ddev stop`.

## Content model

- **Pages** contains one entry for every migrated static page and concept route.
- **Inquiries** stores vehicle offers and general website requests submitted from public forms.
- **Globals > Site Settings** is the source for the business name, phone, address, hours, Google rating, review count, and directions URL.
- **Assets > Site Assets** contains the logo, hero image, and future page uploads.
- Existing `.html` routes remain available alongside canonical extension-free Craft routes.
- Page SEO fields and the complete front-end section tree are stored in Craft. The approved Twig templates remain only as a migration fallback.

## Edit a page

1. Open **Entries > Pages** and choose a page.
2. Use **Content** for the title, SEO metadata, page heading, and summary.
3. Open **Page Builder** to reorder, disable, duplicate, or edit complete page sections.
4. Within a section, edit its type, color theme, width, spacing, container, background image, overlay, anchor, and **Section Content** items.
5. Open a Section Content item to edit its text, button or link destination, form label, or managed image.
6. Use **Preview**, then **Save** when the page is ready.

### Visual editing

The native **Preview** window includes a visual editing layer for Page Builder pages:

1. Open an entry under **Entries > Pages** and choose **Preview**.
2. Hover the rendered page to see editable text, buttons, links, images, and section boundaries.
3. Click an outlined item to open its exact nested Craft entry in a slideout. When a button has both editable text and a destination, choose the value you want from the small menu.
4. Use **Edit section** for the section theme, width, spacing, container, background image, overlay, anchor, content items, or enabled status.
5. Use the **Page Builder** tab when you need to reorder, duplicate, or disable complete sections.
6. Save the slideout, review the refreshed preview, then save the page entry.

The editing controls are added only to a valid signed Craft preview. They are never included in public page responses, and all edits continue through Craft's normal permissions, drafts, revisions, and nested-entry workflow.

The **Advanced** section tab and the page-level CSS/head/script fields preserve the approved layouts. They are available for developer-level changes; routine content and image edits should use the section controls and content items instead. The Content Key on nested items is an internal layout reference and should not be changed.

Schema changes are stored in `config/project/`. Initial content is seeded by the timestamped files in `migrations/`.

Public forms post through the `kikers` module, retain a normalized copy of every submitted field, and redirect to `/thank-you`. Set `KIKERS_NOTIFICATION_EMAIL` to choose the notification recipient. DDEV captures these messages in Mailpit; production must also be configured with a working Craft mail transport.

## Production requirements

Craft requires PHP and a database, so the CMS build cannot run on GitHub Pages. Production hosting must provide PHP 8.2 or newer, MySQL 8.0.17 or newer, Composer 2, and a web root pointed to `craft/web`.

Create the production `.env` from `.env.example.production`, provide real database credentials and `PRIMARY_SITE_URL`, then deploy with:

```bash
composer install --no-dev --optimize-autoloader
php craft up --interactive=0
```

Keep `.env` out of Git. Set `CRAFT_ALLOW_ADMIN_CHANGES=false` in production and make database backups before deployments.
