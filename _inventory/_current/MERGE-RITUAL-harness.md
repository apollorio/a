# Merge ritual — harness (plan-003 · P1-5)

Pre-commit runs `apollo-guard --staged` only. It must stay fast.

Before merging a phase branch into `plugins/` (the deploy), run the harnesses
from the worktree:

    node _inventory/_current/apollo-guard.mjs --root "$PWD" --harness

Five green / zero red is the bar. A slow hook gets bypassed; a bypassed hook
is worse than none — that is why harness is a merge gate, not a commit gate.
