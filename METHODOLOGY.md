# Methodology

## Collection sequence

1. Create or load a roof-documentation job.
2. Record only the property and mission details needed for the field task.
3. Capture or import photos under pilot and inspector control.
4. Assign roof-plane, photo, observation, and marker identifiers.
5. Review annotations and notes before export.
6. Export a documentation packet for human review.

## Review rules

- A human inspector or pilot reviews every mission, photo, marker, and exported statement.
- A generated waypoint is a planning reference, not an aircraft command or safety authorization.
- An annotation identifies an observable condition or review question; it is not an engineering, coverage, causation, or legal determination.
- Direct aircraft control remains disabled until an official SDK adapter is implemented and separately hardware-tested.

## Privacy and redaction

- Public examples use synthetic addresses, identifiers, coordinates, and observations.
- Customer addresses, photos, insurance documents, tokens, credentials, and claim identifiers must not be committed to this repository or uploaded to the public dataset.
- Production deletion and retention are controlled by the WordPress administrator and the published privacy policy.

## Credential handling

- No signing key, keystore password, API key, app secret, WordPress token, or private certificate number belongs in this repository.
- FAA or other credential references must use public-safe issuer evidence and must not expose private cards or certificate numbers.
- A developer-console record proves configuration activity only; it is not a platform certification or endorsement.

## Insurance and public-adjuster boundary

Inspector Roofing documents observable roof conditions and does not act as a public adjuster. Carriers decide coverage, payment, and claim outcomes. DroneProof does not interpret policy language, negotiate claims, or promise outcomes.

## Reproducibility

Run `npm run checksums` and `npm run validate` with Node.js 18 or later. The score is calculated from disclosed checks in `VALIDATOR.md`. Generated reports and the checksum ledger itself are excluded from checksum generation.
