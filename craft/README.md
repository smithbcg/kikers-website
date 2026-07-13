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
- Existing `.html` routes remain available while canonical extension-free Craft routes are introduced.
- Page SEO titles, descriptions, headings, and summaries are seeded as editable Craft fields while each entry continues to use its approved Twig migration template.

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
