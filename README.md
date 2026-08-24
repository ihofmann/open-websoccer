# OpenWebSoccer-Sim — Start your own online football manager

OpenWebSoccer-Sim is a PHP-based web application that lets visitors manage a virtual football (soccer) team and compete against other players.

Managers set tactics for the next match, trade players, train their squad, scout talent in the youth academy, and expand their stadium. Matches are simulated automatically in real time, with match reports and a live ticker similar to those on major sports news sites.

Use it to run your own online football game — whether to attract website visitors, offer premium features, or play against colleagues on a company intranet.

> [!WARNING]
> The current release has severe security vulnerabilities and does not run on modern web servers. With the help of GenAI, a new release will be published soon. It will run on PHP 8.3 and use updated libraries.

**[Download now!](https://github.com/ihofmann/open-websoccer/releases)**

## Successor of HSE WebSoccer-Sim

OpenWebSoccer-Sim is the official successor of the commercial products _H&H WebSoccer_ and _HSE WebSoccer-Sim_.
It is maintained by Ingo Hofmann, who developed the first version in 2003.
Contributions of code and ideas from other developers are very welcome.

## Documentation and issue tracker

How to install, set up, and extend the software: [Wiki](https://github.com/ihofmann/open-websoccer/wiki/00.-Home).

Bugs and feature ideas: [Issue Tracker](https://github.com/ihofmann/open-websoccer/issues).

## Development

### Prerequisites

- [Git](https://git-scm.com/)
- [Docker](https://docs.docker.com/get-docker/) 20.10 or newer, with [Docker Compose](https://docs.docker.com/compose/install/) v2 (the `docker compose` plugin is bundled with current Docker Desktop)
- [Node.js](https://nodejs.org/) 18 or newer (npm is included) — for building browser assets
- [PHP](https://www.php.net/) 8.1 or newer and [Composer](https://getcomposer.org/) — for PHP dependencies, if you are not using Docker
- [Gradle](https://gradle.org/) — optional; used only to assemble a release archive (`gradle build`)

### Build

Clone the repository and install both the PHP and frontend dependencies.

PHP libraries (Twig and others) are managed with Composer. From the repository root:

```bash
composer install --no-dev --optimize-autoloader --working-dir=websoccer
```

If you do not have Composer (or PHP) installed locally, use the official Composer Docker image:

```bash
docker run --rm -v "$(pwd)/websoccer:/app" -w /app composer:2 \
    install --no-dev --optimize-autoloader
```

Browser assets are built with npm and [esbuild](https://esbuild.github.io/). From the repository root:

```bash
npm install
npm run build
```

The output is written to `websoccer/assets/` (ignored by Git). It contains one shared `admincenter` bundle, reused by the admin, install, and update pages, plus one JavaScript/CSS bundle for the bundled `default` skin.

Rebuild automatically while editing frontend files:

```bash
npm run build:watch
```

To assemble a full release archive, run:

```bash
gradle build
```

This runs `composer install` and the asset build, then packages the application. `composer` must be on your `PATH` (or run the Gradle build in an environment that provides it). The Docker image built from `Dockerfile` installs PHP dependencies during the image build, so you do not need to run Composer yourself for the Docker workflow below.

### Run locally

The project ships with a `Dockerfile` and a `docker-compose.yml` that start Apache with **PHP 8.5** and a **MySQL 8.0** database. This is the quickest way to run a local instance.

From the repository root:

```bash
docker compose build
docker compose up -d
```

`docker compose build` creates the `open-websoccer:php8.5` image (Apache + PHP 8.5 with the `mysqli`, `gd`, `xml`, `curl`, and `mbstring` extensions). `docker compose up -d` starts two containers:

| Container       | Image                   | Port mapping | Purpose                     |
| --------------- | ----------------------- | ------------ | --------------------------- |
| `websoccer-web` | `open-websoccer:php8.5` | `8080:80`    | Apache + PHP 8.5 web server |
| `websoccer-db`  | `mysql:8.0`             | `3307:3306`  | MySQL 8.0 database          |

The MySQL database is created with these defaults (override them with environment variables if needed):

| Setting               | Default value    |
| --------------------- | ---------------- |
| `MYSQL_DATABASE`      | `websoccer`      |
| `MYSQL_USER`          | `websoccer`      |
| `MYSQL_PASSWORD`      | `websoccer`      |
| `MYSQL_ROOT_PASSWORD` | `websoccer_root` |

The web container runs with debug mode enabled by default through `DEBUG=true`.
Override it when starting the stack to disable debug mode:

```bash
DEBUG=false docker compose up -d
```

When both containers are running, open the installer:

```
http://localhost:8080/install/
```

On the **database configuration** step enter:

- **Database host:** `db` (the MySQL service name)
- **Database name:** `websoccer`
- **Database user:** `websoccer`
- **Database password:** `websoccer`

After installation the site is at `http://localhost:8080/` and the admin area at `http://localhost:8080/admin/`.

Stop the stack with `docker compose down`. Add `--volumes` to also remove the MySQL data volume (`db_data`).

### Run tests

PHPUnit is a Composer **dev** dependency, so install PHP packages **without** `--no-dev` (that flag is only for production/release builds). From the repository root:

```bash
composer install --working-dir=websoccer
```

Then run the suite (the config is `websoccer/phpunit.xml`):

```bash
websoccer/vendor/bin/phpunit --configuration websoccer/phpunit.xml
```

Or from `websoccer/` (PHPUnit picks up `phpunit.xml` automatically):

```bash
cd websoccer
vendor/bin/phpunit
```

To run a single test class:

```bash
vendor/bin/phpunit tests/Unit/WebSoccerTest.php
```

If you do not have PHP/Composer locally, use the official Composer image:

```bash
docker run --rm -v "$(pwd)/websoccer:/app" -w /app composer:2 install
docker run --rm -v "$(pwd)/websoccer:/app" -w /app composer:2 ./vendor/bin/phpunit
```

### Run End-to-End tests

The repository ships with a Playwright E2E suite in [`e2e/`](e2e/README.md) that
runs the whole application in Docker against a pre-populated MySQL database.

Prerequisites: Docker with Compose v2, Node.js 18+, and free host ports `8081`
(web) and `3308` (MySQL). The development stack can keep running, it uses
different ports.

```bash
# Linux / macOS / Git Bash
./e2e/run-e2e.sh

# Windows PowerShell
./e2e/run-e2e.ps1
```

The script builds the frontend assets, starts the E2E Docker stack (web +
pre-seeded database), installs Playwright, runs the tests and tears the stack
down again. See [`e2e/README.md`](e2e/README.md) for details, the sample data
and how to keep the stack running between runs.
