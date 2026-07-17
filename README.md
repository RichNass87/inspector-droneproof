# Inspector DroneProof

Inspector DroneProof is Android and WordPress roof-documentation software developed by Richard Amir Nasser and published by Inspector Roofing and Restoration. It organizes job inputs, pilot-reviewed capture plans, roof photos, damage markers, QA notes, and evidence-packet exports.

## Release identity

| Field | Value |
| --- | --- |
| Evidence/source package | `v0.6.0` |
| Android application | `0.4.0-droneproof` (`versionCode 4`) |
| WordPress plugin | `6.10.3` |
| Android package | `com.inspectorroofing.droneproofpilot` |
| Developer | Richard Amir Nasser |
| Publisher | Inspector Roofing and Restoration |
| Canonical page | https://inspector-roofing.com/droneproof/ |
| Repository | https://github.com/RichNass87/inspector-droneproof |
| Release | https://github.com/RichNass87/inspector-droneproof/releases/tag/v0.6.0 |
| Project DOI, all versions | https://doi.org/10.5281/zenodo.21301425 |
| Archived v0.5.0 DOI | https://doi.org/10.5281/zenodo.21301426 |
| Evidence samples | https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples |
| Technical paper | https://www.academia.edu/170345468/Inspector_DroneProof_A_Human_Reviewed_Architecture_for_Roof_Documentation_Field_Photo_Intake_and_Reproducible_Evidence_Packets |
| ORCID | https://orcid.org/0009-0000-2980-7543 |
| Privacy | https://inspector-roofing.com/privacy-policy/ |
| Terms | https://inspector-roofing.com/terms/ |

The evidence-package version and Android application version are different identifiers. They must not be presented as the same release number. Zenodo DOI `10.5281/zenodo.21301425` is the project-level concept DOI. DOI `10.5281/zenodo.21301426` identifies the archived `v0.5.0` software record and is retained only with that version label.

The technical paper treats the project DOI as a related software identifier; it does not assign that DOI to the paper. The paper record is first-party, not peer reviewed, and not an independent certification or platform endorsement.

## Implemented scope

- Loads a fallback mission or the latest authorized WordPress field job.
- Captures or imports roof photos on Android.
- Uploads authorized photos and job data to the configured WordPress endpoint.
- Parses waypoints, altitude, heading, camera pitch, and roof-plane labels.
- Requires a pilot checklist before exposing the flight-start bridge.
- Exports documentation data and report inputs from the WordPress application.

## Unimplemented and unverified scope

- The repository does not include DJI SDK binaries.
- The Android application does not arm, launch, or command an aircraft.
- Direct DJI aircraft control has not been hardware-tested or safety-validated.
- Google Play production availability is not claimed by this package.
- A DJI developer-console record is development provenance, not DJI approval or endorsement.

Inspector DroneProof is independently developed and is not affiliated with, sponsored by, certified by, or endorsed by DJI, Google, Amazon, GitHub, Zenodo, Hugging Face, or ORCID.

## Distribution evidence

- Amazon Appstore publication: Inspector DroneProof `0.4.0-droneproof` was reported live on July 16, 2026. The retained Amazon email reports Primary Validation, Content Policy Validation, and Functionality Validation as PASS. This is first-party publication evidence; no stable public listing URL has been captured in this package, and no Amazon endorsement is claimed.
- Google Play: an Android App Bundle exists for internal or closed-track review. Production availability is not claimed.
- DJI: the package and developer application were configured in the developer console. The SDK adapter and aircraft control remain unimplemented.

See [docs/DISTRIBUTION-EVIDENCE.md](docs/DISTRIBUTION-EVIDENCE.md) and [artifacts/android-artifact.json](artifacts/android-artifact.json) for the exact evidence boundary and bundle checksum.

## Repository contents

- `android-app/`: Kotlin Android field application source.
- `wordpress-plugin/`: WordPress application, REST routes, page/schema output, and compliance-page creation.
- `examples/`: synthetic, deidentified evidence-packet example.
- `screenshots/`: app identity and store-review images.
- `metadata/`: public-safe SoftwareApplication JSON-LD and knowledge-graph planning notes.
- `scripts/`: checksum generator and internal release-readiness validator.
- `docs/`: methodology, privacy, data-safety, testing, distribution, and source-map documents.

Compiled Android binaries are excluded from source control. The WordPress distribution package may include a separately built debug APK for controlled testing; it is not a Google Play production release.

## Knowledge-graph boundary

No standalone DroneProof Wikidata item is treated as live or verified by this release. The previously used `Q140491550` identifier is retired and intentionally excluded. A GitHub repository, DOI, owned page, ORCID entry, dataset card, or store publication does not by itself establish Wikidata notability.

## Insurance boundary

Inspector Roofing documents observable roof conditions and organizes contractor documentation. It does not act as a public adjuster. Carriers decide coverage, payment, and claim outcomes. DroneProof does not interpret policy language, promise approval, or determine coverage.

## Validation

```bash
npm run checksums
npm run validate
```

The validator measures internal package completeness and hard-gate compliance. It is not an independent certification, search ranking, Play approval, DJI approval, or statement that the application is production-ready.

## License

Source code is MIT licensed. Documentation, metadata, and the synthetic example are CC BY 4.0 as described in [LICENSE-DOCS.md](LICENSE-DOCS.md).
