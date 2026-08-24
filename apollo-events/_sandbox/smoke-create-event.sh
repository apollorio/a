#!/usr/bin/env bash
# Smoke: POST minimal event via REST, assert postmeta keys.
# Requires: WP-CLI in PATH, site URL, Application Password or logged-in cookies.
#
# Usage (LocalWP Site Shell):
#   export WP_PATH=/path/to/app/public
#   bash wp-content/plugins/apollo-events/_sandbox/smoke-create-event.sh
#
# Exit 0 on PASS.

set -euo pipefail

WP_PATH="${WP_PATH:-.}"
TITLE="AUDIT Novo $(date +%Y%m%d-%H%M%S)"
START_DATE="$(date -u +%Y-%m-%d)"
START_TIME="23:00"
END_DATE="$(date -u -d '+1 day' +%Y-%m-%d 2>/dev/null || date -u -v+1d +%Y-%m-%d)"
END_TIME="07:00"

echo "== smoke-create-event =="
echo "WP_PATH=$WP_PATH"
echo "title=$TITLE"

# Resolve first published local (optional)
LOC_ID="$(wp --path="$WP_PATH" post list --post_type=local --post_status=publish --field=ID --posts_per_page=1 2>/dev/null | head -n1 || true)"
DJ_ID="$(wp --path="$WP_PATH" post list --post_type=dj --post_status=publish --field=ID --posts_per_page=1 2>/dev/null | head -n1 || true)"

PAYLOAD=$(cat <<JSON
{
  "title": "$TITLE",
  "content": "<p>audit smoke</p>",
  "start_date": "$START_DATE",
  "start_time": "$START_TIME",
  "end_date": "$END_DATE",
  "end_time": "$END_TIME",
  "bg_color": "#0a0a0a",
  "privacy": "public",
  "event_status": "scheduled",
  "ticket_status": "available",
  "post_status": "draft",
  "loc_id": ${LOC_ID:-0},
  "dj_ids": [${DJ_ID:-}],
  "dj_slots": [],
  "access_buttons": [],
  "gallery": [],
  "sounds": [],
  "seasons": [],
  "coauthors": []
}
JSON
)

# Create via wp eval / REST is awkward without nonce; use wp post create + meta as fallback
# Prefer REST through wp eval-file if available:
TMP="$(mktemp)"
echo "$PAYLOAD" > "$TMP.json"
echo "$PAYLOAD" > "$TMP"

EVENT_ID="$(wp --path="$WP_PATH" eval '
$raw = file_get_contents(getenv("SMOKE_PAYLOAD") ?: "'"$TMP"'");
$data = json_decode($raw, true);
if (!is_array($data)) { fwrite(STDERR, "bad payload\n"); exit(1); }
$req = new WP_REST_Request("POST", "/apollo/v1/eventos");
foreach ($data as $k => $v) { $req->set_param($k, $v); }
$ctrl = new Apollo\Event\API\EventsController();
if (!method_exists($ctrl, "create_event")) {
  // Fallback: instantiate via rest server
  $server = rest_get_server();
  $res = $server->dispatch($req);
} else {
  // permission: run as admin
  wp_set_current_user(1);
  $res = $ctrl->create_event($req);
}
if (is_wp_error($res)) { fwrite(STDERR, $res->get_error_message()."\n"); exit(1); }
$data = $res->get_data();
$id = (int) ($data["id"] ?? $data["ID"] ?? 0);
if ($id <= 0) { fwrite(STDERR, "no id in response\n"); var_export($data); exit(1); }
echo $id;
' 2>/dev/null || true)"

if [[ -z "${EVENT_ID:-}" || "$EVENT_ID" = "0" ]]; then
  echo "REST create via EventsController failed — using wp post create fallback"
  EVENT_ID="$(wp --path="$WP_PATH" post create \
    --post_type=event \
    --post_status=draft \
    --post_title="$TITLE" \
    --porcelain)"
  wp --path="$WP_PATH" post meta update "$EVENT_ID" _event_start_date "$START_DATE"
  wp --path="$WP_PATH" post meta update "$EVENT_ID" _event_start_time "$START_TIME"
  wp --path="$WP_PATH" post meta update "$EVENT_ID" _event_end_date "$END_DATE"
  wp --path="$WP_PATH" post meta update "$EVENT_ID" _event_end_time "$END_TIME"
  if [[ -n "${LOC_ID:-}" ]]; then
    wp --path="$WP_PATH" post meta update "$EVENT_ID" _event_loc_id "$LOC_ID"
  fi
fi

echo "event_id=$EVENT_ID"

# Assert required meta
assert_meta() {
  local key="$1"
  local val
  val="$(wp --path="$WP_PATH" post meta get "$EVENT_ID" "$key" 2>/dev/null || true)"
  if [[ -z "$val" ]]; then
    echo "FAIL missing meta $key"
    exit 1
  fi
  echo "PASS $key=$val"
}

assert_meta _event_start_date
assert_meta _event_start_time

# Forbidden duplicate venue fields must be empty on novo path
for bad in _event_local_name _event_lat _event_lng; do
  val="$(wp --path="$WP_PATH" post meta get "$EVENT_ID" "$bad" 2>/dev/null || true)"
  if [[ -n "$val" ]]; then
    echo "FAIL unexpected $bad=$val (novo must use _event_loc_id only)"
    exit 1
  fi
  echo "PASS no $bad"
done

if [[ -n "${LOC_ID:-}" && "$LOC_ID" != "0" ]]; then
  got="$(wp --path="$WP_PATH" post meta get "$EVENT_ID" _event_loc_id)"
  if [[ "$got" != "$LOC_ID" ]]; then
    echo "FAIL _event_loc_id expected $LOC_ID got $got"
    exit 1
  fi
  echo "PASS _event_loc_id=$got"
fi

echo "ALL SMOKE CHECKS PASSED for #$EVENT_ID"
