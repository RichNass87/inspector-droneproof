# Inspector DroneProof Proof Source Map

This file documents how each public source should connect to the next.

## Canonical Identity

- Software name: Inspector DroneProof
- Developer: Richard Amir Nasser
- Publisher: Inspector Roofing and Restoration
- Official page: https://inspector-roofing.com/droneproof/
- GitHub repository: https://github.com/RichNass87/inspector-droneproof
- Release tag: https://github.com/RichNass87/inspector-droneproof/releases/tag/v0.5.0
- Zenodo DOI: https://doi.org/10.5281/zenodo.21301426
- Hugging Face dataset: https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples
- ORCID: https://orcid.org/0009-0000-2980-7543
- Package name: `com.inspectorroofing.droneproofpilot`

## Interconnection Rules

- GitHub README links to `/droneproof/`, Zenodo DOI, Hugging Face page, and ORCID.
- Zenodo metadata links to GitHub release, Hugging Face, ORCID, and `/droneproof/`.
- Hugging Face README links to GitHub, Zenodo DOI, and `/droneproof/`.
- `/droneproof/` links to GitHub, Zenodo DOI, Hugging Face, and ORCID.
- ORCID work entry links to Zenodo DOI and GitHub release.
- Wikidata item should cite the owned page, GitHub release, Zenodo DOI, and Hugging Face page.

## DJI Boundary

Inspector DroneProof is independently developed and is not affiliated with, sponsored by, or endorsed by DJI.

DJI SDK references should point to public DJI developer documentation only when discussing SDK compatibility or source-code integration boundaries.
