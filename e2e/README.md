# End-to-End tests (Playwright)

This folder contains the End-to-End test setup for OpenWebSoccer-Sim.

## Approach

The E2E suite runs the real application stack in Docker and drives a browser
through it with [Playwright](https://playwright.dev/). No application code is
mocked.

```
            ┌────────────────────────┐         ┌──────────────────────┐
 Playwright │  Chromium (test specs)  │  HTTP   │  websoccer-e2e-web   │
  runner  ─▶│                         │────────▶│  Apache + PHP 8.5     │
            └────────────────────────┘         └──────────┬───────────┘
                                                          │ mysqli
                                               ┌──────────▼───────────┐
                                               │  websoccer-e2e-db    │
                                               │  MySQL 8.0           │
                                               │  (pre-populated)     │
                                               └──────────────────────┘
```

`docker-compose.e2e.yml` starts two containers:

| Container           | Image                       | Port mapping | Purpose                                 |
| ------------------- | --------------------------- | ------------ | --------------------------------------- |
| `websoccer-e2e-web` | `open-websoccer-e2e:php8.5` | `8081:80`    | Apache + PHP 8.5 application            |
| `websoccer-e2e-db`  | `mysql:8.0`                 | `3308:3306`  | MySQL 8.0, pre-populated on first start |

The host ports are deliberately **different from the development stack**
(`docker-compose.yml` uses `8080` / `3307`), so both stacks can run at the
same time.

### Pre-populated database

The MySQL container is seeded automatically on first start (i.e. while the data
volume is still empty). Two SQL files are mounted into
`/docker-entrypoint-initdb.d`, which the MySQL entrypoint runs in alphabetical
order:

1. `01-schema.sql` &rarr; `websoccer/install/ws3_ddl_full.sql` &mdash; the full
   schema (tables, foreign keys and the default match-report texts). The very
   same file the installer uses, so the schema can never drift.
2. `02-seed.sql` &rarr; [`seed/seed_data.sql`](seed/seed_data.sql) &mdash; the
   sample data:

   * **1 admin user** &mdash; AdminCenter login `admin` / `admin`.
   * **5 frontend users** &mdash; login `user1`..`user5` / `user1`..`user5`.
   * **2 leagues**, each with **20 teams** (40 teams total). Teams 1&ndash;5
     are managed by `user1`..`user5`.
   * **960 players** &mdash; every team has **2 players for every
     `position_main`**. The `position_main` &rarr; `position` mapping used is:

     | position_main              | position    |
     | -------------------------- | ----------- |
     | `T`                        | Torwart     |
     | `LV`, `IV`, `RV`           | Abwehr      |
     | `LM`, `DM`, `ZM`, `OM`, `RM` | Mittelfeld  |
     | `LS`, `MS`, `RS`           | Sturm       |

   * A few rows for (almost) every other table in the schema (stadions,
     sponsors, trainings, cups, seasons, youth players, badges, transfers,
     finances, notifications, etc.).
   * **24 match records** (`ws3_spiel`):
     * 10 completed league matches for **matchday 1** of League 1, Season 1
       (all 20 teams play, `berechnet = 1`).
     * 10 future league matches for **matchday 2**, scheduled ~10 years ahead
       (`berechnet = 0`) so tests remain stable for years.
     * 1 completed + 1 future **cup match** ("Demo Cup", "First Round").
     * 1 completed + 1 scheduled **friendly** for today.
   * **Team season statistics** (`ws3_verein.sa_*` columns) updated for all
     20 League 1 teams to reflect the matchday 1 results, so the league table
     renders meaningful standings.
   * **Player season statistics** (`ws3_spieler.sa_tore`, `sa_assists`,
     `sa_spiele`) set for 6 strikers, so the Top Scorers and Top Strikers
     pages return ranked players.
   * **League history** (`ws3_leaguehistory`) for all 20 teams after matchday
     1, so the Table History chart has data.

> Passwords are hashed in SQL with `SHA2` using exactly the same scheme as
> `SecurityUtil::hashPassword`: `sha256( salt . sha256( password ) )`.

### Skipping the interactive installer

The run scripts copy
[`docker/config.template.inc.php`](docker/config.template.inc.php) to
`docker/generated/config.inc.php`, which is bind-mounted into the web container
as `/var/www/html/generated/config.inc.php`. The interactive installer at
`/install/` is therefore never needed. The config points the app at the `db`
service and sets `login_type = username`, so the seeded users can log in with
their nickname.

The copy is deliberate: on its first request the application appends the default
value of every module setting to `config.inc.php`. `docker/generated/` is
git-ignored for that reason, while the template stays pristine.

## Prerequisites

* **Docker** 20.10+ with the **Docker Compose v2** plugin (`docker compose`).
  On Windows / macOS use Docker Desktop.
* **Node.js** 18+ (npm included) &mdash; for building the browser assets and
  running Playwright.
* The ports **8081** (web) and **3308** (MySQL) must be free on the host. The
  development stack does *not* need to be stopped, it uses `8080` / `3307`.

## Running the tests

The easiest way is the helper script, which builds the assets, brings up the
stack, waits for it to become ready, installs Playwright and runs the tests:

```bash
# Linux / macOS / Git Bash
./e2e/run-e2e.sh

# Windows PowerShell
./e2e/run-e2e.ps1
```

Options:

| Flag (`run-e2e.sh`)     | Parameter (`run-e2e.ps1`) | Meaning                                                       |
| ----------------------- | ------------------------- | ------------------------------------------------------------- |
| `--keep`                | `-Keep`                   | Keep the stack running after the tests (faster re-runs).      |
| `--no-build-assets`     | `-NoBuildAssets`          | Skip `npm run build` (use if `websoccer/assets` is current).  |

When run without `--keep` / `-Keep` the script tears the stack down
(including the database volume) afterwards, so the next run starts from a
clean, freshly seeded state.

### Running the steps manually

```bash
# 1. Build the frontend assets (only needed once / after asset changes)
npm install && npm run build

# 1b. Put a pristine application config in place (required before every run)
mkdir -p e2e/docker/generated
cp -f e2e/docker/config.template.inc.php e2e/docker/generated/config.inc.php

# 2. Start the E2E stack (the DB seed takes a few seconds on first start)
docker compose -f e2e/docker-compose.e2e.yml up -d --build

# 2b. Verify the seed finished (must print 960)
docker compose -f e2e/docker-compose.e2e.yml exec -T db \
    mysql -uwebsoccer -pwebsoccer websoccer -N -B -e 'SELECT COUNT(*) FROM ws3_spieler'

# 3. Install Playwright (once)
cd e2e && npm install && npx playwright install chromium && cd ..

# 4. Run the tests
cd e2e && npx playwright test

# 5. Tear down (also removes the DB volume so the seed re-runs next time)
docker compose -f e2e/docker-compose.e2e.yml down -v
```

While the stack is up you can also browse it manually:

* Frontend: <http://localhost:8081/> (log in as `user1` / `user1`)
* AdminCenter: <http://localhost:8081/admin/> (log in as `admin` / `admin`)

## Sample tests

| File                               | Scenario                                                                          |
| ---------------------------------- | --------------------------------------------------------------------------------- |
| `tests/admin-news.spec.ts`         | `admin` signs in at the AdminCenter, publishes a news article, edits it and deletes it. |
| `tests/my-team.spec.ts`            | `user1` logs in to the frontend, opens **My Team** and verifies that his 24 players are listed. |
| `tests/league-table.spec.ts`       | Guest opens the **Leagues** page, verifies the league selector, the 20-team standings with correct stats, table markers, and league switching. |
| `tests/results.spec.ts`            | Guest opens **Results and Schedules**, verifies the Leagues/Cups/Latest-Results tabs, matchday results, and future match schedules. |
| `tests/match-details.spec.ts`      | Guest opens **Match Details** for a completed league match, a completed cup match, a future (unscheduled) match, and an invalid match id. |
| `tests/top-scorers.spec.ts`        | Guest opens **Top Scorers** and **Top Strikers**, verifies player rankings, tie-breaker ordering, and league filtering. |
| `tests/todays-matches.spec.ts`     | Guest opens **Today's Matches**, verifies completed and scheduled friendly matches. |
| `tests/table-history.spec.ts`      | Guest opens **Table History** for a team, verifies the chart data-series and back-to-league link. |

## Re-seeding the database

The seed only runs on an **empty** MySQL data volume. To force a fresh seed,
remove the volume and start again:

```bash
docker compose -f e2e/docker-compose.e2e.yml down -v
docker compose -f e2e/docker-compose.e2e.yml up -d
```
