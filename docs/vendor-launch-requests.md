# Vendor Launch Requests

## Car-Part / domain administrator

**Subject:** DNS cutover request for KikersAutoParts.com

Hello,

We are preparing to launch Kiker's replacement website on Craft CMS. Please do not change DNS until we send the final production IP/CNAME values and confirm the launch window.

When we provide those values, we will need:

- the apex `kikersautoparts.com` record pointed to `[PRODUCTION A/AAAA TARGET]`;
- the `www.kikersautoparts.com` record pointed to `[PRODUCTION CNAME TARGET]`;
- all existing mail-related MX, SPF, DKIM, and DMARC records preserved;
- the current full DNS zone exported before the change;
- TTL lowered in advance if your policy allows it.

The existing full-service inventory remains hosted at:
`http://search3904.used-auto-parts.biz/inventory/retailF.htm`

Please confirm who will make the DNS change and the expected turnaround once the final target values are supplied.

Thank you.

## AutoRecycler / Ario inventory support

**Subject:** Kiker's U-Pull-It public inventory sync and browse issue

Hello,

Please investigate the AutoRecycler public inventory for Kiker's U-Pull-It.

- Yard UUID: `2c06fa04-9b26-4b8e-b0e8-ac36d7a1b829`
- Organization UUID: `05580e64-16e1-4e09-ab40-192e4bc82ef6`
- Public yard slug: `kikers-u-pull-it`
- Configured source: `yms_windows_sync`

The public endpoint reports 379 active vehicles, but:

- the newest placement date returned is June 19, 2026;
- no vehicles were added in the most recent 30 days;
- 89 records have no image URL;
- some make/model values are malformed;
- unfiltered public browsing stops at approximately 100 vehicles;
- the default collections layout exposes only a small subset of inventory.

Please:

1. verify or restart the Windows YMS connector;
2. run a full active/removal reconciliation;
3. correct make/model mapping;
4. investigate the missing images;
5. raise or remove the anonymous public browse cap;
6. confirm that `kikersautoparts.com` and `www.kikersautoparts.com` remain approved iframe origins.

The replacement Craft site uses:

`https://ario.autorecycler.io/yard/kikers-u-pull-it?embed=1&yard=2c06fa04-9b26-4b8e-b0e8-ac36d7a1b829`

Please confirm when the feed is current and the public browse limit has been addressed.

Thank you.
