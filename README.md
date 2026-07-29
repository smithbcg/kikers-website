# Kiker's U-Pull-It — Craft CMS Website

The production application is the Craft CMS 5 project in `craft/`. The root-level static HTML files remain only as historical visual references and must not be deployed as the live website.

## Craft CMS migration

- Local site: `http://kikers-craft.ddev.site`
- Control panel: `http://kikers-craft.ddev.site/admin`
- Public document root for production: `craft/web`
- Local runtime: OrbStack + DDEV, PHP 8.3, MySQL 8.0
- Content: editor-managed pages, assets, universal navigation/footer settings, and inquiries

See `craft/README.md` for setup, editing, and deployment instructions.

## Site structure

The live routes are Craft entries rendered through the shared page builder. Earlier static concepts remain in the repository only as development history. They are not navigation items, sitemap entries, or production routes.

Public assets live under `craft/web/assets`; uploaded Craft assets use the Site Assets volume at that same public root. Shared front-end behavior and styles live in `craft/web/kikers.js` and `craft/web/kikers.css`.

## Production

- PHP 8.2+ and MySQL 8.0.17+ are required.
- The public web root must be `craft/web`.
- The project is currently configured for Craft Solo, which allows one control-panel user.
- Create `craft/.env` from `craft/.env.example.production`.
- Configure production SMTP before accepting inquiries.
- Deploy with `composer install --no-dev --optimize-autoloader` and `php craft up --interactive=0`.
- Never point the domain at the repository root or deploy this project to a static-only host.

## Launch notes
- Business address: `3010 W. Fairfield Drive, Pensacola, FL 32505`.
- Hours: Mon-Fri, 9 AM-4:30 PM; Sat, 8 AM-2 PM; Sunday closed.
- Google rating copy: 4.2 based on 687 Google reviews.
- Social `href="#"` values can be replaced when the official profiles are ready.
- Some variant pages still use stylized map/photo placeholders because they are comparison builds.
- Offer forms redirect to the Kiker's sell-a-vehicle flow; message forms use the configured email link.

## Guardrails
- Cars for Sale is gated "coming soon" — don't launch live sales until the FL dealer license + FTC
  Buyers Guide/as-is process are in place (legal review). Listings are samples.
- Florida legal copy is summarized — verify with FLHSMV; confirm the salvage-dealer license.
- The U-Pull inventory is supplied by AutoRecycler/Ario; its upstream YMS sync must be maintained by the inventory provider.

## Editing
Routine content, images, icons, navigation, and footer settings are edited in Craft. Template and stylesheet changes should be deployed through Git.
