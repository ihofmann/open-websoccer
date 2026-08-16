#!/usr/bin/env bash
#
# Starts the E2E Docker stack (with a pre-populated database) and runs the
# Playwright E2E tests.
#
# Usage:
#   ./e2e/run-e2e.sh            # build, run tests, tear down
#   ./e2e/run-e2e.sh --keep     # keep the stack running afterwards
#   ./e2e/run-e2e.sh --no-build-assets   # skip the frontend asset build
#
set -euo pipefail

KEEP=0
NO_BUILD_ASSETS=0
for arg in "$@"; do
  case "$arg" in
    --keep) KEEP=1 ;;
    --no-build-assets) NO_BUILD_ASSETS=1 ;;
    *) echo "Unknown argument: $arg" >&2; exit 2 ;;
  esac
done

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
E2E_DIR="$REPO_ROOT/e2e"
COMPOSE="$E2E_DIR/docker-compose.e2e.yml"
BASE_URL='http://localhost:8081'

# Number of players the seed creates: 40 teams x 12 position_main x 2.
EXPECTED_PLAYERS=960

compose() { docker compose -f "$COMPOSE" "$@"; }

echo '==> Building frontend assets (npm run build)'
if [ "$NO_BUILD_ASSETS" -eq 0 ]; then
  ( cd "$REPO_ROOT" && npm install && npm run build )
else
  echo '    (skipped)'
fi

echo '==> Tearing down any existing E2E stack'
compose down -v --remove-orphans

# The application appends all module setting defaults to config.inc.php on its
# first request, so always start from a pristine copy of the template.
echo '==> Preparing the application config'
mkdir -p "$E2E_DIR/docker/generated"
cp -f "$E2E_DIR/docker/config.template.inc.php" \
      "$E2E_DIR/docker/generated/config.inc.php"

echo '==> Building and starting the E2E stack'
compose up -d --build

# The MySQL entrypoint reports "healthy" while it is still importing the init
# scripts, and the application answers with HTTP 200 even when it cannot reach
# the database. So wait for the seed itself to be complete.
echo '==> Waiting for the database seed to complete'
seeded=0
for _ in $(seq 1 90); do
  # MYSQL_PWD avoids the "password on the command line" warning.
  count=$(compose exec -T -e MYSQL_PWD=websoccer db mysql -uwebsoccer websoccer -N -B \
            -e 'SELECT COUNT(*) FROM ws3_spieler' 2>/dev/null | tr -d '[:space:]' || true)
  if [ "$count" = "$EXPECTED_PLAYERS" ]; then seeded=1; break; fi
  sleep 2
done
if [ "$seeded" -ne 1 ]; then
  compose logs db
  echo "Database was not seeded with $EXPECTED_PLAYERS players in time." >&2
  exit 1
fi
echo "    Database seeded ($EXPECTED_PLAYERS players)."

echo '==> Waiting for the web container to become ready'
ready=0
for _ in $(seq 1 60); do
  if curl -sf "$BASE_URL/?page=login" 2>/dev/null | grep -qv 'data base is currently not available'; then
    ready=1; break
  fi
  sleep 2
done
if [ "$ready" -ne 1 ]; then
  compose logs web
  echo 'Web container did not become ready in time.' >&2
  exit 1
fi
echo '    Web container is ready.'

echo '==> Installing Playwright dependencies'
( cd "$E2E_DIR" && npm install && npx playwright install chromium )

echo '==> Running E2E tests'
set +e
( cd "$E2E_DIR" && npx playwright test )
test_exit=$?
set -e

if [ "$KEEP" -eq 1 ]; then
  echo '==> Keeping the stack running (--keep). Tear down with: docker compose -f e2e/docker-compose.e2e.yml down -v'
else
  echo '==> Tearing down the E2E stack'
  compose down -v --remove-orphans
fi

exit "$test_exit"
