# Data-safety disclosure worksheet

This worksheet supports store-console review. The final console answers must be checked against the exact production build and deployed server configuration.

| Data category | Collected or processed | Purpose | Public release note |
| --- | --- | --- | --- |
| Property address | Optional, when entered or loaded | Job identification and optional geocoding | May be sent to configured WordPress and Google Maps services |
| Photos and files | Optional, user initiated | Roof documentation and report export | Uploaded only through an authorized workflow |
| User notes and annotations | Optional | Job documentation and QA | May be sent to configured WordPress and optional AI QA service |
| Precise location | Permission declared; current source does not independently collect background location | Mission context and future SDK integration | Recheck before Play submission |
| Device field token | Stored on configured device | Authenticate WordPress field requests | Sensitive; not public and not committed |
| Diagnostics | Hosting may log normal request metadata | Security and troubleshooting | Controlled by hosting configuration |

## Current declarations

- No advertising SDK is included in the Android source.
- No data-sale behavior is implemented.
- No background location service is implemented.
- Camera and image access are user initiated.
- Direct DJI aircraft control is not implemented.
- The final Play Data safety form must be reconciled with the production build, dependencies, privacy URL, deletion process, and server behavior before production submission.
