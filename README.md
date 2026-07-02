# Kiker's U-Pull-It — Website (full set)

Static HTML site. No build step, no framework. Fully responsive (desktop / laptop / tablet / mobile).

## Page groups
**Live site** (link the shared `kikers.css` + `kikers.js`; these are the primary launch pages):
index, sell-your-vehicle, u-pull-parts, full-service-parts, about, contact, blog, cars-for-sale,
cars-for-sale-vehicle, we-buy-cars-near-pensacola, sell-your-car-{pensacola,pace,milton,cantonment},
thank-you, 404, privacy.

**Variants / earlier concepts** (self-contained — each has its own inline CSS, for reference &
comparison; linked in the footer under Build Versions, not in the main nav): `Kikers-Home.html` (first home), `Kikers-Home-with-Photos.html`
(photo-rich home), `Kikers-Home-v2.html` (style-guide-vocabulary home), `home-funnel.html` &
`home-funnel-2.html` (funnel-style home concepts), `Sell-Your-Vehicle-v2.html`,
`Contact-Visit.html`, `Icon-Comparison.html` (icon-set picker), and `docs/component-kit.html`.
`index.html` is the active home page.

## Assets
- `kikers.css` — design system + all components + responsive layer (single source of truth for the live pages).
- `kikers.js` — shared behavior (sticky nav, mobile drawer, hours highlight, redirects/forms, toast).
- `sitemap.xml`, `robots.txt`, `docs/component-kit.html`.

## Responsive
Breakpoints (bottom of `kikers.css`, and inlined into each variant): 1024 (4-up → 2-up),
768 (multi-col → single, sticky buy-box static, stacked CTAs), 560 (single col, full-width CTAs),
380 (small phone). Global guards prevent horizontal scroll and overflow. Verify in Chrome DevTools
device mode; tell me any page+width that looks off.

## Deploy
Put the folder in git; deploy to any static host (Netlify/Vercel/Cloudflare Pages/GitHub Pages/S3/
Apache/Nginx). `index.html` is home. Enable pretty-URL rewrites (`/sell-your-vehicle/` → .html) or
switch canonicals/sitemap to `.html` paths.

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
- Inventory cards are sample data → wire real inventory from Crush/Checkmate.

## Editing
Live pages: edit `kikers.css` (global look) / `kikers.js` (behavior) / the `.html` (content).
Variants: self-contained — edit the CSS inside that file's `<style>`.
