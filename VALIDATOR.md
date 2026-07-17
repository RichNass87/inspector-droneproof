# Internal release-readiness validator

The validator measures whether this repository is coherent, public-safe, reproducible, and honest about its implementation state. It does not measure Play production readiness, DJI flight readiness, search rank, Wikidata notability, professional competence, or third-party endorsement.

| Category | Points |
| --- | ---: |
| Identity and version consistency | 15 |
| Source and artifact evidence | 15 |
| Methodology and synthetic sample | 15 |
| Privacy and data safety | 20 |
| Reproducibility and integrity | 15 |
| Safety, platform, and insurance boundaries | 15 |
| Published GitHub release | 5 |

Maximum staged score is 95. Maximum published score is 100. The strong target is 91+.

Release remains on hold regardless of score if the package contains a retired DroneProof Wikidata identifier, a private secret, inconsistent canonical identity/version metadata, missing checksum coverage, missing privacy/data-safety documentation, an unsupported direct-aircraft-control claim, an unsupported public Play/Amazon listing claim, or a missing insurance boundary.

`READY_FOR_PUBLICATION` means the staged package passed the internal checks. `PASS` means the manifest also records the public GitHub release. Neither status is independent certification.
