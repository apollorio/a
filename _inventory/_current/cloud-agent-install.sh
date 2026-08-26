#!/usr/bin/env bash
# Idempotent Cloud Agent bootstrap for apollorio/a (WordPress apollo-* plugins).
# Safe to re-run. Does not start servers. Does not mutate production.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT"

echo "[apollo-install] root=$ROOT"

# Pre-commit gate used by this monorepo (apollo-guard --staged).
git config core.hooksPath .githooks
chmod +x .githooks/pre-commit 2>/dev/null || true
echo "[apollo-install] core.hooksPath=$(git config --get core.hooksPath)"

# Toolchain pins expected by CLAUDE.md / harnesses.
node -e 'const major=+process.versions.node.split(".")[0]; if(major<20){console.error("Need Node >=20, got",process.versions.node); process.exit(1)} console.log("[apollo-install] node",process.versions.node)'
python3 -c 'import sys; assert sys.version_info>=(3,10), sys.version; print("[apollo-install] python", "%d.%d.%d"%sys.version_info[:3])'

# PHP unlocks portal harness H2/H3 (header render). Snapshot usually already has it.
if ! command -v php >/dev/null 2>&1; then
  echo "[apollo-install] installing php-cli…"
  sudo apt-get update -qq
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
    php-cli php-xml php-mbstring php-curl php-zip
fi
php -r 'echo "[apollo-install] php ", PHP_VERSION, PHP_EOL;'

# Soft structural check — must terminate; full harness suite is for agent verification.
test -f "$ROOT/_inventory/registry/build.js"
test -f "$ROOT/_inventory/_current/apollo-guard.mjs"
test -f "$ROOT/apollo-events/_sandbox/build-portal-harness.mjs"

echo "[apollo-install] OK"
