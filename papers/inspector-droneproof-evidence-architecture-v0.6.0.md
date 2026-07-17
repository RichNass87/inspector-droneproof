# Inspector DroneProof: A Human-Reviewed Architecture for Roof Documentation, Field-Photo Intake, and Reproducible Evidence Packets

Richard Amir Nasser  
Inspector Roofing and Restoration, Alpharetta, Georgia, United States  
ORCID: https://orcid.org/0009-0000-2980-7543  
Project DOI: https://doi.org/10.5281/zenodo.21301425  
Evidence/source release: v0.6.0  
Published: July 17, 2026

First-party technical paper. This document describes the author's software and evidence architecture. It is not peer reviewed, an independent certification, a DJI or platform endorsement, engineering advice, legal advice, flight authorization, or an insurance-coverage opinion.

## Abstract

Inspector DroneProof is an Android and WordPress roof-documentation system designed to organize authorized field jobs, pilot-reviewed capture plans, roof photographs, damage markers, quality-assurance notes, and evidence-packet exports. The system separates documentation functions from aircraft control and separates observable roofing conditions from insurance decisions. This paper describes the public v0.6.0 evidence architecture, including canonical identity, source versioning, synthetic examples, data handling, checksum verification, and platform-state boundaries. The Android application is version 0.4.0-droneproof, version code 4, under package com.inspectorroofing.droneproofpilot. The WordPress integration is version 6.10.3. The source release does not include DJI SDK binaries and cannot arm, launch, or command an aircraft. A human inspector or pilot remains responsible for mission review, site permissions, regulatory compliance, image interpretation, and final documentation. The project concept DOI, 10.5281/zenodo.21301425, identifies the software family across versions; the archived v0.5.0 record is separately identified by DOI 10.5281/zenodo.21301426.

## 1. Problem and Design Objective

Roof documentation often becomes fragmented across photographs, handwritten notes, inspection forms, maps, estimating records, and carrier-facing files. Fragmentation makes it difficult to determine which image belongs to which roof plane, which observation was made by whom, which version of a report was reviewed, and which statements remain assumptions rather than observed facts.

Inspector DroneProof addresses that organizational problem. Its purpose is not to automate a claim outcome or replace a licensed pilot, engineer, adjuster, or contractor. Its purpose is to preserve relationships among a job, capture plan, roof plane, photograph, observation, marker, reviewer note, and exported packet. The design objective is a reviewable record in which a second person can follow the evidence path without relying on promotional language or undocumented inference.

## 2. System Identity and Version Model

The project uses separate identifiers for separate release layers:

- Evidence and source package: v0.6.0.
- Android application: 0.4.0-droneproof, version code 4.
- Android package: com.inspectorroofing.droneproofpilot.
- WordPress integration: 6.10.3.
- Canonical public page: https://inspector-roofing.com/droneproof/.
- Public source repository: https://github.com/RichNass87/inspector-droneproof.
- Project concept DOI: 10.5281/zenodo.21301425.
- Archived v0.5.0 software DOI: 10.5281/zenodo.21301426.

The concept DOI is the cross-platform project identifier. The v0.5.0 DOI identifies the already published Zenodo archive and is not represented as the version DOI for v0.6.0. This distinction prevents a newer GitHub release from being incorrectly described as the older archived artifact.

## 3. Architecture

[[SYSTEM_FLOW_FIGURE]]

The Android client can load a fallback mission or request an authorized field job from WordPress. It can capture or import roof photographs, preserve a photo manifest, and send authorized job data and images to the configured WordPress routes. The WordPress component provides job storage, roof-data intake, field-photo upload, flight-plan generation, report-data access, and packet-output functions. The public API namespace exposes route definitions, while protected operations require the configured authorization controls.

The public source includes a dry-run DJI bridge and an example integration boundary. It does not include the proprietary DJI SDK binaries or an operational aircraft adapter. Generated waypoints, KML, CSV, mission JSON, and checklists are planning references. They require independent review in approved flight software before use.

## 4. Evidence Model and Human Review

The evidence model uses explicit identifiers and relationships rather than treating an image folder as a complete inspection file. A documentation packet may connect:

- job identifier and authorized property context;
- mission identifier and capture-plan version;
- roof-plane or slope identifier;
- photo identifier and capture metadata;
- marker identifier, location, type, and reviewer note;
- QA status and human reviewer;
- export version and checksum record.

Every substantive interpretation remains subject to human review. Software can preserve labels, timestamps, and relationships, but it cannot determine causation, repairability, code compliance, engineering conclusions, policy coverage, or payment. The public synthetic packet demonstrates data relationships only. It contains no customer address, customer image, claim identifier, aircraft telemetry, token, or private credential.

## 5. Privacy, Security, and Data Boundaries

Authorized workflows may process property and job details, addresses, roof photographs, photo metadata, roof-plane labels, annotations, field notes, mission-planning values, and checklist status. The configured device may retain the WordPress endpoint and a private field-access token. Public repositories and datasets must exclude customer photographs, addresses, insurance documents, claim files, API keys, signing material, field tokens, and other personally identifying information.

The public privacy policy describes use, service providers, retention, deletion requests, security limits, and child-directed-use boundaries. If enabled by the administrator, address data may be sent to Google Maps Platform for geocoding and selected documentation text may be sent to the configured OpenAI service for QA assistance. These optional services must be reconciled with the deployed configuration and applicable provider terms.

## 6. Integrity and Reproducibility

The v0.6.0 source package includes a release manifest, changelog, methodology, software citation metadata, code and documentation licenses, synthetic evidence sample, SHA-256 checksum ledger, and executable internal validator. The validator checks identity consistency, required files, privacy documentation, safety language, checksum coverage, and prohibited overclaims.

The validator score measures internal package completeness. It does not measure flight readiness, software security in every deployment, Google Play approval, DJI approval, search ranking, Wikidata notability, professional competence, or independent certification. Reproduction requires the public source release, Node.js 18 or later for package checks, the documented Android build environment, and a separately configured WordPress deployment.

## 7. Public Distribution and Source Spine

At the date of this paper, the following states are recorded:

- Amazon Appstore: the publisher received a July 16, 2026 notice stating that Android version 0.4.0-droneproof was live after Primary, Content Policy, and Functionality validation passed. No stable public storefront URL is asserted in this paper.
- Google Play: no public production listing is claimed. A public store URL for the package was not live when checked.
- DJI: developer application and package configuration were reported active in the DJI developer console. This is development provenance, not DJI certification, public distribution, or endorsement.
- GitHub: the repository is the versioned source and release layer.
- Zenodo: the concept DOI provides the persistent project relationship; the existing v0.5.0 record remains a version-specific archive.
- Hugging Face: the dataset page hosts synthetic, non-private evidence examples and schema documentation.
- ORCID: the creator profile links Richard Amir Nasser to the archived software work and DOI.
- Academia.edu: this paper is a descriptive distribution layer and not independent validation.
- WordPress: the canonical page, privacy policy, terms, application schema, and protected field-service routes form the deployed application surface.

## 8. Flight, Platform, and Insurance Boundaries

Inspector DroneProof does not include DJI SDK binaries and does not arm, launch, or command an aircraft. A generated route is not flight authorization. The pilot remains responsible for certification, aircraft registration, airspace, property permission, weather, visual line of sight, people, obstacles, battery state, return-to-home settings, controller and aircraft compatibility, and final mission approval.

Inspector DroneProof is independently developed and is not affiliated with, sponsored by, certified by, or endorsed by DJI, Google, Amazon, GitHub, Zenodo, Hugging Face, ORCID, Academia.edu, or OpenAI.

Inspector Roofing documents observable roof conditions and organizes contractor documentation. It does not act as a public adjuster. Insurance carriers decide coverage, payment, and claim outcomes. The system does not interpret policy language, promise approval, determine causation, or guarantee that a reviewer will accept an evidence packet.

## 9. Limitations and Next Validation Work

The current release has material limitations:

- no operational DJI SDK adapter;
- no hardware-validated aircraft-control workflow;
- no public Google Play production listing;
- no claim that the public API routes prove successful production transactions;
- no independent peer review or external certification of this paper;
- no standalone Wikidata item supported by independent item-specific notability evidence;
- no guarantee that a synthetic packet represents a particular roof, storm, or loss.

Appropriate next work includes real-device Android testing, privacy-safe end-to-end transaction tests, a Play pre-launch report before any Play production claim, hardware and safety validation before aircraft integration, a version-specific Zenodo archive for v0.6.0, and independent technical review where available.

## 10. Conclusion

Inspector DroneProof is best understood as a human-reviewed documentation architecture. Its value depends on disciplined identity, version, privacy, safety, and evidence boundaries rather than on unsupported automation claims. The public source spine makes the software inspectable across a canonical page, GitHub, Zenodo, Hugging Face, ORCID, and this descriptive paper. Persistent identifiers improve traceability, but they do not replace property-specific evidence, professional judgment, platform review, or independent verification.

## References

1. Inspector DroneProof project concept DOI. https://doi.org/10.5281/zenodo.21301425

2. Nasser, Richard Amir. Inspector DroneProof v0.5.0: Android roof documentation software proof package. Zenodo. https://doi.org/10.5281/zenodo.21301426

3. Inspector DroneProof source repository. https://github.com/RichNass87/inspector-droneproof

4. Inspector DroneProof v0.6.0 source release. https://github.com/RichNass87/inspector-droneproof/releases/tag/v0.6.0

5. Inspector DroneProof Evidence Samples. Hugging Face. https://huggingface.co/datasets/InspectorRoofing/inspector-droneproof-evidence-samples

6. Richard Amir Nasser ORCID record. https://orcid.org/0009-0000-2980-7543

7. Inspector DroneProof canonical page. https://inspector-roofing.com/droneproof/

8. Inspector DroneProof Privacy Policy. https://inspector-roofing.com/privacy-policy/

9. Inspector DroneProof Terms of Use. https://inspector-roofing.com/terms/
