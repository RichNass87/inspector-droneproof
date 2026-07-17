import { createHash } from "node:crypto";
import { readdirSync, readFileSync, statSync, writeFileSync } from "node:fs";
import { dirname, join, relative, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const excludedDirectories = new Set([".git", ".gradle", "build", "node_modules", "outputs"]);
const excludedFiles = new Set([
  "CHECKSUMS.sha256",
  "release-readiness-report.json",
  "release-readiness-report.md",
  "local.properties",
  "docs/sample-photo-damage-report.pdf",
  "wordpress-plugin/assets/inspector-droneproof-pilot-debug.apk",
]);

function walk(directory) {
  return readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
    if (entry.isDirectory() && excludedDirectories.has(entry.name)) return [];
    const absolute = join(directory, entry.name);
    if (entry.isDirectory()) return walk(absolute);
    const path = relative(root, absolute).replaceAll("\\", "/");
    if (excludedFiles.has(path) || excludedFiles.has(entry.name)) return [];
    if (!statSync(absolute).isFile()) return [];
    return [path];
  });
}

const lines = walk(root).sort().map(path => {
  const hash = createHash("sha256").update(readFileSync(join(root, path))).digest("hex");
  return `${hash}  ${path}`;
});

writeFileSync(join(root, "CHECKSUMS.sha256"), `${lines.join("\n")}\n`);
console.log(`Wrote ${lines.length} SHA-256 entries.`);
