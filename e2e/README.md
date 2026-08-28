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
   * **1 admin user for lockout tests** &mdash; login `locktest` / `locktest` (used only by the 2FA lockout test so it cannot interfere with other tests).
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
     * 1 completed + 1 future **national team friendly** (England vs
       Deutschland).
   * **3 national teams** (IDs 41-43): "England" (user1), "Deutschland"
     (user2), "Italy" (user3, empty). 5 pre-nominated English players and
     12 German players for nomination searches.
   * **Team season statistics** (`ws3_verein.sa_*` columns) updated for all
     20 League 1 teams to reflect the matchday 1 results, so the league table
     renders meaningful standings.
   * **Player season statistics** (`ws3_spieler.sa_tore`, `sa_assists`,
     `sa_spiele`) set for 6 strikers, so the Top Scorers and Top Strikers
     pages return ranked players.
   * **League history** (`ws3_leaguehistory`) for all 20 teams after matchday
     1, so the Table History chart has data.
   * **User high scores** set on `user1`&ndash;`user4` (1500, 1200, 1200, 900)
     with distinct registration dates, so the High Score Ranking page returns
     a deterministic, tie-broken ordering. `user5` keeps a highscore of 0 and
     is excluded from the ranking.

> Passwords are hashed in SQL with `SHA2` using exactly the same scheme as
> `SecurityUtil::hashPassword`: `sha256( salt . sha256( password ) )`.

### Skipping the interactive installer

The run scripts copy
[`docker/config.template.inc.php`](docker/config.template.inc.php) to
`docker/generated/config.inc.php`, which is copied into the web container's
named volume as `/var/www/html/generated/config.inc.php`. The interactive installer at
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

# 1b. Put a pristine application config in place (the runner installs it in the container)
mkdir -p e2e/docker/generated
cp -f e2e/docker/config.template.inc.php e2e/docker/generated/config.inc.php

# 2. Start the E2E stack (the DB seed takes a few seconds on first start)
docker compose -f e2e/docker-compose.e2e.yml up -d --build

# 2a. Install the application config in the web container
docker compose -f e2e/docker-compose.e2e.yml cp \
    e2e/docker/generated/config.inc.php web:/tmp/config.inc.php
docker compose -f e2e/docker-compose.e2e.yml exec -T web sh -c \
    'install -o www-data -g www-data -m 664 /tmp/config.inc.php /var/www/html/generated/config.inc.php && rm /tmp/config.inc.php'

# 2b. Verify the seed finished (must print 972)
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
| `tests/admincenter/login-2fa.spec.ts` | `admin` completes the e-mail second factor after credentials (code shown on page because there is no mail server in the E2E stack). Also verifies that a wrong code shows an error. |
| `tests/admincenter/login-2fa-lockout.spec.ts` | `locktest` admin enters three wrong verification codes and is blocked for 5 minutes; correct credentials and codes are also rejected while blocked. Uses a dedicated admin user so other tests are not affected. |
| `tests/admincenter/login-logs.spec.ts` | `admin` views database-backed login records and removes records older than six months without removing recent records. |
| `tests/admincenter/terms-and-conditions.spec.ts` | `admin` edits the English database-backed terms page and verifies the public page reflects the change. |
| `tests/admincenter/jobs.spec.ts` | `admin` views database-backed job definitions and executes a configured job once. |
| `tests/my-team.spec.ts`            | `user1` logs in to the frontend, opens **My Team** and verifies that his 24 players are listed. |
| `tests/league-table.spec.ts`       | Guest opens the **Leagues** page, verifies the league selector, the 20-team standings with correct stats, table markers, and league switching. |
| `tests/results.spec.ts`            | Guest opens **Results and Schedules**, verifies the Leagues/Cups/Latest-Results tabs, matchday results, and future match schedules. |
| `tests/match-details.spec.ts`      | Guest opens **Match Details** for a completed league match, a completed cup match, a future (unscheduled) match, and an invalid match id. |
| `tests/top-scorers.spec.ts`        | Guest opens **Top Scorers** and **Top Strikers**, verifies player rankings, tie-breaker ordering, and league filtering. |
| `tests/highscore.spec.ts`          | Guest opens the **Users High Score Ranking**, verifies the ranked user table (highscore DESC, registration date tie-breaker), score/date formatting, exclusion of zero-score users, and user/club profile links. |
| `tests/team-and-player-details.spec.ts` | Guest navigates from the **League Table** to a **Team Detail** page, exercises all tabs, then opens a **Player Detail** page and verifies every tab including the advanced statistics modal. |
| `tests/todays-matches.spec.ts`     | Guest opens **Today's Matches**, verifies completed and scheduled friendly matches. |
| `tests/table-history.spec.ts`      | Guest opens **Table History** for a team, verifies the chart data-series and back-to-league link. |
| `tests/team-management.spec.ts`    | `user1` manages his own team: office, squad, formation and tactics, training, sponsor, stadium, stadium environment, finances, ticket prices, selling players, contract extensions, transfer market and transfer offers - including form validation and login protection. |
| `tests/youth-team-management/`     | `user1` manages his youth team: squad listing with action dropdowns and statistics, scouting (scout/country selection, cooldown), marketplace (filtering, buy, remove from market), sell/fire/make-professional player flows, match requests (create, cancel, accept), matches list, formation setup, and completed match report - including form validation and login protection. 66 tests across 13 spec files. |
| `tests/national-team-management/`  | `user1` manages his national team ("England"): squad listing grouped by position with player details and remove action, nominate players page with search form (name, position, main position) and nominate action, national team matches page with next-matches and results AJAX blocks plus formation link - including login protection, requires-team error, and empty-team states. 36 tests across 4 spec files. |

## Re-seeding the database

The seed only runs on an **empty** MySQL data volume. To force a fresh seed,
remove the volume and start again:

```bash
docker compose -f e2e/docker-compose.e2e.yml down -v
docker compose -f e2e/docker-compose.e2e.yml up -d
```

`tests/team-management.spec.ts` changes persistent data (it nominates a
captain, saves a formation, changes ticket prices, sells a player, extends a
contract and rejects a transfer offer), so it is not idempotent: re-seed the
database before running it again.

`tests/youth-team-management/` also changes persistent data (buys, fires, sells
and promotes youth players, creates and accepts match requests, saves a
formation, and runs a scouting action), so it is not idempotent either:
re-seed the database before running it again.

`tests/national-team-management/` also changes persistent data (removes a
nominated player and nominates a new one), so it is not idempotent either:
re-seed the database before running it again.

## Changing the application under test

The image copies the PHP sources, templates and the built assets in
`websoccer/assets`, so a change to the application is only picked up after a
rebuild:

```bash
npm run build   # only when assets-src/ changed
docker compose -f e2e/docker-compose.e2e.yml up -d --build
```
