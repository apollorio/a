# `_inventory/_current/`

The working set for the current release push. Everything in here was derived
from source on **2026-08-20**, not copied from the registry — where the two
disagreed, the disk won.

`_inventory/registry/` remains the chaptered SSOT for *what the ecosystem is*.
This folder is *what we are doing about it right now*, and it is meant to empty
out. When `index.html` reads 100%, archive the folder and open `_current` again
for the next push.

---

## What is here

| File | Is |
|---|---|
| **`index.html`** | The board. **37 tasks in thirteen lanes** — the five plan-002 mobile lanes first, the seven plan-001 correctness lanes after. Click one to close it — closed tasks mute; **Hide done** removes them. Progress lives in your browser; **Export** copies it as JSON so it can be handed over. |
| **`_plans/plan-002_260820.md`** | **Mobile first.** What the phone needs — the manifest and root worker that do not exist, 54 breakpoints, 30 boxes under the toolbar, 289 images with no `srcset`, and the decision about what the app actually is. The desktop is explicitly secondary this cycle. |
| **`_plans/plan-001_260820.md`** | Correctness. Every task with the file, the line, the risk, and the command that proves it done. §11 is the execution log for what was already applied. |
| **`apollo-pwa-dropin.php`** | The manifest, the root-scoped service worker and the two head tags — written, reviewed, **deliberately not wired**. Land it after the repo exists and after `A-1` unifies the two head builders. |
| **`DOCTRINE.md`** | The rules, one page, read at the top of every session. Reference it from `CLAUDE.md` in one line so an agent cannot reintroduce a follow button. |
| **`apollo-guard.mjs`** | The gate. The 12-point `$apollo_rule.audit_verificator` plus twelve doctrine rules and six mobile-first rules, executable. Every finding cites the chapter that forbids it. |
| **`apollo-worktree-bootstrap.sh`** | Run once. `.gitignore` → first commit → pre-commit hook → debt baseline → the seven worktree commands. |
| **`plugins-htaccess-DROP-IN.txt`** | Rename to `plugins/.htaccess`. Denies `.py`, `.txt`, `.log`, `.md`, scratch prefixes and tooling directories. Install it **after** rotating the credential, not instead of. |
| **`findings-260820.json`** | Raw guard output — the evidence behind every number in the plan. |
| **`harness-260820.txt`** | State of the five sandbox harnesses on the day the plan was written. |

---

## The three commands

```bash
# from D:\dev\_apollo.rio.br\plugins

node _inventory/_current/apollo-guard.mjs              # everything
node _inventory/_current/apollo-guard.mjs apollo-djs   # one plugin
node _inventory/_current/apollo-guard.mjs --harness    # all five sandbox harnesses
node _inventory/_current/apollo-guard.mjs --rule M     # the mobile-first series
```

Useful flags: `--staged` (what the pre-commit hook runs) · `--rule D02,G08` ·
`--json out.json` · `--baseline .apollo-guard-baseline.json` ·
`--write-baseline …` · `--verbose`.

---

## Order of operations

1. **`P0-1`** — rotate the FTP/SSH credential. Nothing else matters while a
   plaintext password sits in a folder that mirrors to the public web.
2. **`P0-2`** — delete the scratch and the six diagnostic endpoints, then
   install the `.htaccess`.
3. **`P0-3`** — run the bootstrap. From that moment the folder still deploys on
   save, but every save is recoverable and new breakage is refused at the gate.
   **This one is blocked from the Cowork bridge** — the bridge cannot `unlink`
   and `git add` needs it. Delete `plugins\.git` and run it from Git Bash or
   WSL. See `plan-001` §11.

Then the lanes, in parallel, one worktree each. `plan-001` §9 has the
dependency graph and the one file two lanes both want; `plan-002` §9 has the
mobile chain and §8 re-weights plan-001 by what the phone actually feels.

**Mobile first means `H-1` before `A-1` before `B-1`.** Five of `H-1`'s nine
legacy shell sites are in `apollo-events/src/Plugin.php`, and you cannot unify
the two head builders while five screens still enter through the retired one.

---

## What "release" means here

**Correct** (plan-001):

```
--rule D01,D02,D05,D06,D08,D09,D11,G02,G03,G08   clean
--harness                                        5 green / 0 red
```

**A PRO mobile app** (plan-002):

```
--rule M01,M02,M03,M04,M05                       clean
--rule M06                                       ladder only: 480 · 768 · 1000 · 1400
Lighthouse mobile                                Installable ✓ · Performance ≥ 90
airplane mode on /casa                           renders the shell, not the dinosaur
```

`D04`, `D07`, `D10`, `G11` and `M06` are burn-downs, not gates. They will still be
counting down on release day, and that is the correct state for them — the
point of a baseline is that debt shrinks without blocking work.

---

## A note on the guard's severities

A rule that cannot be mechanised honestly is MEDIUM, never CRITICAL. Three
calibrations were made deliberately while writing it, each after checking the
source by hand:

- **Prepared-fragment SQL is not injectable.** `apollo-membership`'s
  `achievement-functions.php` concatenates `$wpdb->prepare()` output, which is
  correct WordPress. The guard detects that and drops to MEDIUM "verify".
- **A public GET is not a finding.** 100 of the 112 open REST routes are reads,
  correct for a party model where everything is readable.
- **Following an artist on SoundCloud is not an Apollo follow.** `scFollow`,
  `bcFollow`, `spFollow` are outbound links, not a social graph.

A scanner nobody trusts is a scanner nobody runs. If a finding is wrong, fix
the rule — do not add an exception in your head.
