# Analysis Brief and Provenance

> **In plain terms:** This is the original brief that defined what the system analysis needed to examine. It is preserved as context for the detailed records.

---

## Original Analysis Objective
## System Architecture, Process, Flow, Purpose and Content Analyzer

---

## Description
The goal is to scan an entire active project or codebase — adapting to whatever type of project it is — to identify the tech stack being used, including what kind of database is used (e.g. whether it is relational or otherwise), if applicable. From this analysis, the current system architecture should be documented, along with the overall process of the system from start to end and the system flow, to support better understanding of the project. The system flow must be provided as a real, accurate, and proper flowchart for the process, including separate flowcharts for each process if there are multiple processes or multiple processes per module. The content of the system — what the system is all about — should also be explained. No implementation will happen since this is an analyzing phase. The final and expected output should provide both technical and non-technical details, compiled or written into 2 separate .md files — one technical and one non-technical.

---

## Primary Objective
Scan the whole codebase and identify the tech stack being used, including what kind of database is used and whether it is relational, if applicable to the project.

---

## Secondary Objectives
- Write the current system architecture of the system or the whole project.
- Identify the overall process of the system from start to end, as well as the system flow, for better understanding of the project.
- Provide a real, accurate, and proper flowchart for the process, including flowcharts for multiple processes or multiple processes per module if applicable.
- Tell what is the content of the system or what the system is all about.
- Compile or write the final and expected output on a .md file.
- No implementation will happen since it's an analyzing phase.
- Provide both technical and non-technical details, as 2 separate md files — 1 technical and 1 non-technical.
- Adapt the analysis to whatever the project actually contains, applying conditional items only if applicable.

---

## Supporting Tasks

### Scope & Boundaries
- Determine the scope of the scan based on what the project actually contains
- Exclude non-essential files/folders from analysis (e.g. dependency folders, build artifacts, vendor folders) so the output isn't bloated with noise
- Note if multiple repos/services are involved, or if it's a single project

### Tech Stack Identification
- Scan the whole codebase
- Identify the tech stack being used
- Identify what kind of database is used, if the project has one
- Identify whether the database is relational, etc., if applicable
- Identify dependency versions in use, not just the names of libraries/frameworks

### System Architecture Documentation
- Write the current system architecture of the system or the whole project

### Process and Flow Identification
- Identify the overall process of the system from start to end
- Identify the system flow for better understanding of the project
- Provide a real, accurate, and proper flowchart for the process
- If there are multiple processes, or multiple processes for every module, provide a flowchart for each

### Conditional Technical Details *(include only if present in the project)*
- Database schema or entity-relationship diagram, if a database exists
- API/endpoint inventory, if the project exposes routes or a backend service
- Third-party integrations (auth providers, payment processors, external APIs, cloud services, etc.), if any are used
- Environment/config overview (structure only, no actual secret values), if config exists

### Content and Purpose Explanation
- Tell what is the content of the system
- Tell what the system is all about

### Known Issues & Quality Signals
- Identify known issues or technical debt (e.g. TODO/FIXME comments, deprecated patterns)
- Note any obvious gaps found during the scan

### Output Format
- Compile the final and expected output
- Write the output on a .md file
- Provide both technical and non-technical details
- Produce 2 separate md files: 1 technical, 1 non-technical
- Include a glossary translating technical terms into plain language in the non-technical file

### Limitations
- Note any assumptions made during the analysis
- Note any limitations of the scan (e.g. missing documentation, ambiguous code, no way to verify certain behavior)

### Phase Scope
- No implementation will happen
- This is an analyzing phase

---

## Detailed Breakdown

### Scope & Boundaries
Determine what is in scope for the scan, adapting to the actual structure of the project.

#### Nested Details
- Excludes non-essential files/folders (dependency folders, build artifacts, vendor folders) to avoid noise
- Notes whether the project is a single codebase or spans multiple repos/services

### Tech Stack Identification
Scan the active project or codebase in full to determine the tech stack being used.

#### Nested Details
- Scan covers the whole codebase, not a partial section
- Identification is of the tech stack being used in the project
- Identification includes what kind of database is used, if one exists
- Identification includes whether the database is relational, "etc.," if applicable
- Identification includes the specific versions of dependencies in use

### System Architecture Documentation
Write the current system architecture of the system or the whole project, based on what was found in the codebase scan.

#### Nested Details
- Architecture described is the "current" architecture — i.e., as it presently exists
- Scope is "the system or the whole project"

### Process and Flow Identification — Overall Process
Identify the overall process of the system, covering it from start to end.

#### Nested Details
- Process identification spans the full lifecycle: from the start point to the end point
- Purpose is to capture the complete process, not an isolated segment

### Process and Flow Identification — System Flow
Identify the system flow, separate from but related to the overall process, and provide it as a flowchart.

#### Nested Details
- Flow identification is explicitly stated as being "for better understanding of the project"
- Flow and process are named together but are distinct items to identify
- The flowchart provided must be real, accurate, and proper
- If there are multiple processes, or multiple processes for every module, a flowchart must be provided for each one

### Conditional Technical Details
Include additional technical detail sections only where the project actually contains the relevant component.

#### Nested Details
- Database schema/ER diagram is included only if a database is present
- API/endpoint inventory is included only if the project has routes or a backend service
- Third-party integrations are documented only if any are actually used
- Environment/config overview is included only if config exists, and never exposes actual secret values

### Content and Purpose Explanation
Tell what is the content of the system, and what the system is all about.

#### Nested Details
- "Content of the system" and "what the system is all about" are stated as two phrasings of the same task
- This task follows stack, architecture, and process/flow identification

### Known Issues & Quality Signals
Identify known issues or technical debt found during the scan.

#### Nested Details
- Looks for TODO/FIXME comments or deprecated patterns
- Notes any obvious gaps discovered

### Output Format
The final and expected output should be compiled or written on .md files, providing both technical and non-technical details.

#### Nested Details
- Output is described as both "final" and "expected" — i.e., the deliverable result of the prior tasks
- Output medium is specified as .md files
- Output is split into 2 separate md files: one technical, one non-technical
- The non-technical file includes a glossary translating technical terms into plain language

### Limitations
Note assumptions and limitations encountered during the analysis.

#### Nested Details
- Documents anything the scan couldn't fully determine (e.g. missing docs, ambiguous code, unverifiable behavior)
- Documents any assumptions made in the absence of clear information

### Phase Scope
No implementation will happen since this is an analyzing phase.

#### Nested Details
- This phase is explicitly scoped as analysis only
- Implementation is explicitly excluded from this phase
