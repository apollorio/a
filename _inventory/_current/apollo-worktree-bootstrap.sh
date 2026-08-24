#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  apollo-worktree-bootstrap — turn the live-mirrored plugins folder into a
#  repository that can hold worktrees, hooks and an undo button.
#
#  Run ONCE, from D:\dev\_apollo.rio.br\plugins (Git Bash / WSL):
#      bash _inventory/_current/apollo-worktree-bootstrap.sh
#
#  What it does, in order:
#    1. writes .gitignore so the mirror never carries scratch into production
#    2. git init + first commit  ← the first rollback point this project has
#    3. installs the pre-commit hook that runs apollo-guard
#    4. records today's debt as a baseline, so the gate blocks NEW breakage only
#    5. prints the worktree commands for the six work lanes
#
#  It does NOT delete anything, does not touch plugin code, and does not push.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

ROOT="$(pwd)"
[ -d "$ROOT/apollo-core" ] || { echo "run this from the plugins/ folder"; exit 2; }
command -v node >/dev/null || { echo "node is required"; exit 2; }

echo "→ 1/5  .gitignore"
cat > "$ROOT/.gitignore" <<'EOF'
# Scratch and machine state — never mirrored, never committed.
_tmp_*
_to_delete/
*.log
*.bak
*.bak[0-9]
sync.ffs_db
.cursor/
node_modules/
vendor/
.DS_Store
Thumbs.db

# Guard output
.apollo-guard-report.json

# Third-party plugins live here but are not ours to version.
elementor/
loginizer/
loginizer-security/
query-monitor/
really-simple-ssl/
wp-debugging/
EOF

echo "→ 2/5  git init"
if [ ! -d "$ROOT/.git" ]; then
  git init -q
  git add -A
  git commit -q -m "chore: baseline — the state of the ecosystem before the gate went in

41 apollo-* plugins on disk, 47 registry entries, ~1.9k PHP files.
This commit exists so that every later change has something to be diffed
against and something to be reverted to. It asserts nothing about quality."
  echo "   first commit created"
else
  echo "   repo already exists — skipping"
fi

echo "→ 3/5  pre-commit hook"
mkdir -p "$ROOT/.githooks"
cat > "$ROOT/.githooks/pre-commit" <<'EOF'
#!/usr/bin/env bash
# apollo-guard pre-commit gate.
#   · scans ONLY what you staged — fast, and it cannot punish you for old debt
#   · CRITICAL/HIGH blocks; MEDIUM prints
#   · genuinely need to bypass:  git commit --no-verify   (and say why in the message)
set -euo pipefail
ROOT="$(git rev-parse --show-toplevel)"
GUARD="$ROOT/_inventory/_current/apollo-guard.mjs"
[ -f "$GUARD" ] || exit 0
BASE=""
[ -f "$ROOT/.apollo-guard-baseline.json" ] && BASE="--baseline $ROOT/.apollo-guard-baseline.json"
node "$GUARD" --root "$ROOT" --staged $BASE || {
  echo ""
  echo "  ── commit refused ──────────────────────────────────────────────"
  echo "  A rule above comes from the registry, not from taste."
  echo "  Fix it, or open the chapter it cites and change the rule on purpose."
  echo ""
  exit 1
}
EOF
chmod +x "$ROOT/.githooks/pre-commit"
git config core.hooksPath .githooks
echo "   core.hooksPath = .githooks"

echo "→ 4/5  debt baseline"
node "$ROOT/_inventory/_current/apollo-guard.mjs" --root "$ROOT" \
     --write-baseline "$ROOT/.apollo-guard-baseline.json" >/dev/null
echo "   $(node -e "console.log(require('$ROOT/.apollo-guard-baseline.json').accepted.length)") existing findings accepted"
echo "   from now on the gate blocks NEW findings only"
echo "   burn it down with: node _inventory/_current/apollo-guard.mjs --rule D08"

echo "→ 5/5  work lanes"
cat <<'EOF'

  Six lanes. One agent per lane, none of them touching another's files.
  Run each from D:\dev\_apollo.rio.br\ (the parent of plugins/):

    git -C plugins worktree add ../wt-leak     lane/leak
    git -C plugins worktree add ../wt-tokens   lane/tokens
    git -C plugins worktree add ../wt-registry lane/registry
    git -C plugins worktree add ../wt-rest     lane/rest
    git -C plugins worktree add ../wt-doctrine lane/doctrine
    git -C plugins worktree add ../wt-shell    lane/shell

  IMPORTANT — only plugins/ is mirrored to production by RealTimeSync.
  A worktree at ../wt-* is NOT mirrored. That is the point: you get a place
  to be wrong. Merge to the plugins/ checkout only after the guard is green.

    cd ../wt-tokens && <work> && node _inventory/_current/apollo-guard.mjs
    cd ../../plugins && git merge lane/tokens      # ← this is the deploy

EOF
echo "done."
