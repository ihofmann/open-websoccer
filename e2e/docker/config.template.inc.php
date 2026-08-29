<?php
/**
 * Template for the E2E application configuration.
 *
 * The run scripts (run-e2e.ps1 / run-e2e.sh) copy this file to
 * e2e/docker/generated/config.inc.php, which is copied into the web
 * container's named volume as /var/www/html/generated/config.inc.php. That
 * way the interactive installer does NOT need to be run before the tests.
 *
 * The copy is intentional and must not be skipped: on its first request the
 * application appends the default value of every module setting to this file,
 * so the copied file is modified at runtime. e2e/docker/generated/ is
 * therefore git-ignored while this template stays pristine.
 *
 * It points the application at the `db` service of docker-compose.e2e.yml and
 * enables username based login, because the seeded users log in with their
 * nickname (user1 .. user5) rather than an e-mail address.
 */

$conf['db_host'] = "db";
$conf['db_user'] = "websoccer";
$conf['db_passwort'] = "websoccer";
$conf['db_name'] = "websoccer";
$conf['db_prefix'] = "ws3";

$conf['supported_languages'] = "de,en,es,it";
$conf['homepage'] = "http://localhost:8081";
$conf['context_root'] = "";
$conf['projectname'] = "OpenWebSoccer E2E";
$conf['systememail'] = "admin@example.com";
$conf['session_lifetime'] = "7200";

// Log in with nickname (user1..user5) instead of e-mail address.
$conf['login_type'] = "username";
?>
