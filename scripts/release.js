const archiver = require("archiver");
const fs = require("fs");
const path = require("path");
const { spawnSync } = require("child_process");

const rootDir = path.resolve(__dirname, "..");
const applicationDir = path.join(rootDir, "websoccer");
const releaseRoot = path.join(rootDir, "release");
clearDirectory(releaseRoot);
const versionFile = path.join(applicationDir, "admin", "config", "version.txt");
const version = (
  process.env.RELEASE_VERSION || fs.readFileSync(versionFile, "utf8").trim()
).trim();

if (!version || /[<>:"/\\|?*\x00-\x1f]/.test(version)) {
  throw new Error(`Invalid release version: "${version}"`);
}

const packageName = `open-websoccer-${version}`;
const releaseDir = path.join(releaseRoot, "websoccer");
const archivePath = path.join(releaseRoot, `${packageName}.zip`);

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    cwd: rootDir,
    stdio: "inherit",
    shell: process.platform === "win32",
    ...options,
  });

  if (result.error) {
    throw result.error;
  }
  if (result.status !== 0) {
    throw new Error(`${command} failed with exit code ${result.status}`);
  }
}

function installComposerDependencies() {
  const composerArgs = [
    "install",
    "--no-dev",
    "--optimize-autoloader",
    "--working-dir=websoccer",
  ];
  const composer =
    process.platform === "win32" &&
    fs.existsSync(path.join(rootDir, "composer.phar"))
      ? "php"
      : "composer";
  const args =
    composer === "php" ? ["composer.phar", ...composerArgs] : composerArgs;
  run(composer, args);
}

function clearDirectory(directory) {
  fs.mkdirSync(directory, { recursive: true });
  for (const entry of fs.readdirSync(directory)) {
    fs.rmSync(path.join(directory, entry), { recursive: true, force: true });
  }
}

const applicationDirectories = [
  "admin",
  "assets",
  "classes",
  "img",
  "install",
  "modules",
  "templates",
  "update",
  "uploads",
  "vendor",
  "webservices",
];
const applicationFiles = [
  "ajax.php",
  "favicon.ico",
  "frontbase.inc.php",
  "index.php",
];

function copyDirectory(source, destination) {
  fs.mkdirSync(destination, { recursive: true });
  for (const entry of fs.readdirSync(source, { withFileTypes: true })) {
    const sourcePath = path.join(source, entry.name);
    const destinationPath = path.join(destination, entry.name);
    if (entry.isDirectory()) {
      copyDirectory(sourcePath, destinationPath);
    } else {
      fs.copyFileSync(sourcePath, destinationPath);
    }
  }
}

function emptyFiles(directory) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const entryPath = path.join(directory, entry.name);
    if (entry.isDirectory()) {
      emptyFiles(entryPath);
    } else {
      fs.rmSync(entryPath, { force: true });
    }
  }
}

function copyApplication(source, destination) {
  for (const directory of applicationDirectories) {
    copyDirectory(
      path.join(source, directory),
      path.join(destination, directory),
    );
  }
  for (const file of applicationFiles) {
    fs.copyFileSync(path.join(source, file), path.join(destination, file));
  }
  emptyFiles(path.join(destination, "uploads"));
}

function createArchive() {
  return new Promise((resolve, reject) => {
    const output = fs.createWriteStream(archivePath);
    const archive = archiver("zip", { zlib: { level: 9 } });

    output.on("close", resolve);
    output.on("error", reject);
    archive.on("error", reject);
    archive.pipe(output);
    archive.directory(releaseDir, packageName);
    archive.finalize();
  });
}

async function main() {
  console.log(`Building ${packageName}...`);
  run("npm", ["run", "build"]);

  installComposerDependencies();

  fs.mkdirSync(releaseDir, { recursive: true });
  copyApplication(applicationDir, releaseDir);
  fs.writeFileSync(
    path.join(releaseDir, "admin", "config", "version.txt"),
    `${version}\n`,
  );
  await createArchive();
  console.log(`Release written to ${path.relative(rootDir, releaseDir)}`);
  console.log(`Archive written to ${path.relative(rootDir, archivePath)}`);
}

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
