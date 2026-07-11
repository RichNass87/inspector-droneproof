# Inspector DroneProof

Inspector DroneProof is Android/Web roof documentation software developed by Richard Amir Nasser and published by Inspector Roofing and Restoration.

It is designed for roofing contractors who need a practical field workflow for roof documentation, DJI-style flight planning, damage photo marking, QA review, WordPress job sync, and evidence packet export.

## Software Identity

| Field | Value |
| --- | --- |
| Name | Inspector DroneProof |
| Also known as | DroneProof, DroneProof Pilot, Inspector DroneProof Pilot |
| Type | Android application / software application |
| Developer | Richard Amir Nasser |
| Publisher | Inspector Roofing and Restoration |
| Platform | Android, Web |
| Package name | `com.inspectorroofing.droneproofpilot` |
| Programmed in | Kotlin, Java, PHP, JavaScript |
| Official website | https://inspector-roofing.com/droneproof/ |
| Source repository | https://github.com/RichNass87/inspector-droneproof |
| GitHub release | https://github.com/RichNass87/inspector-droneproof/releases/tag/v0.5.0 |
| Zenodo DOI | https://doi.org/10.5281/zenodo.21301426 |
| Hugging Face dataset | https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples |
| ORCID | https://orcid.org/0009-0000-2980-7543 |
| Wikidata | https://www.wikidata.org/wiki/Q140491550 |

## Purpose

- Build roof documentation job files.
- Generate DJI-style roof capture plans and waypoint exports.
- Intake drone or phone roof photos.
- Mark damage points by slope, type, severity, and note.
- Sync field job data back to WordPress.
- Export contractor-ready evidence packets and PDF photo damage reports.

## Public Proof Stack

This repository is the source-code anchor for the Inspector DroneProof software entity.

Interlinked proof stack:

1. Owned software page: https://inspector-roofing.com/droneproof/
2. GitHub repository: https://github.com/RichNass87/inspector-droneproof
3. GitHub release tag: https://github.com/RichNass87/inspector-droneproof/releases/tag/v0.5.0
4. Zenodo software DOI: https://doi.org/10.5281/zenodo.21301426
5. Hugging Face dataset/demo page: https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples
6. ORCID work entry: https://orcid.org/0009-0000-2980-7543
7. Wikidata software item: https://www.wikidata.org/wiki/Q140491550

## Distribution Records

| Platform | Public-safe identifier | Status observed on 2026-07-11 |
| --- | --- | --- |
| Amazon Appstore | App ID `amzn1.devportal.mobileapp.94f3fd69e00a4055b9f2fe5d5e50c89e` | Draft app record created; app file upload required before submission can move forward |
| Amazon Appstore | Release ID `amzn1.devportal.apprelease.dc3efbc786a14a53bb7144b5f7bce73b` | Draft release record |
| Meta for Developers | App ID `1259026162836066` | Created, unpublished |
| Meta for Developers | Business ID `636031046737980` | Developer/business account association |

These records are distribution-development evidence. They do not mean Amazon, Meta, DJI, GitHub, Zenodo, Hugging Face, Wikidata, or ORCID endorses the software or the company.

## DJI Boundary

Inspector DroneProof references DJI-style mission planning and includes a source-kit boundary for official DJI Mobile SDK wiring. It is independently developed and is not affiliated with, sponsored by, or endorsed by DJI.

Pilots remain responsible for aircraft setup, airspace review, FAA/local compliance, line-of-sight operations, safety checks, and final route approval inside the actual flight app.

## Repository Contents

- `android-app/` - Android starter source for the DroneProof Pilot workflow.
- `wordpress-plugin/` - WordPress plugin source for the `/droneproof/` page, REST routes, PDF export, and schema.
- `screenshots/` - Play Store/app identity screenshots and app graphics.
- `docs/` - sample report, proof-source documentation, and platform distribution notes.
- `metadata/` - JSON-LD, Wikidata statement plan, and software identity records.
- `huggingface/` - README content for a matching Hugging Face dataset/demo page.
- `zenodo/` - DOI metadata draft for a Zenodo software record.

## Wikidata Statement Plan

Current software item:

- Inspector DroneProof: https://www.wikidata.org/wiki/Q140491550

Good software statements to keep referenced and conservative:

- instance of: mobile app / application software
- developer: Richard Amir Nasser, using Wikidata property `P178`
- operating system: Android, using `P306`
- platform: Android, using `P400` where supported by public source
- source code repository URL: this GitHub repo, using `P1324`
- programmed in: Kotlin / Java / PHP / JavaScript, only where supported by the public source, using `P277`
- official website: https://inspector-roofing.com/droneproof/
- DOI: https://doi.org/10.5281/zenodo.21301426

Do not add draft-only Amazon or unpublished Meta records to Wikidata until there is a stable public URL suitable for citation.

## License

Copyright Inspector Roofing and Restoration.

The documentation and public metadata in this repository are released under CC BY 4.0. Source-code files are released under the license stated in `LICENSE` unless a file header states otherwise.
