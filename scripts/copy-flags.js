/**
 * Copies the country flags used by the frontend from the flag-icons npm package
 * into websoccer/img/flags, together with the package license and a copyright
 * note.
 *
 * Two flag sets are generated:
 *   languages/<language code>.svg - shown in the language switcher
 *   countries/<nationality>.svg   - shown wherever a player, club or league
 *                                   nationality is rendered
 *
 * Country file names intentionally match the untranslated nationality values
 * stored in the database (umlauts transliterated the same way as
 * PlayersDataService::getFlagFilename does), so templates can resolve a flag
 * from a nationality without an additional lookup.
 */
const fs = require("fs");
const path = require("path");

const rootDir = path.resolve(__dirname, "..");
const packageDir = path.join(rootDir, "node_modules", "flag-icons");
const sourceDir = path.join(packageDir, "flags", "4x3");
const flagsDir = path.join(rootDir, "websoccer", "img", "flags");
const languagesDir = path.join(flagsDir, "languages");
const countriesDir = path.join(flagsDir, "countries");

// Supported UI language (see the supported_languages setting) -> flag-icons id
// of the region whose flag represents that language.
const languageFlags = {
  de: "de",
  en: "gb",
  es: "es",
  it: "it",
};

// Supported nationality -> flag-icons id. Nationalities are the values of
// ws3_spieler.nation and ws3_liga.land plus the folder names below
// admin/config/names, which drive youth scouting.
const countryFlags = {
  Brasilien: "br",
  Deutschland: "de",
  England: "gb-eng",
  Frankreich: "fr",
  Italien: "it",
  Oesterreich: "at",
  Spanien: "es",
};

function readPackageMetadata() {
  const manifestPath = path.join(packageDir, "package.json");
  if (!fs.existsSync(manifestPath)) {
    throw new Error(
      "flag-icons is not installed. Run 'npm install' before building the flags.",
    );
  }

  const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));
  return {
    name: manifest.name,
    version: manifest.version,
    license: manifest.license,
    repository: manifest.repository.url.replace(/^git\+|\.git$/g, ""),
  };
}

function recreateDirectory(directory) {
  fs.rmSync(directory, { recursive: true, force: true });
  fs.mkdirSync(directory, { recursive: true });
}

function copyFlags(flags, targetDirectory) {
  recreateDirectory(targetDirectory);

  for (const [targetName, flagId] of Object.entries(flags)) {
    const sourcePath = path.join(sourceDir, `${flagId}.svg`);
    if (!fs.existsSync(sourcePath)) {
      throw new Error(
        `flag-icons does not provide a flag with id "${flagId}" (required for "${targetName}").`,
      );
    }
    fs.copyFileSync(sourcePath, path.join(targetDirectory, `${targetName}.svg`));
  }

  return Object.keys(flags).length;
}

function writeAttribution(metadata) {
  fs.copyFileSync(
    path.join(packageDir, "LICENSE"),
    path.join(flagsDir, "LICENSE.txt"),
  );

  const copyright = fs
    .readFileSync(path.join(packageDir, "LICENSE"), "utf8")
    .split(/\r?\n/)
    .find((line) => line.startsWith("Copyright"));

  if (!copyright) {
    throw new Error(
      `Could not find a copyright line in the ${metadata.name} license.`,
    );
  }

  const note = `Country flag images in this folder are generated files. Do not edit them by
hand; run "npm run build:flags" instead. Add or remove countries in
scripts/copy-flags.js.

    Source:    ${metadata.name} ${metadata.version}
               ${metadata.repository}
    License:   ${metadata.license} (full text in LICENSE.txt)
    ${copyright}

Folder layout:

    languages/<language code>.svg - flag per supported UI language
    countries/<nationality>.svg   - flag per supported nationality, named after
                                    the untranslated nationality value stored in
                                    the database (umlauts transliterated, e.g.
                                    "Österreich" => "Oesterreich")
`;

  fs.writeFileSync(path.join(flagsDir, "README.txt"), note);
}

function main() {
  const metadata = readPackageMetadata();

  fs.mkdirSync(flagsDir, { recursive: true });
  const languageCount = copyFlags(languageFlags, languagesDir);
  const countryCount = copyFlags(countryFlags, countriesDir);
  writeAttribution(metadata);

  console.log(
    `Copied ${languageCount} language and ${countryCount} country flags from ` +
      `${metadata.name} ${metadata.version} to ${path.relative(rootDir, flagsDir)}`,
  );
}

main();
