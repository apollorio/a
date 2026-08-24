# Apollo SSOT Compliance Audit — cult sweep + user-display model

**Date:** 2026-07-19  **Scope:** all files under `plugins/` (recursive)

---

## ✅ Compliant with SSOT

### cult → cena (live code)
**Zero** real `cult` brand tokens remain in live code. Only survivors are the three
**intentional** deprecation markers in `apollo-registry.json` (`"cult": "USE 'cena'"`,
FORBIDDEN list, quick-map). Stray `apollo-registry.json.bak` removed.

### Role model — roles = capabilities/permissions only
Backend WP slugs untouched; frontend relabels consistent everywhere:

| Backend | Frontend | Level | Verified in |
|---|---|---|---|
| administrator | apollo | 10 | roles.php, functions.php, ActivationHandler, RoleAccess |
| editor | MOD | 7 | ″ |
| author | cena+ | 5 | ″ |
| contributor | cena | 3 | ″ |
| subscriber | clubber | 1 | ″ |

Roles drive only capabilities (`ActivationHandler::setup_roles`) and gestor access
levels (`RoleAccess`). **Badges are NOT derived from role.** ✓

### User-display SSOT — canonical function exists and is correct
`apollo-users/includes/functions.php:649` → `apollo_get_user_display_data()` returns
exactly the spec order: **display_name → badge → membership → nucleos (filtered to
`type === 'nucleo'`) → @handle → time-ago (member_for)**. ✓

### Membership-badge gating
Badge SSOT = `_apollo_membership` (array) via
`apollo_membership_get_user_badge()` / `apollo_membership_render_badge()`
(`apollo-membership/includes/functions.php:174,305`). Driven by membership, not role. ✓

### Group model
`nucleo` → privacy `private`; `comuna` → privacy `public`
(`apollo-groups/includes/functions.php:34-38`). Matches spec. ✓

### Naming rules
Targeted scan for forbidden identifiers (venue/bookmark/like/comment/`/user/`) in
`register_*` calls → **no violations found.**

---

## ✗ Deviations found → ✅ FIXED (2026-07-19)

### A. Feed byline bypassed the canonical SSOT — **FIXED**
`apollo-social/src/Components/PostRenderer.php` (`render_header`, lines ~69–90) does
NOT use `apollo_get_user_display_data()`. Instead it:

1. **Wrong meta key** — reads `apollo_membership_badge` (line 69) instead of the SSOT
   `_apollo_membership` via `apollo_membership_get_user_badge()`.
2. **Hardcoded to `apollo` only** (line 78: `if ('apollo' === $membership_badge)`) —
   users with `prod`, `dj`, `host`, `govern`, `business-pers` memberships get **no badge**.
3. **Núcleo tag missing** — the byline never renders the user's núcleo tag below the name.
4. **No time-ago** in the header.

**Fixed:** `render_header` rebuilt on `apollo_get_user_display_data()` — badge now
sourced from `_apollo_membership` (via `badge.ri_icon` / `apollo_membership_get_ri_icon()`)
for **all** membership types, núcleo tags rendered below the name (`type==='nucleo'`),
and post-age time-ago added. Byline order now matches SSOT.

### B. Núcleo creation not gated to admins — **FIXED**
`apollo-groups/src/Plugin.php:485` (`rest_create_group`) and
`apollo-groups/includes/functions.php:15` (`apollo_create_group`) accept
`type = 'nucleo'` from any caller with **no capability check**. SSOT says núcleo
(private/work group) should be **admin-created**. Member-adding/invite IS gated
(`functions.php:611`, `:474`).

**Fixed:** admin gate (`manage_options`) added at three layers — `rest_create_group`
(403 for non-admin núcleo), `apollo_create_group` (returns false, with
`apollo/groups/allow_system_nucleo` filter escape hatch for seeders/imports), and the
frontend create form (núcleo option hidden from non-admins).

### C. Minor — registry icon title
`apollo-registry.json` `user_industry` icon title still reads `"Cena::rio"`
(membership marker, not a role badge). Cosmetic; change on request.

---

## Summary
The SSOT is **correctly implemented at the canonical layer** (apollo-users,
apollo-membership, apollo-core roles, apollo-groups). The two drifts found — the
apollo-social feed byline (A) and the ungated núcleo creation (B) — are now **fixed**.
Only cosmetic item C (registry icon title) remains, pending your say-so.

## Files changed by fixes
- `apollo-social/src/Components/PostRenderer.php` — byline rebuilt on canonical SSOT.
- `apollo-groups/src/Plugin.php` — REST núcleo admin gate.
- `apollo-groups/includes/functions.php` — core núcleo admin gate + filter hook.
- `apollo-groups/src/FrontendForm.php` — núcleo option hidden from non-admins.
