# Diagrams — show every process, structure, and set of stored information

> Standalone prompt — paste the whole file.

## Your responsibility

You create pictures of what the system actually does, not what people assume it does. These diagrams are part of the work to deliver, not decoration added later.

## Rules that do not change

1. **Create one plain process map for each distinct process.** If a section of the system has three processes, create three maps. Combining unrelated processes makes each one hard to read.
2. **Use numbered steps and simple tables inside the document, never diagram code or screenshots.** Show the same people, actions, choices, information, and results as the matching Technical diagram, but explain them in ordinary words that can be read without a diagramming tool.
3. **Link every diagram from the document that analyzes or plans it.** A diagram with no link is unlikely to be kept current.
4. **Keep every generated filename paired.** Each file must exist under both the Stage 08 `PLAIN` and `TECHNICAL` folders. Add, delete, or rename both copies together.
5. **Keep the plain copy independent.** It must show the same facts, statuses, dates, decisions, risks, and outcomes as the technical copy without code, programming-language details, implementation notation, or links to Technical.

## What to draw

| Diagram | Create one for | When to create it |
|---|---|---|
| **Process map** | Each distinct process | Always — this is the main deliverable |
| **Architecture** (the system’s main structure) | The system | Always |
| **Data flow** (where information travels) | The system | Always |
| **Stored-information relationships** | The records the system keeps and how they relate | Whenever the system keeps structured records |
| **Sequence** (step-by-step exchange between parties) | Each interaction involving multiple parties | Login, payment, third-party calls, or anything with more than two actors |
| **State** (the stages an item moves through) | Each item with a lifecycle | Orders, requests, tickets, or anything that moves through statuses |

### Current and target versions

In an existing system, create both an **as-is** diagram (how it works now) and a **to-be** diagram (how it will work after the change) for anything that will change. This makes the difference visible. In a new system, create only the target version.

## What each diagram needs

Give every map a `DGM-###` reference number, a caption naming the process, section, and whether it shows the current or target version, the finding or requirement it belongs to, its corresponding map if one exists, and a plain-language reading below it.

```markdown
### DGM-004 — User sign-in (as-is: how it works now)
_Section: Authentication · [ANL-009](../../01%20-%20SYSTEM%20ANALYSIS/PLAIN/architecture.md#anl-009) ·
Target state: [DGM-005](process-auth-signin-to-be.md) (`to-be`: how it should work later) · Changed by: TASK-014_

| Step | What happens | If the check fails or information is missing |
|---|---|---|
| 1 | The person submits their email and password. | — |
| 2 | The system checks whether the email is known. | It gives a general failure message. |
| 3 | The system checks whether the password is correct. | It counts the failed attempt and gives the same general failure message. |
| 4 | The system remembers the person as signed in. | — |
| 5 | The system checks whether an agreed sign-in duration was supplied. | It uses the built-in fallback duration. |
| 6 | The person reaches the dashboard. | — |

**In plain terms.** The system checks the email, then the password, then remembers
the person as logged in. The "built-in fallback time" step is where the three
current sign-in paths disagree with each other.
```

`ANL-009` is the related recorded finding, `DGM-005` is the target-version map, and `TASK-014` is the task that changes the process. The saved sign-in record is how the system remembers that the person signed in. The sign-in duration says how long that record remains valid. A built-in fallback duration is used when no agreed duration has been supplied.

The plain-language reading is required. It makes the record useful to a panel, client, adviser, or other reader without an engineering background.

## Plain presentation conventions

- Use numbered steps for the order of events.
- Use a table column for choices and what happens for each result.
- Name places where information is kept in everyday words and explain what each place contains.
- Name participants as people, teams, organizations, or outside services, not as code components.
- Keep each step short and explain important detail underneath the map.

For stored-information relationships, state whether one record connects to one, many, or no other records. For a multi-party sequence, name every person, group, system area, or outside service by its clear business role and list each exchange in order. The matching Technical file may use engineering notation and code-level names; the Plain file must communicate the same relationship and sequence without that notation.

## Keep diagrams true

A map becomes wrong when the system changes and the map does not. Updating the map is part of completing the task that changed the process, not later cleanup.

If you find a map that no longer matches the working system, do not quietly correct it. First record the difference as an `ISS-###` issue reference, because the gap between the document and the actual system is itself a finding.

## Files to produce

```text
docs/WORKFLOW/08 - DIAGRAMS/
|- PLAIN/
|  |- architecture.md
|  |- data-flow.md
|  |- erd.md
|  |- process-<module>-<name>-as-is.md
|  |- process-<module>-<name>-to-be.md
|  `- sequence-<interaction>.md
`- TECHNICAL/
   |- architecture.md
   |- data-flow.md
   |- erd.md
   |- process-<module>-<name>-as-is.md
   |- process-<module>-<name>-to-be.md
   `- sequence-<interaction>.md
```

Create only the files the project needs: system structure and information flow always; stored-information relationships when structured records exist; process files for each distinct process; sequence files for each multi-party interaction. In an existing system, each changing process has both its `as-is` and `to-be` files. In a new system, create only its target process file. An item's lifecycle, when needed, belongs in the relevant paired process file rather than introducing another filename.

Every created relative filename must appear under both audience folders. Add, delete, and rename paired files together. In these filenames, `<module>` means the system section, `<name>` means the process name, and `<interaction>` means the exchange between parties. `as-is` means how it works now; `to-be` means how it should work later. Keep those exact filename parts so related documents can find them.

Every file listed above must open with an `In plain terms` block: two to four sentences before the first diagram explaining what the whole file shows. This is in addition to the plain-language reading required below each diagram.

## Done when

- [ ] Every distinct process has its own plain process map
- [ ] Every process that will change has both a current (`as-is`) and target (`to-be`) version
- [ ] System structure and information flow explained; stored-information relationships explained whenever structured records exist
- [ ] Ordered exchanges recorded for every multi-party interaction
- [ ] Every map has a `DGM-###`, a caption, and a plain-language reading
- [ ] Every map is linked from the document that analyzes or plans it
- [ ] Nothing is a screenshot
- [ ] Every created relative filename exists under both audience folders
- [ ] Both audience copies agree on facts, statuses, dates, decisions, risks, and outcomes
- [ ] Plain files contain no code, programming-language detail, implementation notation, or link to Technical
- [ ] Every diagram file opens with an `In plain terms` block, on top of each diagram's own plain reading

## Input to provide

**Project:** `<path to repository>` — the repository is the project’s main folder.
**Draw:** `<which processes or sections, or "everything">`
**Which version:** `<"as-is" | "to-be" | "both">` — `as-is` means how it works now; `to-be` means how it should work later.
**Docs go in:** `<path — default: docs/WORKFLOW>`
