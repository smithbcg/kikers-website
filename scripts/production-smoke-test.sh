#!/usr/bin/env bash

set -euo pipefail

kikers_base_url="${1:-https://www.kikersautoparts.com}"
kikers_base_url="${kikers_base_url%/}"
kikers_tmp_dir="$(mktemp -d "${TMPDIR:-/tmp}/kikers-smoke.XXXXXX")"
trap 'rm -rf "$kikers_tmp_dir"' EXIT

kikers_live_routes=(
  /
  /home
  /sell-your-vehicle
  /u-pull-parts
  /full-service-parts
  /we-buy-cars-near-pensacola
  /sell-your-car-pensacola
  /sell-your-car-pace
  /sell-your-car-milton
  /sell-your-car-cantonment
  /about
  /contact
  /blog
  /cars-for-sale
  /privacy
  /home-funnel-2
)

kikers_retired_routes=(
  /home-funnel
  /contact-visit
  /icon-comparison
  /kikers-home
  /kikers-home-v2
  /kikers-home-with-photos
)

printf 'Checking live routes at %s\n' "$kikers_base_url"
for kikers_route in "${kikers_live_routes[@]}"; do
  kikers_status="$(curl --silent --show-error --location --output /dev/null --write-out '%{http_code}' "${kikers_base_url}${kikers_route}")"
  printf '  %s %s\n' "$kikers_status" "$kikers_route"
  if [[ "$kikers_status" != "200" ]]; then
    printf 'Expected HTTP 200 for %s\n' "$kikers_route" >&2
    exit 1
  fi
done

printf 'Checking retired routes\n'
for kikers_route in "${kikers_retired_routes[@]}"; do
  kikers_status="$(curl --silent --show-error --output /dev/null --write-out '%{http_code}' "${kikers_base_url}${kikers_route}")"
  printf '  %s %s\n' "$kikers_status" "$kikers_route"
  if [[ "$kikers_status" != "404" ]]; then
    printf 'Expected HTTP 404 for %s\n' "$kikers_route" >&2
    exit 1
  fi
done

curl --fail --silent --show-error --location "${kikers_base_url}/" --output "$kikers_tmp_dir/home.html"
curl --fail --silent --show-error --location "${kikers_base_url}/contact" --output "$kikers_tmp_dir/contact.html"
curl --fail --silent --show-error --location "${kikers_base_url}/u-pull-parts" --output "$kikers_tmp_dir/u-pull.html"
curl --fail --silent --show-error --location "${kikers_base_url}/full-service-parts" --output "$kikers_tmp_dir/full-service.html"
curl --fail --silent --show-error --location "${kikers_base_url}/robots.txt" --output "$kikers_tmp_dir/robots.txt"
curl --fail --silent --show-error --location "${kikers_base_url}/sitemap.xml" --output "$kikers_tmp_dir/sitemap.xml"

kikers_tracking_count="$(grep -o 's\.ksrndkehqnwntyxlhgto\.com/165238\.js' "$kikers_tmp_dir/home.html" | wc -l | tr -d ' ')"
[[ "$kikers_tracking_count" == "1" ]] || {
  printf 'Expected exactly one WhatConverts loader; found %s\n' "$kikers_tracking_count" >&2
  exit 1
}

grep -q 'tel:+\?18504357630\|tel:8504357630' "$kikers_tmp_dir/home.html"
grep -q 'assets/maps/kikers-yard-map.svg' "$kikers_tmp_dir/contact.html"
grep -q 'embed=1.*yard=2c06fa04-9b26-4b8e-b0e8-ac36d7a1b829' "$kikers_tmp_dir/u-pull.html"
grep -q 'search3904.used-auto-parts.biz/inventory/retailF.htm' "$kikers_tmp_dir/full-service.html"
grep -q 'https://www.kikersautoparts.com/sitemap.xml' "$kikers_tmp_dir/robots.txt"
grep -q '<loc>https://www.kikersautoparts.com/</loc>' "$kikers_tmp_dir/sitemap.xml"

if command -v xmllint >/dev/null 2>&1; then
  xmllint --noout "$kikers_tmp_dir/sitemap.xml"
fi

printf '\nAutomated production smoke test passed.\n'
printf 'Manual launch checks still required:\n'
printf '  1. Submit the contact form and verify the Craft inquiry and email at cars@kikersautoparts.com.\n'
printf '  2. Confirm WhatConverts swaps 850-435-7630 in a private browser and records a test call.\n'
printf '  3. Exercise AutoRecycler search inside the production iframe and its full-screen fallback.\n'
printf '  4. Confirm the apex domain redirects to the canonical www hostname.\n'
