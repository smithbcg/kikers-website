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

Create Hunter's named Craft account only after receiving his preferred email address and username. Give the account the permissions needed to edit pages, assets, icons, navigation/footer settings, and inquiries. Have Hunter set his own password, confirm access, and then remove or disable any temporary shared administrator account.

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
3. Hunter's preferred email address and Craft username.

After cutover, verify `/home`, `/sell-your-vehicle`, `/u-pull-parts`, `/full-service-parts`, `/contact`, the four location pages, robots/sitemap behavior, and a representative 404.
