# Apollo SEO — feature roadmap (native, no mega-merge)

This plugin ships core SEO (meta, schema, sitemap, robots, OG/Twitter, Apollo CPTs). Bigger SEO suites (Yoast, Rank Math, SEOPress, etc.) are **not** merged in-tree; features below are **Apollo-native** milestones with manageable scope.

## Phase 2 — high priority (ship in order)

1. **Redirect manager** — Store rules in options or custom table; `template_redirect` for 301/302; simple prefix/wildcard patterns; avoid regex DoS; capability `manage_options`; log last hit optionally.
2. **Bulk SEO editor** — Admin screen or posts list bulk action: edit SEO title/description for selected posts; respect `APOLLO_SEO_POST_META`; nonce + capability checks.
3. **Content analysis panel (heuristic)** — Length checks for title/description, keyword presence hint, image alt reminder; scores as guidance only (no proprietary “traffic light” clones).
4. **Internal linking suggestions** — Simple: suggest related posts/CPTs by taxonomy or title similarity; output in metabox as optional links.

## Phase 3 — optional

5. **AI assist (BYOK)** — Optional API key in settings; generate title/description suggestions from post content; rate limit server-side; opt-in per site.
6. **Compatibility bridges** — Detect another SEO plugin active; show admin notice; optional “delegate” toggles to avoid duplicate meta (design only when needed).

## Out of scope for this roadmap

- Single-plugin merge of multiple upstream SEO codebases (maintenance and conflict cost).
- Copying commercial “premium” features not present in OSS sources.

## References

- Settings defaults: `src/Settings.php`
- Admin UI: `src/Admin.php`, `assets/css/admin.css`
