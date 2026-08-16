#requires -Version 5.1
<#
.SYNOPSIS
    Starts the E2E Docker stack (with a pre-populated database) and runs the
    Playwright E2E tests.

.PARAMETER Keep
    Keep the Docker containers running after the tests finish (useful for
    re-running tests quickly). By default the stack is torn down.

.PARAMETER NoBuildAssets
    Skip the frontend asset build (npm run build). Use this only if the
    assets in websoccer/assets are already up to date.

.EXAMPLE
    ./e2e/run-e2e.ps1
.EXAMPLE
    ./e2e/run-e2e.ps1 -Keep
#>
param(
    [switch]$Keep,
    [switch]$NoBuildAssets
)

# 'Continue' (not 'Stop') on purpose: docker compose and the mysql client write
# progress/warnings to stderr, which PowerShell would otherwise escalate into a
# terminating error as soon as the script output is piped somewhere. Failures
# are detected explicitly via $LASTEXITCODE instead.
$ErrorActionPreference = 'Continue'

# Resolve repository root (parent of the e2e/ folder this script lives in).
$repoRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$e2eDir   = Join-Path $repoRoot 'e2e'
$compose  = Join-Path $e2eDir 'docker-compose.e2e.yml'
$baseUrl  = 'http://localhost:8081'

# Number of players the seed creates: 40 teams x 12 position_main x 2.
$expectedPlayers = 960

# NOTE: the parameter must NOT be called $args - that is a PowerShell
# automatic variable and the passed values would be silently discarded.
function Invoke-Compose([string[]]$ComposeArgs) {
    & docker compose -f $compose @ComposeArgs
    if ($LASTEXITCODE -ne 0) { throw "docker compose $($ComposeArgs -join ' ') failed (exit $LASTEXITCODE)" }
}

Write-Host '==> Building frontend assets (npm run build)' -ForegroundColor Cyan
if (-not $NoBuildAssets) {
    Push-Location $repoRoot
    try {
        & npm install
        if ($LASTEXITCODE -ne 0) { throw 'npm install failed' }
        & npm run build
        if ($LASTEXITCODE -ne 0) { throw 'npm run build failed' }
    } finally { Pop-Location }
} else {
    Write-Host '    (skipped)' -ForegroundColor DarkGray
}

Write-Host '==> Tearing down any existing E2E stack' -ForegroundColor Cyan
Invoke-Compose @('down', '-v', '--remove-orphans')

# The application appends all module setting defaults to config.inc.php on its
# first request, so always start from a pristine copy of the template.
Write-Host '==> Preparing the application config' -ForegroundColor Cyan
$generatedDir = Join-Path $e2eDir 'docker/generated'
New-Item -ItemType Directory -Force -Path $generatedDir | Out-Null
Copy-Item -Force (Join-Path $e2eDir 'docker/config.template.inc.php') `
                 (Join-Path $generatedDir 'config.inc.php')

Write-Host '==> Building and starting the E2E stack' -ForegroundColor Cyan
Invoke-Compose @('up', '-d', '--build')

# The MySQL entrypoint reports "healthy" while it is still importing the init
# scripts, and the application answers with HTTP 200 even when it cannot reach
# the database. So wait for the seed itself to be complete.
Write-Host '==> Waiting for the database seed to complete' -ForegroundColor Cyan
$seeded = $false
# While the seed is still running the mysql client writes to stderr and exits
# non-zero. Both are expected here.
for ($i = 0; $i -lt 90; $i++) {
    # MYSQL_PWD avoids the "password on the command line" warning.
    $count = & docker compose -f $compose exec -T -e MYSQL_PWD=websoccer db `
        mysql -uwebsoccer websoccer -N -B -e 'SELECT COUNT(*) FROM ws3_spieler' 2>$null
    if ($LASTEXITCODE -eq 0 -and "$count".Trim() -eq "$expectedPlayers") { $seeded = $true; break }
    Start-Sleep -Seconds 2
}
if (-not $seeded) {
    Invoke-Compose @('logs', 'db')
    throw "Database was not seeded with $expectedPlayers players in time."
}
Write-Host "    Database seeded ($expectedPlayers players)." -ForegroundColor Green

Write-Host '==> Waiting for the web container to become ready' -ForegroundColor Cyan
$ready = $false
for ($i = 0; $i -lt 60; $i++) {
    try {
        $resp = Invoke-WebRequest -Uri "$baseUrl/?page=login" -UseBasicParsing `
            -TimeoutSec 5 -ErrorAction SilentlyContinue
        if ($resp.StatusCode -eq 200 -and $resp.Content -notmatch 'data base is currently not available') {
            $ready = $true; break
        }
    } catch {
        # container not accepting connections yet
    }
    Start-Sleep -Seconds 2
}
if (-not $ready) {
    Invoke-Compose @('logs', 'web')
    throw 'Web container did not become ready in time.'
}
Write-Host '    Web container is ready.' -ForegroundColor Green

Write-Host '==> Installing Playwright dependencies' -ForegroundColor Cyan
Push-Location $e2eDir
try {
    & npm install
    if ($LASTEXITCODE -ne 0) { throw 'npm install (e2e) failed' }
    & npx playwright install chromium
    if ($LASTEXITCODE -ne 0) { throw 'playwright install failed' }

    Write-Host '==> Running E2E tests' -ForegroundColor Cyan
    & npx playwright test
    $testExit = $LASTEXITCODE
} finally { Pop-Location }

if ($Keep) {
    Write-Host '==> Keeping the stack running (-Keep). Tear down with: docker compose -f e2e/docker-compose.e2e.yml down -v' -ForegroundColor Yellow
} else {
    Write-Host '==> Tearing down the E2E stack' -ForegroundColor Cyan
    Invoke-Compose @('down', '-v', '--remove-orphans')
}

if ($testExit -eq 0) {
    Write-Host '==> E2E tests passed' -ForegroundColor Green
} else {
    Write-Host "==> E2E tests failed (exit $testExit)" -ForegroundColor Red
}
exit $testExit
