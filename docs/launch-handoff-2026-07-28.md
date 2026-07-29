# Kiker's Craft CMS Launch Handoff

## Completed in Craft

- WhatConverts profile `165238` loads once in the shared front-end `<head>` on every normal site template.
- The server-rendered business number remains `850-435-7630`. WhatConverts swaps that number and its `tel:` links for attributed visitors.
- Site-wide header and mobile actions are call-first.
- Cash-offer forms remain available as a secondary option; duplicate and hero-dominant forms were removed.
- All Craft form notifications are addressed to `cars@kikersautoparts.com`.
- Notification emails include the normalized contact details and the complete submitted answer payload.
- The full-service inventory opens in a new tab at:
  `http://search3904.used-auto-parts.biz/inventory/retailF.htm`
- The U-Pull page uses the live AutoRecycler yard search with a full-screen fallback.
- Retired pages return 404: Home Funnel, Contact Visit, Icon Comparison, Homepage Concept, Homepage Concept V2, and Homepage with Photos.

## Legacy Webflow tracking verification

The current Webflow source places the supplied WhatConverts bootstrap and profile `165238` loader together in the document `<head>`. Representative checks of the live homepage, Sell a Vehicle page, and Inventory page each returned exactly one loader. The visible/source phone remains `850-435-7630`.

Craft mirrors that placement through the shared `_partials/seo.twig` include. Representative checks of the Craft homepage, Sell Your Vehicle page, and U-Pull Parts page also returned exactly one loader each. Do not paste the script into individual Craft entries or page-builder sections; that would create duplicate sessions and attribution errors.

## Production mail requirement

The repository identifies `cars@kikersautoparts.com` as both notification recipient and sender. Before DNS cutover, configure and test a production mail transport that is authorized to send for `kikersautoparts.com` (SMTP or a transactional mail provider). Do not assume a production server's local Sendmail transport will deliver reliably.

The production `.env` needs real values for `SMTP_HOST`, `SMTP_PORT`, `SMTP_USE_AUTHENTICATION`, `SMTP_USERNAME`, and `SMTP_PASSWORD`. The repository intentionally does not contain those credentials.

Required smoke test:

1. Submit the public contact form.
2. Confirm the inquiry appears in Craft under Inquiries.
3. Confirm the email arrives at `cars@kikersautoparts.com`.
4. Reply to the inquiry only after confirming the submitted sender information.

## WhatConverts production test

1. Open `https://www.kikersautoparts.com/?wc_clear=true` in a private window.
2. Confirm visible instances of `850-435-7630` and their `tel:` links swap to a tracking-pool number.
3. Place one test call.
4. Confirm the call appears in WhatConverts with the expected source.

Never hardcode `850-257-7292`; it is a dynamic pool number. The permanent number in Craft must remain `850-435-7630`.

## Owner access requirement

The project is configured as Craft Solo and currently has one administrator: the local placeholder `admin@kikers.local`. Solo allows only one user.

Choose one of these handoff paths:

1. Stay on Solo and transfer the existing sole administrator to Hunter after receiving his preferred email address and username. Have Hunter set his own password, confirm access to pages, assets, icons, navigation/footer settings, and inquiries, and invalidate the temporary local credentials.
2. Upgrade to Craft Team/Pro, then create Hunter's named owner account and retain a separate named technical administrator.

Do not create or guess an owner identity before Hunter's real account details are supplied.

## U-Pull inventory vendor action

Kiker's AutoRecycler yard UUID is `2c06fa04-9b26-4b8e-b0e8-ac36d7a1b829`. The public endpoint reports 379 active records, but the feed appears stale:

- newest placement date found: June 19, 2026;
- zero additions in the most recent 30 days;
- 89 records have no image;
- some make/model pairs are malformed;
- unfiltered public browsing stops at approximately 100 records, though filtered search queries all 379.

Ask AutoRecycler to:

1. verify/restart the `yms_windows_sync` connector for the yard UUID;
2. run a full active/removal reconciliation;
3. correct make/model normalization;
4. investigate the missing vehicle images;
5. raise or remove the anonymous browse cap;
6. whitelist the production/staging hostname if it differs from `kikersautoparts.com`.

The Craft page has a full-screen inventory fallback because the provider does not whitelist `kikers-craft.ddev.site`.

## Domain cutover checklist

Do not ask Car-Part to change DNS until all items below are known:

- production host and final A/AAAA or CNAME targets;
- `PRIMARY_SITE_URL=https://www.kikersautoparts.com`;
- production database imported and Craft migrations applied;
- asset volume copied and writable;
- production mail transport verified;
- HTTPS certificate ready for both apex and `www`;
- canonical redirect selected between apex and `www`;
- WhatConverts call test completed;
- AutoRecycler embed tested on the production hostname;
- current DNS records and TTL exported before changes.

Three external inputs are still required before the cutover can be executed:

1. the production hosting provider or server login, including the exact A/AAAA and/or CNAME targets;
2. production SMTP credentials authorized for `kikersautoparts.com`;
3. Hunter's preferred email address and Craft username, plus a decision to transfer the sole Solo account or upgrade for multiple users.

After cutover, verify `/home`, `/sell-your-vehicle`, `/u-pull-parts`, `/full-service-parts`, `/contact`, the four location pages, robots/sitemap behavior, and a representative 404.

Run the repository smoke test once DNS resolves to production:

```bash
./scripts/production-smoke-test.sh https://www.kikersautoparts.com
```

## Current external state

The domain is still pointed at Webflow and its authoritative DNS is managed by Car-Part/PhoneWare:

- apex `A`: `198.202.211.1` (TTL 86400);
- `www` `CNAME`: `cdn.webflow.com.` (TTL 86400);
- authoritative SOA: `ns0.phoneware.com`;
- authoritative nameservers: `ns1.phoneware.com`, `ns2.phoneware.com`, and `ns3` through `ns8.car-part.com`;
- MX: `defenderMX00.Car-Part.com`, `defenderMX01.Car-Part.com`, and `defenderMX02.Car-Part.com`;
- SPF: `v=spf1 mx a ip4:69.24.30.0/24 ip4:69.24.29.0/24 ?all`;
- no DMARC TXT record was returned at `_dmarc.kikersautoparts.com`.

The 86400-second TTL means some visitors can remain on the old Webflow destination for up to 24 hours after a DNS change. Ask Car-Part to lower the apex and `www` TTLs immediately; lowering a TTL only becomes fully effective after the old 24-hour TTL has expired.

GitHub Pages is enabled from `main`, but it is only a historical static preview. It cannot run Craft's PHP/MySQL application. The repository has no production deployment workflow, environment variables, secrets, or Craft-compatible hosting target connected.

If no Craft-compatible server already exists, the fastest supported launch path is a Craft Cloud trial. It requires a Craft Console organization with a payment method and permission to connect this GitHub repository. Do not run `craft setup/cloud` or alter the codebase for Craft Cloud until the owner approves that hosting choice.
