# Scope and Evidence Rules

> **In plain terms:** These rules define which project facts belong in the current record. Claims without evidence stay open instead of being presented as current behavior.

## In scope

- Current repository behavior evidenced by source, migrations, routes, configuration, and tests.
- The ten unchanged prompts in `prompt/tasks/`.
- Historical implementation status only where repository evidence or the retained task record supports it.

## Out of scope

- New features, policy choices, database changes, and claims based only on old recommendations.
- Secrets, live environment values, payment credentials, and customer data.

## Status vocabulary

`Not Started`, `Ready`, `In Progress`, `Blocked`, `Under Review`, `Testing`, `Completed`, `Deferred`, and `Cancelled` are the only status labels. “Completed” requires linked evidence.
