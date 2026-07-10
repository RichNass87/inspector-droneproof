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

## Purpose

- Build roof documentation job files.
- Generate DJI-style roof capture plans and waypoint exports.
- Intake drone or phone roof photos.
- Mark damage points by slope, type, severity, and note.
- Sync field job data back to WordPress.
- Export contractor-ready evidence packets and PDF photo damage reports.

## DJI Boundary

Inspector DroneProof references DJI-style mission planning and includes a source-kit boundary for official DJI Mobile SDK wiring. It is independently developed and is not affiliated with, sponsored by, or endorsed by DJI.

Pilots remain responsible for aircraft setup, airspace review, FAA/local compliance, line-of-sight operations, safety checks, and final route approval inside the actual flight app.

## Repository Contents

- `android-app/` - Android starter source for the DroneProof Pilot workflow.
- `wordpress-plugin/` - WordPress plugin source for the `/droneproof/` page, REST routes, PDF export, and schema.
- `screenshots/` - Play Store/app identity screenshots and app graphics.
- `docs/` - sample report and proof-source documentation.
- `metadata/` - JSON-LD, Wikidata statement plan, and software identity records.
- `huggingface/` - README content for a matching Hugging Face dataset/demo page.
- `zenodo/` - DOI metadata draft for a Zenodo software record.

## Public Proof Stack

This repository is the source-code anchor for the Inspector DroneProof software entity.

Interlinked proof stack:

1. Owned software page: https://inspector-roofing.com/droneproof/
2. GitHub repository: https://github.com/RichNass87/inspector-droneproof
3. GitHub release tag: https://github.com/RichNass87/inspector-droneproof/releases/tag/v0.5.0
4. Zenodo software DOI: https://doi.org/10.5281/zenodo.21301426
5. Hugging Face dataset/demo page: https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples
6. ORCID work entry: https://orcid.org/0009-0000-2980-7543
7. Wikidata item only after the public source stack exists.

## Wikidata Statement Plan

Good software statements after the proof stack exists:

- instance of: mobile app / application software
- developer: Richard Amir Nasser, using Wikidata property `P178`
- operating system: Android, using `P306`
- platform: Android and DJI drone platform if supported, using `P400`
- source code repository URL: this GitHub repo, using `P1324`
- programmed in: Kotlin / Java / PHP / JavaScript, only where supported by the public source, using `P277`
- official website: https://inspector-roofing.com/droneproof/

## License

Copyright Inspector Roofing and Restoration.

The documentation and public metadata in this repository are released under CC BY 4.0. Source-code files are released under the license stated in `LICENSE` unless a file header states otherwise.
