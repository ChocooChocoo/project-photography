# Glossary

> **In plain terms:** This list translates technical words used in the project record. The final column explains why each word matters to Platinum.

| Technical term | Plain-language meaning | Why it matters here |
| --- | --- | --- |
| Blade | Laravel’s server-generated page template system | It produces the role-specific pages people use |
| Portal | The part of the website shown to one type of user | Each role reaches a different working area |
| Middleware | A checkpoint that allows or blocks a web request | It helps keep portal actions limited to authorized users |
| Migration | A versioned database-structure change | It records how stored information changes over time |
| Seeder | A tool that fills the database with sample or starting data | It supplies the connected test records required by Tasks 03 and 06 |
| Webhook | A message sent by a payment provider to confirm an event | It lets the application verify payment outcomes sent by a provider |
| Public disk | The configured location for website-visible uploads | It keeps upload write and display paths aligned without a storage symlink |
| Traceability | Links showing how a request connects to work and proof | It prevents plans, tasks, and test claims from becoming disconnected |
