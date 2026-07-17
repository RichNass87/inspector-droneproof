import { createHash } from "node:crypto";
import { existsSync, readFileSync, readdirSync, writeFileSync } from "node:fs";
import { dirname, extname, join, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const VERSION = "0.6.0";
const APP_VERSION = "0.4.0-droneproof";
const APP_VERSION_CODE = 4;
const WP_VERSION = "6.10.3";
const RELEASE_DATE = "2026-07-17";
const AUTHOR = "Richard Amir Nasser";
const PUBLISHER = "Inspector Roofing and Restoration";
const PACKAGE = "com.inspectorroofing.droneproofpilot";
const RELEASE_URL = "https://github.com/RichNass87/inspector-droneproof/releases/tag/v0.6.0";
const AAB_SHA256 = "0f929553ec1dc6e0eca72c2a64256fec6cbb4881294a376a02841985946cd61b";

const read = file => readFileSync(join(root, file), "utf8");
const json = file => JSON.parse(read(file));
const hasAll = (text, values) => values.every(value => text.includes(value));

const coreFiles = [
  "README.md",
  "CHANGELOG.md",
  "METHODOLOGY.md",
  "VALIDATOR.md",
  "SECURITY.md",
  "LICENSE",
  "LICENSE-DOCS.md",
  "CITATION.cff",
  "package.json",
  "release-manifest.json",
  "artifacts/android-artifact.json",
  "docs/PRIVACY.md",
  "docs/TERMS.md",
  "docs/DATA-SAFETY.md",
  "docs/TESTING.md",
  "docs/DISTRIBUTION-EVIDENCE.md",
  "docs/RELEASE-NOTES-v0.6.0.md",
  "docs/proof-source-map.md",
  "examples/anonymized-evidence-packet/README.md",
  "examples/anonymized-evidence-packet/inspection.json",
  "metadata/software-identity.jsonld",
  "metadata/wikidata-statement-plan.md",
  "android-app/app/build.gradle.kts",
  "android-app/app/src/main/AndroidManifest.xml",
  "android-app/app/src/main/java/com/inspectorroofing/droneproofpilot/DjiSdkBridge.kt",
  "android-app/app/src/main/java/com/inspectorroofing/droneproofpilot/MainActivity.kt",
  "android-app/app/src/main/java/com/inspectorroofing/droneproofpilot/WordPressDroneProofClient.kt",
  "wordpress-plugin/inspector-droneproof.php",
  "scripts/generate-checksums.mjs",
  "scripts/validate-release.mjs",
];

const missingCoreFiles = coreFiles.filter(file => !existsSync(join(root, file)));
const manifest = json("release-manifest.json");
const packageJson = json("package.json");
const artifact = json("artifacts/android-artifact.json");
const sample = json("examples/anonymized-evidence-packet/inspection.json");
const softwareSchema = json("metadata/software-identity.jsonld");

function textFiles(directory) {
  const excluded = new Set([".git", ".gradle", "build", "node_modules"]);
  const textExtensions = new Set([".md", ".txt", ".json", ".jsonld", ".cff", ".kts", ".kt", ".java", ".php", ".js", ".mjs", ".xml", ".yaml", ".yml"]);
  return readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
    if (entry.isDirectory() && excluded.has(entry.name)) return [];
    const absolute = join(directory, entry.name);
    if (entry.isDirectory()) return textFiles(absolute);
    if (!textExtensions.has(extname(entry.name)) && !["LICENSE"].includes(entry.name)) return [];
    return [relative(root, absolute).replaceAll("\\", "/")];
  });
}

const scanFiles = textFiles(root).filter(file => !file.startsWith("release-readiness-report"));
const publicText = scanFiles.map(file => read(file)).join("\n");
const retiredWikidataUrl = /https:\/\/www\.wikidata\.org\/wiki\/(?:Q140491550|Q140475713|Q140480476|Q140480722|Q140481799|Q140482857)/;
const privateSecret = /(?:AIza[0-9A-Za-z_-]{20,}|sk-[0-9A-Za-z_-]{20,}|-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----|RELEASE_STORE_PASSWORD\s*=\s*\S+|DJI_API_KEY\s*=\s*(?!YOUR_DJI_APP_KEY)\S+)/;
const directControlOverclaim = /(?:direct|live|real) (?:DJI )?(?:aircraft|drone) control (?:is )?(?:enabled|operational|ready|validated)/i;
const productionPlayOverclaim = /(?:live|available|published) (?:on|in) (?:the )?Google Play Store/i;

const report = {
  validator: "Inspector DroneProof internal release-readiness validator",
  validatorVersion: "1.0.0",
  evaluatedAt: new Date().toISOString(),
  packageVersion: VERSION,
  strongTargetScore: 91,
  independentCertification: false,
  categories: [],
  hardGates: [],
  findings: [],
};

function category(name, points, checks) {
  const passed = checks.filter(check => check.ok).length;
  const earned = Number(((passed / checks.length) * points).toFixed(2));
  report.categories.push({ name, pointsAvailable: points, pointsEarned: earned, checks });
  return earned;
}

const identityChecks = [
  {
    name: "Release manifest uses canonical identity and three distinct version fields",
    ok: manifest.title === "Inspector DroneProof" && manifest.releaseVersion === VERSION &&
      manifest.androidVersionName === APP_VERSION && manifest.androidVersionCode === APP_VERSION_CODE &&
      manifest.wordpressPluginVersion === WP_VERSION && manifest.releaseDate === RELEASE_DATE &&
      manifest.developer === AUTHOR && manifest.publisher === PUBLISHER && manifest.packageName === PACKAGE,
  },
  {
    name: "Package and citation metadata use the canonical author and release version",
    ok: packageJson.name === "inspector-droneproof" && packageJson.version === VERSION &&
      packageJson.author === AUTHOR && hasAll(read("CITATION.cff"), [
        `version: "${VERSION}"`, `date-released: "${RELEASE_DATE}"`,
        "given-names: \"Richard Amir\"", "family-names: \"Nasser\"",
      ]),
  },
  {
    name: "Android and WordPress source versions match the manifest",
    ok: hasAll(read("android-app/app/build.gradle.kts"), [
      `applicationId = "${PACKAGE}"`, `targetSdk = 35`, `versionCode = ${APP_VERSION_CODE}`,
      `versionName = "${APP_VERSION}"`,
    ]) && hasAll(read("wordpress-plugin/inspector-droneproof.php"), [
      `Version: ${WP_VERSION}`, `const VERSION = '${WP_VERSION}'`,
    ]),
  },
];

const sourceChecks = [
  {
    name: "Android source includes mission parsing, field client, UI, and manifest",
    ok: [
      "android-app/app/src/main/java/com/inspectorroofing/droneproofpilot/DroneProofMission.kt",
      "android-app/app/src/main/java/com/inspectorroofing/droneproofpilot/WordPressDroneProofClient.kt",
      "android-app/app/src/main/java/com/inspectorroofing/droneproofpilot/MainActivity.kt",
      "android-app/app/src/main/AndroidManifest.xml",
    ].every(file => existsSync(join(root, file))),
  },
  {
    name: "WordPress source and public assets are present",
    ok: existsSync(join(root, "wordpress-plugin/inspector-droneproof.php")) &&
      existsSync(join(root, "wordpress-plugin/assets/inspector-droneproof.js")) &&
      existsSync(join(root, "wordpress-plugin/assets/inspector-droneproof.css")),
  },
  {
    name: "AAB manifest records the exact package, version, target, and SHA-256",
    ok: artifact.packageName === PACKAGE && artifact.versionName === APP_VERSION &&
      artifact.versionCode === APP_VERSION_CODE && artifact.targetSdk === 35 && artifact.sha256 === AAB_SHA256,
  },
];

const methodologyChecks = [
  {
    name: "Methodology covers collection, review, privacy, credentials, insurance, and reproducibility",
    ok: hasAll(read("METHODOLOGY.md"), [
      "## Collection sequence", "## Review rules", "## Privacy and redaction",
      "## Credential handling", "## Insurance and public-adjuster boundary", "## Reproducibility",
    ]),
  },
  {
    name: "Synthetic sample is deidentified and cannot enable aircraft control",
    ok: sample.sample === true && sample.schemaVersion === VERSION && sample.property.address === "DEIDENTIFIED" &&
      sample.property.postalCode === "00000" && sample.mission.aircraftControlEnabled === false &&
      sample.mission.waypoints.every(item => item.latitude === 0 && item.longitude === 0),
  },
  {
    name: "Synthetic observation references resolve and avoid outcome claims",
    ok: (() => {
      const photoIds = new Set(sample.photos.map(item => item.photoId));
      return sample.observations.every(item => photoIds.has(item.photoId)) &&
        hasAll(sample.boundary.toLowerCase(), ["does not act as a public adjuster", "carriers decide coverage, payment, and claim outcomes"]);
    })(),
  },
];

const privacyChecks = [
  {
    name: "Privacy policy discloses address, photos, token, service providers, retention, deletion, and security",
    ok: hasAll(read("docs/PRIVACY.md"), [
      "Property and job details", "Roof photos", "field-access token", "Google Maps Platform",
      "OpenAI", "Retention and deletion", "does not sell", "Security",
    ]),
  },
  {
    name: "Data-safety worksheet distinguishes permissions, collection, sale, ads, and production reconciliation",
    ok: hasAll(read("docs/DATA-SAFETY.md"), [
      "Precise location", "Photos and files", "Device field token", "No advertising SDK",
      "No data-sale behavior", "final Play Data safety form",
    ]),
  },
  {
    name: "WordPress plugin creates or repairs privacy and terms pages",
    ok: hasAll(read("wordpress-plugin/inspector-droneproof.php"), [
      "PRIVACY_PAGE_OPTION", "TERMS_PAGE_OPTION", "ensure_compliance_pages",
      "privacy-policy", "privacy_page_content", "terms_page_content",
    ]),
  },
  {
    name: "Compliance pages are recorded as deployed only after live verification",
    ok: manifest.compliancePagesDeployed === true,
  },
];

function checksumState() {
  if (!existsSync(join(root, "CHECKSUMS.sha256"))) return { ok: false, detail: "CHECKSUMS.sha256 is missing" };
  const entries = new Map();
  for (const line of read("CHECKSUMS.sha256").trim().split("\n").filter(Boolean)) {
    const match = line.match(/^([a-f0-9]{64})  (.+)$/);
    if (!match) return { ok: false, detail: `Malformed checksum line: ${line}` };
    entries.set(match[2], match[1]);
  }
  for (const file of coreFiles) {
    if (!entries.has(file)) return { ok: false, detail: `Missing checksum entry: ${file}` };
    const actual = createHash("sha256").update(readFileSync(join(root, file))).digest("hex");
    if (actual !== entries.get(file)) return { ok: false, detail: `Stale checksum entry: ${file}` };
  }
  return { ok: true, detail: `${entries.size} checksum entries; all core entries verified` };
}

const checksum = checksumState();
const reproducibilityChecks = [
  {
    name: "All required release files are present",
    ok: missingCoreFiles.length === 0,
    detail: missingCoreFiles.join(", ") || "All core files present",
  },
  {
    name: "Code and documentation licensing are explicit",
    ok: read("LICENSE").includes("MIT License") && read("LICENSE-DOCS.md").includes("CC BY 4.0"),
  },
  {
    name: "SHA-256 ledger is current for every core file",
    ok: checksum.ok,
    detail: checksum.detail,
  },
];

const boundaryFiles = ["README.md", "METHODOLOGY.md", "docs/TERMS.md", "examples/anonymized-evidence-packet/README.md"];
const safetyChecks = [
  {
    name: "No retired Wikidata URL is presented as a live entity",
    ok: !retiredWikidataUrl.test(publicText),
  },
  {
    name: "No private secret pattern is present",
    ok: !privateSecret.test(publicText),
  },
  {
    name: "Direct aircraft control and Play production are not overclaimed",
    ok: !directControlOverclaim.test(publicText) && !productionPlayOverclaim.test(publicText) &&
      manifest.directAircraftControl === false && manifest.googlePlayProductionClaimed === false,
  },
  {
    name: "DJI independence and unimplemented adapter boundary are explicit",
    ok: hasAll(publicText, [
      "not affiliated with, sponsored by, certified by, or endorsed by DJI",
      "does not arm, launch, or command an aircraft",
    ]),
  },
  {
    name: "Insurance and public-adjuster boundary appears across the evidence spine",
    ok: boundaryFiles.every(file => {
      const text = read(file).toLowerCase();
      return text.includes("does not act as a public adjuster") &&
        text.includes("carriers decide coverage, payment, and claim outcomes");
    }),
  },
];

const publicationChecks = [
  {
    name: "Public v0.6.0 GitHub release is recorded only after publication",
    ok: manifest.releaseState === "published" && manifest.publicReleaseUrl === RELEASE_URL,
  },
];

let score = 0;
score += category("Identity and version consistency", 15, identityChecks);
score += category("Source and artifact evidence", 15, sourceChecks);
score += category("Methodology and synthetic sample", 15, methodologyChecks);
score += category("Privacy and data safety", 20, privacyChecks);
score += category("Reproducibility and integrity", 15, reproducibilityChecks);
score += category("Safety, platform, and insurance boundaries", 15, safetyChecks);
score += category("Published GitHub release", 5, publicationChecks);
report.score = Number(score.toFixed(2));

function hardGate(name, ok, detail) {
  report.hardGates.push({ name, ok, detail });
}

hardGate("Core files present", missingCoreFiles.length === 0, missingCoreFiles.join(", ") || "All core files present");
hardGate("Canonical identity and versions consistent", identityChecks.every(check => check.ok), "See identity checks");
hardGate("No retired Wikidata URLs", !retiredWikidataUrl.test(publicText), "Public text and metadata scanned");
hardGate("No private secrets", !privateSecret.test(publicText), "Public text and source scanned");
hardGate("No platform or aircraft-control overclaim", safetyChecks.slice(2, 4).every(check => check.ok), "See safety checks");
hardGate("Privacy and data-safety package complete", privacyChecks.slice(0, 3).every(check => check.ok), "See privacy checks");
hardGate("Compliance pages deployed", manifest.compliancePagesDeployed === true, "Set true only after both public URLs return their actual page content");
hardGate("Checksum ledger current", checksum.ok, checksum.detail);
hardGate("Insurance boundary complete", safetyChecks[4].ok, "Required evidence-spine files scanned");
hardGate("Strong target reached", report.score >= 91, `Score ${report.score}/100`);

const hardGatesPass = report.hardGates.every(gate => gate.ok);
report.status = hardGatesPass
  ? (manifest.releaseState === "published" ? "PASS" : "READY_FOR_PUBLICATION")
  : "RELEASE_HOLD";

for (const section of report.categories) {
  for (const check of section.checks) {
    if (!check.ok) report.findings.push(`${section.name}: ${check.name}${check.detail ? ` (${check.detail})` : ""}`);
  }
}
for (const gate of report.hardGates) {
  if (!gate.ok) report.findings.push(`Hard gate: ${gate.name} (${gate.detail})`);
}

writeFileSync(join(root, "release-readiness-report.json"), `${JSON.stringify(report, null, 2)}\n`);
const markdown = `# Inspector DroneProof release-readiness report\n\n` +
  `- Evaluated: ${report.evaluatedAt}\n- Package: ${VERSION}\n- Score: **${report.score}/100**\n- Status: **${report.status}**\n- Independent certification: **No**\n\n` +
  `## Categories\n\n| Category | Earned | Available |\n| --- | ---: | ---: |\n` +
  report.categories.map(item => `| ${item.name} | ${item.pointsEarned} | ${item.pointsAvailable} |`).join("\n") +
  `\n\n## Hard gates\n\n| Gate | Result | Detail |\n| --- | --- | --- |\n` +
  report.hardGates.map(item => `| ${item.name} | ${item.ok ? "PASS" : "FAIL"} | ${item.detail} |`).join("\n") +
  `\n\n## Findings\n\n${report.findings.length ? report.findings.map(item => `- ${item}`).join("\n") : "- None."}\n\n` +
  `This is an internal package-readiness result, not Play approval, DJI approval, Wikidata notability, search ranking, or independent certification.\n`;
writeFileSync(join(root, "release-readiness-report.md"), markdown);

console.log(`${report.status}: ${report.score}/100`);
if (!hardGatesPass) process.exitCode = 1;
