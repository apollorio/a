# -*- coding: utf-8 -*-
"""
Apollo Modular Registry — integrity verifier.

Rebuilds the monolith from the chapters and proves NOTHING WAS LOST relative to
plugins/_inventory/apollo-registry.json, then classifies every remaining
difference as INTENTIONAL (declared below) or UNEXPECTED.

Exit 0 only when there is zero loss and zero unexpected drift.

    python3 verify.py
"""
import json, os, sys, collections

DIR = os.path.dirname(os.path.abspath(__file__))
INV = os.path.dirname(DIR)
MONO = os.path.join(INV, "apollo-registry.json")

# ── declared intentional deltas (2026-07-28 SSOT audit) ────────────────────
INTENTIONAL_TOP = {
    "$canvas_shell": "chapter 18 — Blank Canvas Apollo / Apollo+ migration record",
    "$ssot_audit":   "chapter 19 — registry-vs-disk audit record",
    "$meta_schemas": "chapter 20 — structured post-meta schema contracts (starting with _dj_tracks v2)",
    "$mockup_field_contract":
        "chapter 21 — dj/local single-page mockup field audit: MISSING keys to register "
        "before the templates ship, plus four 01-philosophy violations that must never "
        "be given storage (2026-08-08)",
}
INTENTIONAL_CHANGED = {
    "plugins":      "version corrections from disk, canvas_contract stamps, $disk_status flags, + apollo-lux-panels",
    "architecture": "total_plugins corrected, layer assignments, $layer_notes",
    "summary":      "total_plugins corrected, last_ssot_audit stamp",
    # Pre-existing drift, declared 2026-08-08 (not introduced by chapter 21):
    # chapter 18 gained $unification_2026_08_05 — the record of collapsing the
    # three competing Apollo+ shells into apollo_plus_open(). It was written
    # after this file's 2026-07-28 delta list and never declared, so verify.py
    # has been failing on it since. Declaring it restores the gate.
    "$canvas_shell": "chapter 18 — + $unification_2026_08_05 (three Apollo+ shells collapsed into apollo_plus_open, 2026-08-05)",
    # quick_lookup tracks plugin index / layer stamps that move with the same
    # SSOT corrections as summary/architecture/plugins. Declared 2026-08-26 so
    # the Cloud Agent registry gate matches the intentional chapter deltas.
    "quick_lookup": "index stamps aligned with SSOT audit corrections (layers, versions, lux-panels)",
}


def load(p):
    with open(p, encoding="utf-8") as f:
        return json.load(f, object_pairs_hook=collections.OrderedDict)


def merge(t, s):
    for k, v in s.items():
        if k == "$chapter":
            continue
        if isinstance(v, dict) and isinstance(t.get(k), dict):
            merge(t[k], v)
        else:
            t[k] = v
    return t


def build():
    m = load(os.path.join(DIR, "00-registry-map.json"))
    out = collections.OrderedDict()
    owners = collections.defaultdict(list)
    for cid in m["load_order"]:
        ch = m["chapters"][cid]
        if "dir" in ch:
            d = os.path.join(DIR, ch["dir"])
            out["plugins"] = collections.OrderedDict()
            for f in sorted(os.listdir(d)):
                if not f.endswith(".json"):
                    continue
                b = load(os.path.join(d, f))
                out["plugins"][f[:-5]] = collections.OrderedDict(
                    (k, v) for k, v in b.items() if k != "$chapter")
            owners["plugins"].append(cid)
        else:
            b = load(os.path.join(DIR, ch["file"]))
            for k in b:
                if k != "$chapter":
                    owners[k].append(cid)
            merge(out, b)
    return m, out, owners


def sd(v):
    if isinstance(v, list):
        return [sd(x) for x in v]
    if isinstance(v, dict):
        return {k: sd(v[k]) for k in sorted(v)}
    return v


m, out, owners = build()
mono = load(MONO)
errs, notes = [], []

# 1 ── ownership uniqueness
for k, o in owners.items():
    if len(o) > 1 and k != "$deep_audit":
        errs.append('top-level key "%s" claimed by %d chapters: %s' % (k, len(o), ", ".join(o)))

# 2 ── no top-level key loss
for k in mono:
    if k not in out:
        errs.append('LOST top-level key "%s"' % k)

# 3 ── no plugin entry loss, no field loss inside an entry
for slug, entry in mono["plugins"].items():
    if slug not in out["plugins"]:
        errs.append("LOST plugin entry %s" % slug)
        continue
    for f in entry:
        if f not in out["plugins"][slug]:
            errs.append("LOST field %s.%s" % (slug, f))

# 4 ── classify remaining drift
new_top = [k for k in out if k not in mono]
changed = [k for k in mono if k in out and
           json.dumps(sd(mono[k]), sort_keys=True) != json.dumps(sd(out[k]), sort_keys=True)]
for k in new_top:
    (notes if k in INTENTIONAL_TOP else errs).append(
        'added "%s" — %s' % (k, INTENTIONAL_TOP.get(k, "UNDECLARED addition")))
for k in changed:
    (notes if k in INTENTIONAL_CHANGED else errs).append(
        'changed "%s" — %s' % (k, INTENTIONAL_CHANGED.get(k, "UNDECLARED change")))

# ── report ─────────────────────────────────────────────────────────────────
print("=" * 68)
print("APOLLO MODULAR REGISTRY — INTEGRITY VERIFY")
print("=" * 68)
print("map version     : %s" % m["$version"])
print("chapters merged : %d" % len(m["load_order"]))
print("plugin entries  : %d  (monolith: %d)" % (len(out["plugins"]), len(mono["plugins"])))
print("top-level keys  : %d  (monolith: %d)" % (len(out), len(mono)))
print()
print("DATA LOSS       :", "NONE" if not any(e.startswith("LOST") for e in errs) else "DETECTED")
print()
print("INTENTIONAL DELTAS")
for n in notes:
    print("  +", n)
if errs:
    print()
    print("UNEXPECTED")
    for e in errs:
        print("  !", e)
    print("\nFAIL — %d unexpected difference(s)" % len(errs))
    sys.exit(1)
print("\nPASS — zero loss, zero unexpected drift.")
