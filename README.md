# OpenWebSoccer-Sim - Start your own Online Football Manager!

With with PHP based web application your website visitors can manage a virtual fantasy football (soccer) team and play with it against other users.
They set the tactics for the next match, trade players, train their team, look for new talents in their youth section or extend their stadium.
The software simulates all matches automatically and in real-time. It produces match reports with a live ticker similar to what you know from popular news websites.
Setup your own football online game in order to attract new website visitors, make money through premium features or for playing against colleagues in your company intranet.

**[Download now!](https://github.com/ihofmann/open-websoccer/releases)**

## Successor of HSE WebSoccer-Sim

OpenWebSoccer-Sim is the official successor of the commercial products _H&H WebSoccer_ and _HSE WebSoccer-Sim_.
It is maintained by Ingo Hofmann who has developed the first version of the software in 2003.
Ingo is very happy about support from other developers! If you feel like working on a new challenge in your leisure time, please feel free to contribute code or ideas.

## Documentation and Issue Tracker

Find out how you install, setup and enhance the software at the [Wiki](https://github.com/ihofmann/open-websoccer/wiki/00.-Home).

You found a bug or have an idea for a new feature? Then don't hesitate to open an issue at the [Issue Tracker](https://github.com/ihofmann/open-websoccer/issues).

## PHP Dependencies

OpenWebSoccer-Sim uses [Twig](https://twig.symfony.com/) as its template engine.
Twig and other third-party PHP libraries are managed with
[Composer](https://getcomposer.org/).

### Installing dependencies

If you have Composer installed locally, run from the repository root:

```bash
composer install --no-dev --optimize-autoloader --working-dir=websoccer
```

If you do **not** have Composer (or PHP) installed, use the official Composer
Docker image (no local installation required):

```bash
docker run --rm -v "$(pwd)/websoccer:/app" -w /app composer:2 \
    install --no-dev --optimize-autoloader
```

> The Docker image built by `Dockerfile` installs the dependencies automatically
> during the image build (multi-stage build), so for the Docker-based quick
> start below you do not need to run Composer yourself.

### Building a release

`gradle build` runs `composer install` automatically before assembling the
release archive (see `build.gradle`), so the generated `websoccer/vendor/`
folder is included in the release. This requires `composer` to be available on
your `PATH` (or run the Gradle build inside an environment that provides it).

### Upgrading Twig

To upgrade Twig to a newer release, run from the repository root:

```bash
composer update twig/twig --working-dir=websoccer
```

then commit the updated `websoccer/composer.lock`.

## Frontend dependencies and asset bundles

The JavaScript and CSS dependencies are built locally with
[npm](https://nodejs.org/) and [esbuild](https://esbuild.github.io/).
Install Node.js 18 or newer (npm is included).

Install the frontend dependencies from the repository root:

```bash
npm install
```

Build the browser assets during development:

```bash
npm run build
```

The output is written to `websoccer/assets/`. It contains one shared
`admincenter` bundle, reused by the admin, install, and update pages, plus one
JavaScript/CSS bundle for each bundled skin: `default`, `green`, and `schedio`.
The generated directory is ignored by Git.

To rebuild automatically while editing frontend files:

```bash
npm run build:watch
```

The Gradle release build runs both dependency installation steps and the asset
build automatically:

```bash
gradle build
```

## Run with Docker (PHP 8.5 + MySQL)

The project ships with a `Dockerfile` and a `docker-compose.yml` that bring up a
slim Apache web server running **PHP 8.5** together with a **MySQL 8.0** database.
This is the quickest way to get a local instance running.

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) 20.10 or newer
- [Docker Compose](https://docs.docker.com/compose/install/) v2 (the `docker compose` plugin is bundled with modern Docker)

### 1. Build the image

From the repository root run:

```bash
docker compose build
```

This builds the `open-websoccer:php8.5` image, which contains Apache with PHP 8.5
and the required extensions (`mysqli`, `gd`, `xml`, `curl`, `mbstring`).

### 2. Start the containers

```bash
docker compose up -d
```

This starts two containers:

| Container       | Image                   | Port mapping | Purpose                     |
| --------------- | ----------------------- | ------------ | --------------------------- |
| `websoccer-web` | `open-websoccer:php8.5` | `8080:80`    | Apache + PHP 8.5 web server |
| `websoccer-db`  | `mysql:8.0`             | `3307:3306`  | MySQL 8.0 database          |

The MySQL database is created automatically with these default credentials
(override them via environment variables if needed):

| Setting               | Default value    |
| --------------------- | ---------------- |
| `MYSQL_DATABASE`      | `websoccer`      |
| `MYSQL_USER`          | `websoccer`      |
| `MYSQL_PASSWORD`      | `websoccer`      |
| `MYSQL_ROOT_PASSWORD` | `websoccer_root` |

### 3. Run the web installer

Once both containers are up, open your browser and navigate to:

```
http://localhost:8080/install/
```

Follow the installation wizard. On the **database configuration** step enter:

- **Database host:** `db` (this is the MySQL container's service name)
- **Database name:** `websoccer`
- **Database user:** `websoccer`
- **Database password:** `websoccer`

Complete the remaining steps (project settings, schema import, admin user
creation). After installation your site is available at
`http://localhost:8080/` and the admin area at `http://localhost:8080/admin/`.

### 4. Stop the containers

```bash
docker compose down
```

Add `--volumes` if you also want to remove the MySQL data:

```bash
docker compose down --volumes
```

### Notes

- The MySQL data is stored in a named Docker volume (`db_data`).
