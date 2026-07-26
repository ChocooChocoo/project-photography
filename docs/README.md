# Capstone Documentation Index

Documentation for the Laravel Studio Platform capstone project, organized by purpose. Nothing in this
directory reflects implemented code changes unless a document explicitly says so — most of this is
analysis and planning.

---

## 01-ANALYSIS

What the system currently is and how it works — no implementation, just findings.

- [`ANALYSIS BRIEF.md`](./01-ANALYSIS/ANALYSIS%20BRIEF.md) — the objective/brief that scoped the analysis work below (tech stack ID, architecture, flow, output format).
- [`TECHNICAL ANALYSIS.md`](./01-ANALYSIS/TECHNICAL%20ANALYSIS.md) — full technical scan: tech stack, database, architecture, flowcharts, API inventory, known issues. Audience: developers/reviewers.
- [`NON TECHNICAL ANALYSIS.md`](./01-ANALYSIS/NON%20TECHNICAL%20ANALYSIS.md) — plain-language companion to the technical analysis, with a glossary. Audience: non-technical stakeholders.
- [`REVISION CHECKLIST AND RECOMMENDATIONS.md`](./01-ANALYSIS/REVISION%20CHECKLIST%20AND%20RECOMMENDATIONS.md) — revision checklist against capstone requirements, automation suggestions, deep-scan findings, and workflow improvement proposals.

## 02-PLANNING

What to do about the findings above.

- [`CAPSTONE B IMPLEMENTATION ROADMAP.md`](./02-PLANNING/CAPSTONE%20B%20IMPLEMENTATION%20ROADMAP.md) — phased execution plan derived from `REVISION CHECKLIST AND RECOMMENDATIONS.md`. Phases are dependency-ordered; each has its own checklist.

## 03-PROGRESS

Tracking execution against the plan.

- [`ROADMAP PROGRESS.md`](./03-PROGRESS/ROADMAP%20PROGRESS.md) — status of roadmap phases as they're completed. Audience: developers/reviewers.
- [`NON TECHNICAL ROADMAP PROGRESS.md`](./03-PROGRESS/NON%20TECHNICAL%20ROADMAP%20PROGRESS.md) — plain-language companion to the above, same items, same structure. Audience: non-technical stakeholders.

## 04-REFERENCE

Additional material that sits outside the analysis → planning → progress pipeline. Deep-dives on a single
feature or a single problem, each feeding one roadmap phase rather than the roadmap as a whole.

- [`AI ASSISTANT INTEGRATION.md`](./04-REFERENCE/AI%20ASSISTANT%20INTEGRATION.md) — the Groq-powered photography AI assistant that replaced the fixed-response chatbot: architecture, configuration, security controls, fallback behavior, usage limits, testing. **Living documentation of shipped code** (roadmap Phase 8). Audience: developers/reviewers.
- [`PHOTOGRAPHER CANCELLATION CONTINGENCY.md`](./04-REFERENCE/PHOTOGRAPHER%20CANCELLATION%20CONTINGENCY.md) — what happens when a photographer cancels a booking the client has already paid for: current behavior, nine resolution options (substitution inside and outside the studio, reschedule, refund, value-gap refund, credit, manual escalation), the nine business decisions that gate them, and a recommended build set. **Analysis only — no policy chosen, nothing implemented** (feeds roadmap Phase 9).

---

## How these relate

```
01-ANALYSIS  →  what exists today (system scan, tech/non-tech write-ups, gap checklist)
     ↓
02-PLANNING  →  what to build/fix, in what order (roadmap)
     ↓
03-PROGRESS  →  what's actually been done against that roadmap

04-REFERENCE →  sits alongside, not in the chain: per-feature and per-problem
                deep-dives that each feed a single roadmap phase
```

Start with `NON TECHNICAL ANALYSIS.md` for a plain-language overview, or `TECHNICAL ANALYSIS.md` for
implementation detail. `REVISION CHECKLIST AND RECOMMENDATIONS.md` is the source of truth for the
*original* gap list; the roadmap follows from it.

**Note on currency:** the 01-ANALYSIS documents are dated snapshots taken before any implementation
(scan dates 2026-06-21 / 2026-06-24). Roadmap Phases 1–3 have since been implemented, so several gaps
listed there are now closed. For the current state of any checklist item, read
[`03-PROGRESS/ROADMAP PROGRESS.md`](./03-PROGRESS/ROADMAP%20PROGRESS.md) — it takes precedence over
01-ANALYSIS wherever the two disagree.
