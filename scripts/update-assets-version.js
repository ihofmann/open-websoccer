const fs = require("fs");
const path = require("path");

const frontbasePath = path.resolve(
  __dirname,
  "..",
  "websoccer",
  "frontbase.inc.php",
);
const unixTimestamp = Math.floor(Date.now() / 1000);
const source = fs.readFileSync(frontbasePath, "utf8");
const updatedSource = source.replace(
  /(define\('ASSETS_VERSION',\s*'av)\d+('\);)/,
  `$1${unixTimestamp}$2`,
);

if (updatedSource === source) {
  throw new Error(
    "Could not update ASSETS_VERSION in websoccer/frontbase.inc.php",
  );
}

fs.writeFileSync(frontbasePath, updatedSource);
console.log(`Updated ASSETS_VERSION to av${unixTimestamp}`);
