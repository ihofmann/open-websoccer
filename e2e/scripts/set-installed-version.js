const fs = require("fs");
const path = require("path");

/**
 * Sets the `installed_version` entry in the prepared E2E application config
 * (e2e/docker/generated/config.inc.php) to the version shipped in
 * websoccer/admin/config/version.txt.
 *
 * The E2E database is seeded with the schema of exactly this repository
 * version, so the config must mark that version as installed. Otherwise the
 * /update wizard would not show its "Update already performed" notice
 * (verified by e2e/tests/update.spec.ts).
 *
 * Called by the E2E run scripts (run-e2e.sh / run-e2e.ps1) after the config
 * template has been copied.
 */

const repoRoot = path.resolve(__dirname, "..", "..");
const versionFile = path.join(
  repoRoot,
  "websoccer",
  "admin",
  "config",
  "version.txt",
);
const configFile = path.join(
  repoRoot,
  "e2e",
  "docker",
  "generated",
  "config.inc.php",
);

if (!fs.existsSync(configFile)) {
  console.error(`Config file not found: ${configFile}`);
  console.error(
    "Run this after the config template has been copied (see e2e/README.md).",
  );
  process.exit(1);
}

const version = fs.readFileSync(versionFile, "utf8").trim();
if (!version) {
  console.error(`Version file is empty: ${versionFile}`);
  process.exit(1);
}

const source = fs.readFileSync(configFile, "utf8");
// Keep the config file free of version duplication: the template carries a
// snapshot value which is replaced here with the actual shipped version.
const entryPattern = /^(\$conf\['installed_version'\] = ")[^"]*(";)(\r?)$/m;
if (!entryPattern.test(source)) {
  console.error(
    "Could not find the installed_version entry in the E2E application config.",
  );
  console.error(
    "The config template e2e/docker/config.template.inc.php must contain the entry.",
  );
  process.exit(1);
}

fs.writeFileSync(
  configFile,
  source.replace(entryPattern, `$1${version}$2$3`),
);
console.log(`    installed_version set to ${version}`);
