# Kiker's U-Pull-It — Website

Static HTML site for Kiker's U-Pull-It (Pensacola, FL). No build step, no framework — just
HTML, one shared CSS file, and one shared JS file. Drop it on any static host.

## Quick start / deploy
1. Put this folder in your git repo.
2. Deploy the folder to any static host (Netlify, Vercel, Cloudflare Pages, GitHub Pages,
   S3/CloudFront, Apache, Nginx). `index.html` is the home page.
3. (Recommended) Configure "pretty URLs" so `/sell-your-vehicle/` serves `sell-your-vehicle.html`,
   etc. The `<link rel="canonical">` tags and sitemap use the pretty form. If you can't rewrite,
   change the canonicals/sitemap to the `.html` paths instead.

## What's here
- `*.html` — the pages (see list below). Each links the two shared assets relatively.
- `kikers.css` — **the design system + all components (single source of truth).** Change tokens,
  colors, type, and component styles here; do not hand-edit styles inside a page.
- `kikers.js` — shared behavior (sticky nav, mobile drawer, today's-hours highlight, form
  routing, toast).
- `sitemap.xml`, `robots.txt` — currently point at the GitHub Pages launch URL.
- `docs/component-kit.html` — a visual reference of the design-system components.

### Pages
Core: index, sell-your-vehicle, u-pull-parts, full-service-parts, about, contact, blog,
thank-you, 404, privacy.
Cars for sale: cars-for-sale (listing), cars-for-sale-vehicle (vehicle-detail template).
Local landing pages: we-buy-cars-near-pensacola, sell-your-car-pensacola, -pace, -milton, -cantonment.
Alternate concept (not the live home page): home-funnel-2.html.

## Launch status
Published-ready values currently in the site:
- Launch URL: `https://smithbcg.github.io/kikers-website/`
- Address: `3010 W. Fairfield Drive, Pensacola, FL 32505`
- Phone: `850-435-7630`
- Email: `sales@kikersautoparts.com`
- Hours: Monday-Friday, 9 AM-4:30 PM; Saturday, 8 AM-2 PM; Sunday closed.
- Facebook: `https://www.facebook.com/kikersupullit/`

Before moving to a custom domain, replace the GitHub Pages URL in canonical tags, JSON-LD,
`sitemap.xml`, and `robots.txt`.

## Remaining content placeholders
Search the files for each token and replace:
- Photos — every dashed box labeled `📷 PHOTO — …` is a placeholder describing the exact shot
  needed (hero yard photo, tow/handoff, Hunter portrait, team, vehicles, etc.). Drop in real
  images and remove the `.ph` placeholder wrappers.
- Inventory/listing samples — replace sample vehicles and "coming soon" blog/article cards with
  live inventory and articles when available.

## Forms
Offer forms redirect to the existing Kiker's `sell-a-vehicle` flow. Message/parts forms open a
pre-filled email to `sales@kikersautoparts.com`. For a tighter launch, replace the `onsubmit`
handlers with a real form backend, CRM/Crush, email, or WhatConverts POST and redirect to
`thank-you.html` on success.

## Fonts
Loaded from Google Fonts (Saira / Inter / JetBrains Mono) via a `<link>` in each page's head and an
`@import` in `kikers.css`. To remove the CDN dependency, self-host the `.woff2` files and replace
the link/@import with `@font-face` rules.

## SEO / structured data
Every page includes JSON-LD (LocalBusiness/AutoDealer, BreadcrumbList, and FAQPage/Car where
relevant). Keep the visible FAQ copy identical to the FAQ JSON-LD. Update the domain throughout.

## Important content notes
- **Cars for Sale** is intentionally gated "coming soon." Don't launch live vehicle sales until the
  Florida dealer license is active and the FTC Buyers Guide / as-is disclosure process is in place.
  Have the as-is sale process reviewed by counsel. Listings/prices shown are samples.
- **Florida title/legal content** (on sell + local + cars pages) is summarized for guidance — verify
  current forms/rules with FLHSMV, and confirm the salvage-dealer license is current before
  publishing claims that rely on it.
- **Inventory** cards (new arrivals, listings) are sample data; wire real inventory from
  Crush/Checkmate.

## Editing
- Global look (colors, fonts, spacing, components): edit `kikers.css` only.
- Shared behavior: edit `kikers.js`.
- Page content/structure: edit the individual `.html` file.
