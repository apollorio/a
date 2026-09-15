# APOLLO-WAHA — PROJECT CONTEXT

You are working inside the **Apollo ecosystem**, specifically on the **apollo-waha** project.

This session is primarily dedicated to understanding, maintaining, debugging, verifying, and making controlled changes related to **apollo-waha** and its integration with the broader Apollo architecture.

## 1. Apollo in general

Apollo is a modular WordPress-based system being evolved toward a cellular architecture.

The current architectural direction is approximately:

```text
APOLLO
│
├── BRAIN / REQUEST CONTEXT
│
├── CORE
│   └── registration / shared structural ownership
│
├── RUNTIME
│   └── helpers / transformations / runtime consumers
│
├── UI
│   └── rendering / shell / layout / parts
│
├── POLICY
│   └── only where WordPress lifecycle evidence proves it is safe
│
└── DOMAIN CELLS
    ├── EVENTS
    ├── MAPS
    ├── ADS
    ├── DJS
    ├── WAHA
    └── other Apollo domains
```

The important architectural concept is:

```text
CONTRACTS > HIERARCHY
```

Each Apollo component should have clearly verified answers to:

```text
What does it own?
What does it consume?
What must it never own?
```

Do not infer ownership merely from filenames or desired architecture.

Actual runtime evidence wins.

---

## 2. BRAIN / request context

Apollo already has a live progressive request-context layer.

Its purpose is to answer:

```text
"What do we currently know about this request?"
```

Known request-context concepts include:

```text
method
logged_in
rest
slug
cpt
```

Information is populated only when WordPress makes that information authoritative.

Important:

```text
UNKNOWN != FALSE
NULL    != UNKNOWN
EMPTY   != NULL
```

Do not force request knowledge earlier than the WordPress lifecycle allows.

BRAIN-01 is observational.

It does not inherently mean:

```text
selective plugin loading
cache
CPT registration
REST registration
HTML rendering
UI ownership
```

---

## 3. WordPress lifecycle matters

Apollo contains WordPress plugins, MU plugins, force-load paths, hooks, CPT registrations, REST endpoints, templates, runtime helpers, and other integrations.

Never assume a hook or decision point happens at the time that would be architecturally convenient.

Always distinguish:

```text
information availability
!=
plugin-loading availability
```

and:

```text
request context
!=
plugin loading
!=
cache
```

Some Apollo code can be force-loaded from MU-plugin top-level execution before normal Apollo hooks are available.

Therefore:

```text
do not invent lifecycle control
do not retroactively "gate" code that has already loaded
do not assume active_plugins can safely be filtered
```

Verify actual boot order first.

---

## 4. Apollo development law

For this project, operate using this sequence:

```text
DISCOVER
↓
VERIFY
↓
ONE SMALL CHANGE
↓
VERIFY
↓
NEXT LAYER
```

Never:

```text
DISCOVER
↓
REWRITE EVERYTHING
```

Mandatory rules:

```text
1. Evidence > target architecture.
2. UNKNOWN stays UNKNOWN until directly proven.
3. One topic per run.
4. One writer per lock.
5. No broad refactor.
6. No invented WordPress hooks.
7. No invented cache layer.
8. No invented CPT or REST namespace.
9. Do not touch unrelated dirty work.
10. Verify before merge.
11. Verify live when deployment is involved.
12. Never overwrite a dirty file wholesale.
13. Do not create a new manager/plugin ecosystem merely to simplify architecture aesthetically.
14. Existing behavior must be understood before ownership is changed.
```

If evidence contradicts the desired architecture, report the evidence.

Do not force reality to match the plan.

---

## 5. Git / dirty-tree rule

The Apollo development environment may contain an intentionally dirty working tree.

Before modifying anything:

```text
inspect git status
identify task-related files
identify unrelated modifications
preserve unrelated work
```

Never automatically:

```text
git reset --hard
git clean
checkout unrelated files
overwrite complete dirty files
mass-format the repository
```

Changes should be surgical.

---

## 6. Ownership verification

When the same concept appears in multiple places, do not immediately call it duplication.

For example, Apollo already encountered cases where two CPT registration paths could represent:

```text
SOLE_RUNTIME_REGISTRATION
GUARDED_FALLBACK_REGISTRATION
TRUE_DUPLICATE_RUNTIME_REGISTRATION
UNKNOWN
```

The same principle applies to apollo-waha.

Multiple implementations can mean:

```text
fallback
compatibility path
legacy path
dead code
runtime duplicate
different lifecycle paths
different ownership boundaries
```

Determine which one is true before deleting or consolidating anything.

Also distinguish duplicate execution from argument/configuration drift.

---

## 7. REST and public behavior

Apollo contains both WordPress REST behavior and Apollo-owned REST behavior.

A public endpoint is not automatically a vulnerability.

For every endpoint, verify:

```text
owner intent
permission_callback
authentication requirements
nonce behavior
data sensitivity
method
actual runtime exposure
consumer expectations
```

Do not harden or disable behavior solely because a scanner labels it public.

---

## 8. Runtime and UI direction

The long-term direction is toward:

```text
request
↓
canonical context
↓
runtime/helpers
↓
UI
↓
domain data
```

Desired separation:

```text
runtime
    = request/runtime preparation

UI
    = rendering/layout

domain plugin
    = domain-specific behavior/data
```

But this is a target architecture, not permission to move code immediately.

Before moving WAHA rendering, runtime, hooks, API calls, data handling, or domain behavior:

```text
map current ownership first
```

---

# APOLLO-WAHA SESSION SCOPE

This Codex session is specifically for:

```text
apollo-waha
```

Treat WAHA as an Apollo domain/integration that must obey the same architectural and safety rules above.

However, do NOT assume its internal architecture from this context alone.

The current Apollo context does not define the detailed WAHA implementation.

Therefore the first responsibility in any WAHA task is:

```text
DISCOVER THE ACTUAL WAHA IMPLEMENTATION
```

Before making architectural claims, establish evidence for things such as:

```text
where apollo-waha lives
how it loads
whether it is a normal plugin or force-loaded
its bootstrap file
its WordPress hooks
its REST/AJAX endpoints
its external WAHA API integration
its authentication/configuration
its webhook handling
its cron/background behavior
its database/options/meta usage
its runtime dependencies
its UI/admin ownership
its logging/error handling
its relationship with Apollo core/runtime/brain/UI
its relationship with other Apollo domain plugins
```

Do not invent any of those details.

If something has not been verified from the repository/runtime, mark it:

```text
UNKNOWN
```

---

# DEFAULT WORKFLOW FOR APOLLO-WAHA

For every new WAHA task:

```text
1. Read this context.
2. Read the task-specific files.
3. Inspect relevant apollo-waha source.
4. Inspect git status.
5. Identify actual runtime entry points.
6. Identify dependencies and consumers.
7. Establish ownership.
8. Report unknowns explicitly.
9. Make no change until the problem is proven.
10. If a write is required, make the smallest reversible change.
11. Show the diff.
12. Verify syntax/static behavior.
13. Verify runtime behavior when possible.
14. Stop after the requested scope.
```

---

# DEFAULT SAFETY MODE

Unless the task explicitly authorizes source changes:

```text
MODE = READ ONLY
```

Read-only mode allows:

```text
repository discovery
search
cross-reference
dependency tracing
hook tracing
REST tracing
configuration discovery
ownership analysis
Git history inspection
diff inspection
artifact/report generation
verification
```

It does not allow:

```text
source modification
configuration modification
plugin activation/deactivation
database writes
cache changes
deployment changes
mass formatting
Git cleanup
```

---

# WHEN WRITES ARE AUTHORIZED

Before editing, state internally:

```text
PROBLEM:
EVIDENCE:
OWNER:
FILES:
DEPENDENCIES:
EXPECTED BEHAVIOR:
RISK:
VERIFICATION:
```

Then change the minimum number of lines/files necessary.

Do not bundle unrelated cleanup with the task.

---

# SESSION OBJECTIVE

This chat/session should become the dedicated engineering context for **Apollo WAHA**.

Build knowledge progressively.

Prefer:

```text
verified WAHA facts
+
small task-specific artifacts
```

over repeatedly scanning the entire Apollo repository.

As WAHA facts become directly proven, maintain a compact source-of-truth containing:

```text
WAHA current state
load path
ownership
dependencies
external API contract
webhook flow
REST/AJAX flow
runtime flow
UI/admin flow
configuration
security boundaries
known hazards
verification status
next task
```

The ultimate goal is not to make Apollo-WAHA look architecturally clean.

The goal is:

```text
understand actual behavior
↓
establish verified ownership
↓
remove hazards
↓
make controlled improvements
↓
preserve compatibility
```

Evidence first.