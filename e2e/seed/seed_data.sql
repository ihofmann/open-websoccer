-- =============================================================================
-- OpenWebSoccer-Sim E2E sample data
-- =============================================================================
-- Executed by the MySQL entrypoint as /docker-entrypoint-initdb.d/02-seed.sql,
-- i.e. AFTER 01-schema.sql (= websoccer/install/ws3_ddl_full.sql) has created
-- the schema.
--
-- Contents
--   * 1 admin user            name / password: admin
--   * 5 frontend users        nick  / password: user1 .. user5
--   * 2 leagues               each with 20 teams (40 teams total)
--   * 960 players             2 players per position_main per team
--                             (12 position_main values x 2 x 40 teams)
--   * a few rows for (almost) every other table in the schema.
--
-- Passwords are hashed with the same algorithm as SecurityUtil::hashPassword:
--     sha256( salt . sha256( password ) )
-- MySQL SHA2(x, 256) returns the lowercase hex digest, matching PHP hash().
-- =============================================================================

-- Common salt used for every seeded account (4 characters, as the app expects).
SET @salt := 'salt';

-- -----------------------------------------------------------------------------
-- Admin user (AdminCenter login: admin / admin)
-- -----------------------------------------------------------------------------
INSERT INTO ws3_admin
    (id, name, passwort, passwort_salt, email, lang, r_admin, r_adminuser,
     r_user, r_daten, r_staerken, r_spiele, r_news, r_faq, r_umfrage,
     r_kalender, r_seiten, r_design, r_demo)
VALUES
    (1, 'admin', SHA2(CONCAT(@salt, SHA2('admin', 256)), 256), @salt,
     'admin@example.com', 'en',
     '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0');

-- Second admin used exclusively by the 2FA lockout E2E test.
-- One old login proves the AdminCenter retention action only removes records
-- older than six months.
INSERT INTO ws3_adminlog (admin_name, ip, created_date)
VALUES ('oldadmin', '192.0.2.1', UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 MONTH)));

-- It is intentionally NOT used by any other test so that locking it
-- does not interfere with the rest of the suite.
INSERT INTO ws3_admin
    (id, name, passwort, passwort_salt, email, lang, r_admin, r_adminuser,
     r_user, r_daten, r_staerken, r_spiele, r_news, r_faq, r_umfrage,
     r_kalender, r_seiten, r_design, r_demo)
VALUES
    (2, 'locktest', SHA2(CONCAT(@salt, SHA2('locktest', 256)), 256), @salt,
     'locktest@example.com', 'en',
     '1', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0');

-- -----------------------------------------------------------------------------
-- Frontend users (login: userN / userN)
-- -----------------------------------------------------------------------------
INSERT INTO ws3_user
    (id, nick, passwort, passwort_salt, email, lang, status, datum_anmeldung,
     schluessel, name, wohnort, land)
VALUES
    (1, 'user1', SHA2(CONCAT(@salt, SHA2('user1', 256)), 256), @salt, 'user1@example.com', 'en', '1', UNIX_TIMESTAMP(), 'key0000001', 'User One',   'Berlin',  'Deutschland'),
    (2, 'user2', SHA2(CONCAT(@salt, SHA2('user2', 256)), 256), @salt, 'user2@example.com', 'en', '1', UNIX_TIMESTAMP(), 'key0000002', 'User Two',   'Munich',  'Deutschland'),
    (3, 'user3', SHA2(CONCAT(@salt, SHA2('user3', 256)), 256), @salt, 'user3@example.com', 'en', '1', UNIX_TIMESTAMP(), 'key0000003', 'User Three', 'Hamburg', 'Deutschland'),
    (4, 'user4', SHA2(CONCAT(@salt, SHA2('user4', 256)), 256), @salt, 'user4@example.com', 'en', '1', UNIX_TIMESTAMP(), 'key0000004', 'User Four',  'Cologne', 'Deutschland'),
    (5, 'user5', SHA2(CONCAT(@salt, SHA2('user5', 256)), 256), @salt, 'user5@example.com', 'en', '1', UNIX_TIMESTAMP(), 'key0000005', 'User Five',  'London',  'England');

-- -----------------------------------------------------------------------------
-- Leagues (2)
-- -----------------------------------------------------------------------------
INSERT INTO ws3_liga
    (id, name, kurz, land, p_steh, p_sitz, p_haupt_steh, p_haupt_sitz, p_vip,
     preis_steh, preis_sitz, preis_vip)
VALUES
    (1, 'Premier Sample League', 'PSL', 'England',     10, 10, 10, 10, 5, 7, 12, 100),
    (2, 'Demo Bundesliga',       'DBL', 'Deutschland', 10, 10, 10, 10, 5, 7, 12, 100);

-- -----------------------------------------------------------------------------
-- Helper tables used to generate teams and players set-based (no stored
-- procedure, so the file can simply be piped into the mysql client).
-- They are dropped again at the end of this script.
-- -----------------------------------------------------------------------------
CREATE TABLE seed_team_no (n INT PRIMARY KEY);
INSERT INTO seed_team_no (n) VALUES
    (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),
    (11),(12),(13),(14),(15),(16),(17),(18),(19),(20),
    (21),(22),(23),(24),(25),(26),(27),(28),(29),(30),
    (31),(32),(33),(34),(35),(36),(37),(38),(39),(40);

-- position_main -> position mapping required by the schema:
--   T                     -> Torwart
--   LV / IV / RV          -> Abwehr
--   LM / DM / ZM / OM / RM -> Mittelfeld
--   LS / MS / RS          -> Sturm
CREATE TABLE seed_position (idx INT PRIMARY KEY, position_main VARCHAR(4), position VARCHAR(16));
INSERT INTO seed_position (idx, position_main, position) VALUES
    ( 1, 'T',  'Torwart'),
    ( 2, 'LV', 'Abwehr'),
    ( 3, 'IV', 'Abwehr'),
    ( 4, 'RV', 'Abwehr'),
    ( 5, 'LM', 'Mittelfeld'),
    ( 6, 'DM', 'Mittelfeld'),
    ( 7, 'ZM', 'Mittelfeld'),
    ( 8, 'OM', 'Mittelfeld'),
    ( 9, 'RM', 'Mittelfeld'),
    (10, 'LS', 'Sturm'),
    (11, 'MS', 'Sturm'),
    (12, 'RS', 'Sturm');

-- Two players per position_main.
CREATE TABLE seed_slot (slot INT PRIMARY KEY);
INSERT INTO seed_slot (slot) VALUES (1), (2);

-- -----------------------------------------------------------------------------
-- Teams (40): teams 1-20 in league 1, teams 21-40 in league 2.
-- Teams 1-5 are managed by user1..user5, the rest have no manager.
-- IDs are set explicitly so the player rows below can reference them.
-- -----------------------------------------------------------------------------
INSERT INTO ws3_verein
    (id, name, kurz, liga_id, user_id, user_id_actual, finanz_budget,
     preis_stehen, preis_sitz, preis_haupt_stehen, preis_haupt_sitze,
     preis_vip, nationalteam, status)
SELECT
    n,
    CONCAT('Team ', n),
    CONCAT('T', n),
    IF(n <= 20, 1, 2),
    IF(n <= 5, n, NULL),
    IF(n <= 5, n, NULL),
    5000000,
    7, 12, 15, 20, 50,
    '0', '1'
FROM seed_team_no
ORDER BY n;

-- -----------------------------------------------------------------------------
-- Players (40 teams x 12 position_main x 2 = 960)
-- Name scheme: Player<teamNo>_<positionMain><slot>  /  Lastname<posIdx><slot>
-- -----------------------------------------------------------------------------
INSERT INTO ws3_spieler
    (vorname, nachname, verein_id, position, position_main, geburtstag, nation,
     w_staerke, w_technik, w_kondition, w_frische, w_zufriedenheit,
     vertrag_gehalt, vertrag_spiele, vertrag_torpraemie, marktwert, age, status)
SELECT
    CONCAT('Player', t.n, '_', p.position_main, s.slot),
    CONCAT('Lastname', p.idx, s.slot),
    t.n,
    p.position,
    p.position_main,
    '1995-06-15',
    'England',
    75, 70, 80, 65, 60,
    50000, 30, 1000, 1000000, 25,
    '1'
FROM seed_team_no t
CROSS JOIN seed_position p
CROSS JOIN seed_slot s
ORDER BY t.n, p.idx, s.slot;

DROP TABLE seed_slot;
DROP TABLE seed_position;
DROP TABLE seed_team_no;

-- -----------------------------------------------------------------------------
-- User-related auxiliary tables
-- -----------------------------------------------------------------------------
INSERT INTO ws3_user_inactivity (user_id, login_last, login_check, transfer_check) VALUES
    (1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
    (2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

INSERT INTO ws3_briefe (empfaenger_id, absender_id, absender_name, datum, betreff, nachricht) VALUES
    (1, 2, 'user2', UNIX_TIMESTAMP(), 'Welcome', 'Have fun managing your team!');

INSERT INTO ws3_notification (user_id, eventdate, eventtype, message_key, team_id) VALUES
    (1, UNIX_TIMESTAMP(), 'test', 'test_notification', 1);

INSERT INTO ws3_premiumpayment (user_id, amount, created_date) VALUES
    (1, 100, UNIX_TIMESTAMP());

INSERT INTO ws3_premiumstatement (user_id, action_id, amount, created_date) VALUES
    (1, 'test_action', 50, UNIX_TIMESTAMP());

INSERT INTO ws3_useractionlog (user_id, action_id, created_date) VALUES
    (1, 'login', UNIX_TIMESTAMP()),
    (2, 'login', UNIX_TIMESTAMP());

INSERT INTO ws3_userabsence (user_id, deputy_id, from_date, to_date) VALUES
    (5, 2, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 604800);

-- -----------------------------------------------------------------------------
-- Stadiums (assigned to a couple of teams)
-- -----------------------------------------------------------------------------
INSERT INTO ws3_stadion
    (id, name, stadt, land, p_steh, p_sitz, p_haupt_steh, p_haupt_sitz, p_vip)
VALUES
    (1, 'Sample Arena', 'Sample City', 'England',     10000, 5000, 2000, 1000, 200),
    (2, 'Demo Stadion', 'Demo Town',   'Deutschland',  8000, 4000, 1500,  800, 150);

UPDATE ws3_verein SET stadion_id = 1 WHERE id = 1;
UPDATE ws3_verein SET stadion_id = 2 WHERE id = 21;

-- -----------------------------------------------------------------------------
-- Sponsors + training schedules (linked to a few teams)
-- -----------------------------------------------------------------------------
INSERT INTO ws3_sponsor
    (id, name, liga_id, b_spiel, b_heimzuschlag, b_sieg, b_meisterschaft,
     max_teams, min_platz)
VALUES
    (1, 'Sponsor Alpha', 1, 10000, 5000, 20000, 100000, 5, 1),
    (2, 'Sponsor Beta',  2, 12000, 6000, 25000, 120000, 5, 1);

UPDATE ws3_verein SET sponsor_id = 1 WHERE id = 1;
UPDATE ws3_verein SET sponsor_id = 2 WHERE id = 21;

INSERT INTO ws3_training
    (id, name, w_staerke, w_technik, w_kondition, w_frische, w_zufriedenheit)
VALUES
    (1, 'Standard Training',  30, 30, 30, 30, 30),
    (2, 'Intensive Training', 50, 40, 50, 20, 20);

UPDATE ws3_verein SET training_id = 1 WHERE id = 1;
UPDATE ws3_verein SET training_id = 2 WHERE id = 2;

-- -----------------------------------------------------------------------------
-- Training camps, trainers, training units
-- -----------------------------------------------------------------------------
INSERT INTO ws3_trainingslager
    (id, name, land, preis_spieler_tag, p_staerke, p_technik, p_kondition,
     p_frische, p_zufriedenheit)
VALUES (1, 'Sunny Camp', 'Spain', 1000, 5, 5, 5, 5, 5);

INSERT INTO ws3_trainingslager_belegung (verein_id, lager_id, datum_start, datum_ende)
VALUES (1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 86400);

INSERT INTO ws3_trainer (id, name, salary, p_technique, p_stamina, premiumfee)
VALUES (1, 'Coach Carl', 50000, 60, 60, 1000);

INSERT INTO ws3_training_unit (team_id, trainer_id, focus, intensity, date_executed)
VALUES (1, 1, 'TE', 50, UNIX_TIMESTAMP());

-- -----------------------------------------------------------------------------
-- Cup + cup rounds
-- -----------------------------------------------------------------------------
INSERT INTO ws3_cup (id, name, winner_award, second_award, perround_award)
VALUES (1, 'Demo Cup', 100000, 50000, 10000);

INSERT INTO ws3_cup_round (id, cup_id, name, firstround_date, finalround, groupmatches)
VALUES (1, 1, 'First Round', UNIX_TIMESTAMP() + 604800, '0', '0');

INSERT INTO ws3_cup_round_pending (team_id, cup_round_id) VALUES (1, 1), (2, 1);

INSERT INTO ws3_cup_round_group (cup_round_id, team_id, name)
VALUES (1, 1, 'Group A'), (1, 2, 'Group A');

INSERT INTO ws3_cup_round_group_next (cup_round_id, groupname, `rank`, target_cup_round_id)
VALUES (1, 'Group A', 1, 1);

-- -----------------------------------------------------------------------------
-- Seasons, league table markers, statistics and history
-- -----------------------------------------------------------------------------
INSERT INTO ws3_saison
    (id, name, liga_id, platz_1_id, platz_2_id, platz_3_id, platz_4_id,
     platz_5_id, beendet)
VALUES
    (1, '2025/2026', 1, 0, 0, 0, 0, 0, '0'),
    (2, '2025/2026', 2, 0, 0, 0, 0, 0, '0');

INSERT INTO ws3_team_league_statistics (team_id, season_id) VALUES
    (1, 1), (2, 1), (21, 2);

-- League history is populated below, after team statistics have been set
-- (see "Match data + standings" section).

INSERT INTO ws3_tabelle_markierung (liga_id, bezeichnung, farbe, platz_von, platz_bis, target_league_id)
VALUES
    (1, 'Champions League', '00FF00',  1,  4, 0),
    (1, 'Relegation',       'FFA500', 16, 17, 0),
    (1, 'Relegation',       'FF0000', 18, 20, 0);

INSERT INTO ws3_teamoftheday (season_id, matchday, statistic_id, player_id, position_main)
VALUES (1, 1, 1, 1, 'T');

-- -----------------------------------------------------------------------------
-- Match data + standings
-- -----------------------------------------------------------------------------
-- Completed league matches for matchday 1 (League 1, Season 1), a completed
-- cup match, future matches scheduled ~10 years ahead, and today's friendlies.
-- Team and player season statistics are updated to reflect the results so that
-- the league table, top-scorers and top-strikers pages render meaningful data.

-- Timestamps: matchday 1 = yesterday; future = ~10 years from now; today = now.
SET @md1_date   := UNIX_TIMESTAMP() - 86400;
SET @future_ts := UNIX_TIMESTAMP() + 315360000;  -- 3650 * 86400

-- --- Matchday 1: 10 completed league matches (Teams 1-20) -----------------
INSERT INTO ws3_spiel
    (id, spieltyp, liga_id, saison_id, spieltag, datum, home_verein, gast_verein,
     home_user_id, gast_user_id, home_tore, gast_tore, berechnet, minutes,
     stadion_id, zuschauer)
VALUES
    (1,  'Ligaspiel', 1, 1, 1, @md1_date, 1,  2,  1,    2,    3, 0, '1', 90, 1, 10000),
    (2,  'Ligaspiel', 1, 1, 1, @md1_date, 3,  4,  3,    4,    2, 1, '1', 90, NULL, 8000),
    (3,  'Ligaspiel', 1, 1, 1, @md1_date, 5,  6,  5,    NULL, 1, 1, '1', 90, NULL, 7000),
    (4,  'Ligaspiel', 1, 1, 1, @md1_date, 7,  8,  NULL, NULL, 0, 2, '1', 90, NULL, 6000),
    (5,  'Ligaspiel', 1, 1, 1, @md1_date, 9,  10, NULL, NULL, 4, 0, '1', 90, NULL, 9000),
    (6,  'Ligaspiel', 1, 1, 1, @md1_date, 11, 12, NULL, NULL, 1, 0, '1', 90, NULL, 5000),
    (7,  'Ligaspiel', 1, 1, 1, @md1_date, 13, 14, NULL, NULL, 2, 2, '1', 90, NULL, 7500),
    (8,  'Ligaspiel', 1, 1, 1, @md1_date, 15, 16, NULL, NULL, 3, 1, '1', 90, NULL, 8500),
    (9,  'Ligaspiel', 1, 1, 1, @md1_date, 17, 18, NULL, NULL, 0, 0, '1', 90, NULL, 4000),
    (10, 'Ligaspiel', 1, 1, 1, @md1_date, 19, 20, NULL, NULL, 1, 2, '1', 90, NULL, 6500);

-- --- Matchday 2: 10 future league matches (~10 years ahead) ----------------
INSERT INTO ws3_spiel
    (id, spieltyp, liga_id, saison_id, spieltag, datum, home_verein, gast_verein,
     home_user_id, gast_user_id, berechnet)
VALUES
    (11, 'Ligaspiel', 1, 1, 2, @future_ts, 1,  3,  1,    3,    '0'),
    (12, 'Ligaspiel', 1, 1, 2, @future_ts, 2,  4,  2,    4,    '0'),
    (13, 'Ligaspiel', 1, 1, 2, @future_ts, 5,  7,  5,    NULL, '0'),
    (14, 'Ligaspiel', 1, 1, 2, @future_ts, 6,  8,  NULL, NULL, '0'),
    (15, 'Ligaspiel', 1, 1, 2, @future_ts, 9,  11, NULL, NULL, '0'),
    (16, 'Ligaspiel', 1, 1, 2, @future_ts, 10, 12, NULL, NULL, '0'),
    (17, 'Ligaspiel', 1, 1, 2, @future_ts, 13, 15, NULL, NULL, '0'),
    (18, 'Ligaspiel', 1, 1, 2, @future_ts, 14, 16, NULL, NULL, '0'),
    (19, 'Ligaspiel', 1, 1, 2, @future_ts, 17, 19, NULL, NULL, '0'),
    (20, 'Ligaspiel', 1, 1, 2, @future_ts, 18, 20, NULL, NULL, '0');

-- --- Cup matches: one completed, one future (~10 years ahead) ---------------
INSERT INTO ws3_spiel
    (id, spieltyp, pokalname, pokalrunde, datum, home_verein, gast_verein,
     home_user_id, gast_user_id, home_tore, gast_tore, berechnet, minutes,
     stadion_id, zuschauer)
VALUES
    (21, 'Pokalspiel', 'Demo Cup', 'First Round', @md1_date,  1, 2, 1, 2, 2, 1, '1', 90, 1, 12000);

INSERT INTO ws3_spiel
    (id, spieltyp, pokalname, pokalrunde, datum, home_verein, gast_verein,
     home_user_id, gast_user_id, berechnet)
VALUES
    (22, 'Pokalspiel', 'Demo Cup', 'First Round', @future_ts, 3, 4, 3, 4, '0');

-- --- Today's friendly matches (one completed, one scheduled) ----------------
INSERT INTO ws3_spiel
    (id, spieltyp, datum, home_verein, gast_verein, home_user_id, gast_user_id,
     home_tore, gast_tore, berechnet, minutes, stadion_id, zuschauer)
VALUES
    (23, 'Freundschaft', UNIX_TIMESTAMP(), 5, 6, 5, NULL, 2, 1, '1', 90, 1, 5000),
    (24, 'Freundschaft', UNIX_TIMESTAMP(), 7, 8, NULL, NULL, NULL, NULL, '0', NULL, NULL, NULL);

-- --- Team season statistics (reflect matchday 1 results) --------------------
-- Columns: sa_spiele, sa_siege, sa_niederlagen, sa_unentschieden,
--          sa_tore, sa_gegentore, sa_punkte
UPDATE ws3_verein SET sa_spiele=1, sa_siege=1, sa_niederlagen=0, sa_unentschieden=0, sa_tore=3, sa_gegentore=0, sa_punkte=3 WHERE id=1;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=1, sa_unentschieden=0, sa_tore=0, sa_gegentore=3, sa_punkte=0 WHERE id=2;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=1, sa_niederlagen=0, sa_unentschieden=0, sa_tore=2, sa_gegentore=1, sa_punkte=3 WHERE id=3;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=1, sa_unentschieden=0, sa_tore=1, sa_gegentore=2, sa_punkte=0 WHERE id=4;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=0, sa_unentschieden=1, sa_tore=1, sa_gegentore=1, sa_punkte=1 WHERE id=5;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=0, sa_unentschieden=1, sa_tore=1, sa_gegentore=1, sa_punkte=1 WHERE id=6;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=1, sa_unentschieden=0, sa_tore=0, sa_gegentore=2, sa_punkte=0 WHERE id=7;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=1, sa_niederlagen=0, sa_unentschieden=0, sa_tore=2, sa_gegentore=0, sa_punkte=3 WHERE id=8;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=1, sa_niederlagen=0, sa_unentschieden=0, sa_tore=4, sa_gegentore=0, sa_punkte=3 WHERE id=9;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=1, sa_unentschieden=0, sa_tore=0, sa_gegentore=4, sa_punkte=0 WHERE id=10;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=1, sa_niederlagen=0, sa_unentschieden=0, sa_tore=1, sa_gegentore=0, sa_punkte=3 WHERE id=11;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=1, sa_unentschieden=0, sa_tore=0, sa_gegentore=1, sa_punkte=0 WHERE id=12;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=0, sa_unentschieden=1, sa_tore=2, sa_gegentore=2, sa_punkte=1 WHERE id=13;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=0, sa_unentschieden=1, sa_tore=2, sa_gegentore=2, sa_punkte=1 WHERE id=14;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=1, sa_niederlagen=0, sa_unentschieden=0, sa_tore=3, sa_gegentore=1, sa_punkte=3 WHERE id=15;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=1, sa_unentschieden=0, sa_tore=1, sa_gegentore=3, sa_punkte=0 WHERE id=16;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=0, sa_unentschieden=1, sa_tore=0, sa_gegentore=0, sa_punkte=1 WHERE id=17;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=0, sa_unentschieden=1, sa_tore=0, sa_gegentore=0, sa_punkte=1 WHERE id=18;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=0, sa_niederlagen=1, sa_unentschieden=0, sa_tore=1, sa_gegentore=2, sa_punkte=0 WHERE id=19;
UPDATE ws3_verein SET sa_spiele=1, sa_siege=1, sa_niederlagen=0, sa_unentschieden=0, sa_tore=2, sa_gegentore=1, sa_punkte=3 WHERE id=20;

-- --- Player season statistics (for top-scorers / top-strikers pages) --------
-- Players are named Player<teamNo>_<positionMain><slot>. We update strikers
-- (LS/RS position_main) so that goals+assists are meaningful.
--   Player9_LS1 : 5 goals, 2 assists, 1 match → score 7  (overall top scorer)
--   Player15_LS1: 3 goals, 2 assists, 1 match → score 5
--   Player9_RS1 : 3 goals, 1 assist,  1 match → score 4
--   Player1_LS1 : 3 goals, 0 assists, 1 match → score 3  (top striker, 0 assists)
--   Player8_LS1 : 2 goals, 1 assist,  1 match → score 3
--   Player20_LS1: 2 goals, 0 assists, 1 match → score 2
UPDATE ws3_spieler SET sa_tore=5, sa_assists=2, sa_spiele=1 WHERE vorname='Player9_LS1';
UPDATE ws3_spieler SET sa_tore=3, sa_assists=2, sa_spiele=1 WHERE vorname='Player15_LS1';
UPDATE ws3_spieler SET sa_tore=3, sa_assists=1, sa_spiele=1 WHERE vorname='Player9_RS1';
UPDATE ws3_spieler SET sa_tore=3, sa_assists=0, sa_spiele=1 WHERE vorname='Player1_LS1';
UPDATE ws3_spieler SET sa_tore=2, sa_assists=1, sa_spiele=1 WHERE vorname='Player8_LS1';
UPDATE ws3_spieler SET sa_tore=2, sa_assists=0, sa_spiele=1 WHERE vorname='Player20_LS1';

-- --- League history (matchday 1 rankings for table-history page) ------------
-- Standings order after matchday 1 (see team stats above):
--   1. Team 9  (3 pts, GD +4)    8.  Team 13 (1 pt,  GD 0, goals 2)
--   2. Team 1  (3 pts, GD +3)    9.  Team 14 (1 pt,  GD 0, goals 2)
--   3. Team 15 (3 pts, GD +2, G3)10.  Team 5  (1 pt,  GD 0, goals 1)
--   4. Team 8  (3 pts, GD +2, G2)11.  Team 6  (1 pt,  GD 0, goals 1)
--   5. Team 20 (3 pts, GD +1, G2)12.  Team 17 (1 pt,  GD 0, goals 0)
--   6. Team 3  (3 pts, GD +1, G2)13.  Team 18 (1 pt,  GD 0, goals 0)
--   7. Team 11 (3 pts, GD +1, G1)14.  Team 19 (0 pts, GD -1, goals 1)
--                               15.  Team 4  (0 pts, GD -1, goals 1)
--                               16.  Team 12 (0 pts, GD -1, goals 0)
--                               17.  Team 16 (0 pts, GD -2, goals 0)
--                               18.  Team 7  (0 pts, GD -2, goals 0)
--                               19.  Team 2  (0 pts, GD -3, goals 0)
--                               20.  Team 10 (0 pts, GD -4, goals 0)
INSERT INTO ws3_leaguehistory (team_id, season_id, user_id, matchday, `rank`) VALUES
    (9,  1, NULL, 1,  1),
    (1,  1, 1,    1,  2),
    (15, 1, NULL, 1,  3),
    (8,  1, NULL, 1,  4),
    (20, 1, NULL, 1,  5),
    (3,  1, 3,    1,  6),
    (11, 1, NULL, 1,  7),
    (13, 1, NULL, 1,  8),
    (14, 1, NULL, 1,  9),
    (5,  1, 5,    1, 10),
    (6,  1, NULL, 1, 11),
    (17, 1, NULL, 1, 12),
    (18, 1, NULL, 1, 13),
    (19, 1, NULL, 1, 14),
    (4,  1, 4,    1, 15),
    (12, 1, NULL, 1, 16),
    (16, 1, NULL, 1, 17),
    (7,  1, NULL, 1, 18),
    (2,  1, 2,    1, 19),
    (10, 1, NULL, 1, 20);

-- -----------------------------------------------------------------------------
-- Youth module
-- -----------------------------------------------------------------------------
-- Scouts (2): one without speciality, one specialised on goalkeepers.
INSERT INTO ws3_youthscout (id, name, expertise, fee, speciality)
VALUES
    (1, 'Scout Sam',  80, 5000, NULL),
    (2, 'Scout Alex', 60, 3000, 'Torwart');

-- Team 1 youth players (12, IDs 1-12).  IDs are explicit so that youth matches
-- and tests can reference them.
--   1  Young Talent       16  Mittelfeld  – used by sell-player test
--   2  Young Goalie       17  Torwart     – used by make-professional test
--   3  Teen Defender      15  Abwehr      – too young for professional (age < 16)
--   4  Market Midfielder  16  Mittelfeld  – already on market (transfer_fee > 0)
--   5  Youth Striker      17  Sturm       – used by fire-player test
--   6-12 fillers (various positions / ages, untouched by action tests)
INSERT INTO ws3_youthplayer
    (id, team_id, firstname, lastname, age, position, strength, nation,
     transfer_fee, st_matches, st_goals, st_assists, st_cards_yellow,
     st_cards_yellow_red, st_cards_red, strength_last_change)
VALUES
    ( 1, 1, 'Young',          'Talent',      16, 'Mittelfeld', 50, 'England',      0,      5, 2, 1, 1, 0, 0,  1),
    ( 2, 1, 'Young',          'Goalie',      17, 'Torwart',    60, 'England',      0,      3, 0, 0, 0, 0, 0,  0),
    ( 3, 1, 'Teen',           'Defender',    15, 'Abwehr',     40, 'Deutschland',  0,      2, 0, 0, 0, 0, 0,  0),
    ( 4, 1, 'Market',         'Midfielder',  16, 'Mittelfeld', 55, 'England',  200000,      4, 1, 1, 0, 0, 0,  0),
    ( 5, 1, 'Youth',          'Striker',     17, 'Sturm',      65, 'England',      0,      6, 4, 2, 1, 0, 0,  2),
    ( 6, 1, 'Young',          'Defender',    16, 'Abwehr',     45, 'England',      0,      3, 0, 0, 0, 0, 0,  0),
    ( 7, 1, 'Youth',          'Midfielder',  16, 'Mittelfeld', 48, 'Deutschland',  0,      4, 1, 1, 0, 0, 0,  0),
    ( 8, 1, 'Youth',          'Keeper',      16, 'Torwart',    52, 'England',      0,      2, 0, 0, 0, 0, 0,  0),
    ( 9, 1, 'Young',          'Wing',        17, 'Sturm',      42, 'England',      0,      5, 2, 1, 1, 0, 0,  0),
    (10, 1, 'Teen',           'Mid',         15, 'Mittelfeld', 38, 'England',      0,      1, 0, 0, 0, 0, 0,  0),
    (11, 1, 'Youth',          'Back',        16, 'Abwehr',     44, 'England',      0,      3, 0, 0, 0, 0, 0,  0),
    (12, 1, 'Young',          'Forward',     16, 'Sturm',      50, 'England',      0,      4, 1, 0, 0, 0, 0,  0);

-- Team 2 youth players (12, IDs 13-24).  None are touched by action tests, so
-- they are safe to reference from the completed-match seed below.
INSERT INTO ws3_youthplayer
    (id, team_id, firstname, lastname, age, position, strength, nation, transfer_fee)
VALUES
    (13, 2, 'Guest', 'Keeper',     17, 'Torwart',    55, 'England',      0),
    (14, 2, 'Guest', 'Defender',   16, 'Abwehr',     50, 'England',      0),
    (15, 2, 'Guest', 'Midfielder', 16, 'Mittelfeld', 52, 'Deutschland',  0),
    (16, 2, 'Guest', 'Striker',    17, 'Sturm',      58, 'England',      0),
    (17, 2, 'Guest', 'Fullback',   16, 'Abwehr',     46, 'England',      0),
    (18, 2, 'Guest', 'Winger',     17, 'Mittelfeld', 49, 'England',      0),
    (19, 2, 'Guest', 'Center',     16, 'Mittelfeld', 47, 'Deutschland',  0),
    (20, 2, 'Guest', 'Forward',    17, 'Sturm',      53, 'England',      0),
    (21, 2, 'Guest', 'Backup1',    16, 'Torwart',    41, 'England',      0),
    (22, 2, 'Guest', 'Backup2',    16, 'Abwehr',     43, 'England',      0),
    (23, 2, 'Guest', 'Backup3',    16, 'Mittelfeld', 44, 'England',      0),
    (24, 2, 'Guest', 'Backup4',    16, 'Sturm',      45, 'England',      0);

-- Team 3 youth player on the market, buyable by user1 (budget 5 000 000).
INSERT INTO ws3_youthplayer
    (id, team_id, firstname, lastname, age, position, strength, nation, transfer_fee)
VALUES (25, 3, 'Buyable', 'Youth', 16, 'Sturm', 50, 'England', 150000);

-- Team 2 youth player on the market (for marketplace filter / buy-button tests).
INSERT INTO ws3_youthplayer
    (id, team_id, firstname, lastname, age, position, strength, nation, transfer_fee)
VALUES (26, 2, 'Market', 'Keeper', 16, 'Torwart', 50, 'England', 120000);

-- Scouting state:
--   Team 1 (user1): never scouted  → scouting is possible.
--   Team 2 (user2): scouted just now → scouting blocked for 24 h (break test).
UPDATE ws3_verein SET scouting_last_execution = 0                WHERE id = 1;
UPDATE ws3_verein SET scouting_last_execution = UNIX_TIMESTAMP() WHERE id = 2;

-- Youth match requests (2):
--   Request 1: Team 1, 7 days ahead, reward 5 000 → user1 sees "Cancel".
--   Request 2: Team 2, 5 days ahead, no reward    → user1 sees "Accept".
INSERT INTO ws3_youthmatch_request (id, team_id, matchdate, reward)
VALUES
    (1, 1, UNIX_TIMESTAMP() + 604800, 5000),
    (2, 2, UNIX_TIMESTAMP() + 432000, 0);

-- Youth matches (2):
--   Match 1: completed (simulated), Team 1 vs Team 2, 2-1.
--   Match 2: scheduled (not simulated), Team 1 vs Team 2, ~8 days ahead.
SET @ymatch_future_ts := UNIX_TIMESTAMP() + 691200; -- 8 days
INSERT INTO ws3_youthmatch
    (id, matchdate, home_team_id, guest_team_id, simulated,
     home_goals, guest_goals)
VALUES
    (1, UNIX_TIMESTAMP() - 86400, 1, 2, '1', 2, 1),
    (2, @ymatch_future_ts,        1, 2, '0', NULL, NULL);

-- Players who took part in the completed match (Match 1).
-- Uses safe youth-player IDs (untouched by any action test).
INSERT INTO ws3_youthmatch_player
    (match_id, team_id, player_id, playernumber, position, position_main,
     grade, minutes_played, strength, name, goals, assists,
     ballcontacts, wontackles, shoots, passes_successed, passes_failed, state)
VALUES
    (1, 1,  6, 1, 'Abwehr',     'IV', 2.5, 90, 45, 'Young Defender',    0, 0, 10, 5, 2, 8, 2, '1'),
    (1, 1,  7, 2, 'Mittelfeld', 'ZM', 3.0, 90, 48, 'Youth Midfielder',  0, 1, 15, 6, 3, 12, 3, '1'),
    (1, 1,  9, 3, 'Sturm',      'MS', 1.8, 90, 42, 'Young Wing',        2, 0, 8, 3, 5, 6, 1, '1'),
    (1, 1, 12, 4, 'Sturm',      'RS', 2.8, 75, 50, 'Young Forward',     0, 1, 7, 2, 3, 5, 2, 'Ausgewechselt'),
    (1, 2, 13, 1, 'Torwart',    'T',  3.2, 90, 55, 'Guest Keeper',      0, 0, 6, 2, 1, 4, 1, '1'),
    (1, 2, 15, 2, 'Mittelfeld', 'ZM', 2.7, 90, 52, 'Guest Midfielder',  1, 0, 14, 7, 4, 10, 4, '1'),
    (1, 2, 16, 3, 'Sturm',      'LS', 2.0, 90, 58, 'Guest Striker',     0, 0, 9, 4, 6, 7, 2, '1');

-- Match report items for the completed match.
INSERT INTO ws3_youthmatch_reportitem
    (match_id, minute, message_key, message_data, home_on_ball)
VALUES
    (1, 12, 'ymreport_goal',          '{"scorer":"Young Wing"}',     '1'),
    (1, 25, 'ymreport_card_yellow',   '{"player":"Guest Midfielder"}','0'),
    (1, 55, 'ymreport_goal',          '{"scorer":"Guest Midfielder"}','0'),
    (1, 78, 'ymreport_substitution',  '{"in":"Youth Midfielder","out":"Young Forward"}','1'),
    (1, 85, 'ymreport_goal',          '{"scorer":"Young Wing"}',     '1');

-- -----------------------------------------------------------------------------
-- Stadium builder / buildings
-- -----------------------------------------------------------------------------
INSERT INTO ws3_stadium_builder
    (id, name, fixedcosts, cost_per_seat, construction_time_days,
     construction_time_days_min, min_stadium_size, max_stadium_size, reliability)
VALUES (1, 'Builder Inc.', 50000, 100, 30, 20, 0, 50000, 90);

INSERT INTO ws3_stadium_construction (team_id, builder_id, started, deadline)
VALUES (1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 2592000);

INSERT INTO ws3_stadiumbuilding (id, name, costs, construction_time_days, effect_training)
VALUES
    (1, 'Youth Center',   100000, 20, 5),
    (2, 'Medical Center',  80000, 15, 0);

INSERT INTO ws3_buildings_of_team (building_id, team_id) VALUES (1, 1);

-- -----------------------------------------------------------------------------
-- Random events
-- -----------------------------------------------------------------------------
INSERT INTO ws3_randomevent (id, message, effect, effect_money_amount, weight)
VALUES
    (1, 'A sponsor pays an extra bonus.', 'money',           50000, 5),
    (2, 'A player gets injured.',         'player_injured',      0, 2);

INSERT INTO ws3_randomevent_occurrence (user_id, team_id, event_id, occurrence_date)
VALUES (1, 1, 1, UNIX_TIMESTAMP());

-- -----------------------------------------------------------------------------
-- Badges + achievements
-- -----------------------------------------------------------------------------
INSERT INTO ws3_badge (id, name, description, level, event, event_benchmark)
VALUES (1, 'Veteran', 'Member for 30 days', 'bronze', 'membership_since_x_days', 30);

INSERT INTO ws3_badge_user (user_id, badge_id, date_rewarded)
VALUES (1, 1, UNIX_TIMESTAMP());

INSERT INTO ws3_achievement (user_id, team_id, season_id, `rank`, date_recorded)
VALUES (1, 1, 1, 1, UNIX_TIMESTAMP());

-- -----------------------------------------------------------------------------
-- Finances + transfers
-- -----------------------------------------------------------------------------
INSERT INTO ws3_konto (verein_id, absender, betrag, datum, verwendung)
VALUES (1, 'Sponsor Alpha', 100000, UNIX_TIMESTAMP(), 'Sponsor payment');

INSERT INTO ws3_transfer_angebot
    (spieler_id, verein_id, user_id, datum, abloese, vertrag_spiele, vertrag_gehalt)
VALUES (1, 2, 2, UNIX_TIMESTAMP(), 1000000, 30, 50000);

INSERT INTO ws3_transfer_offer
    (player_id, sender_user_id, sender_club_id, receiver_club_id,
     submitted_date, offer_amount)
VALUES (1, 2, 2, 1, UNIX_TIMESTAMP(), 1000000);

INSERT INTO ws3_transfer
    (spieler_id, seller_user_id, seller_club_id, buyer_user_id, buyer_club_id,
     datum, directtransfer_amount)
VALUES (1, 2, 2, 1, 1, UNIX_TIMESTAMP(), 1000000);

-- -----------------------------------------------------------------------------
-- One sample news article (autor_id references the admin created above).
-- The E2E admin test additionally creates, edits and deletes its own article.
-- -----------------------------------------------------------------------------
INSERT INTO ws3_news
    (datum, autor_id, titel, nachricht, linktext1, linkurl1, linktext2, linkurl2,
     linktext3, linkurl3, status)
VALUES
    (UNIX_TIMESTAMP(), 1, 'Welcome to OpenWebSoccer', 'This is a sample news entry.',
     'OpenWebSoccer project', 'https://github.com/OpenWebSoccer',
     'OpenWebSoccer documentation', 'https://docs.openwebsoccer.org',
     'OpenWebSoccer license', 'https://github.com/OpenWebSoccer/OpenWebSoccer-Sim/blob/master/LICENSE',
     '1');

-- -----------------------------------------------------------------------------
-- National teams module
-- -----------------------------------------------------------------------------
-- Three national teams (IDs 41-43, beyond the 40 club teams):
--   41 "England"      managed by user1  (players have nation = "England")
--   42 "Deutschland"  managed by user2  (needs German players – see below)
--   43 "Italy"        managed by user3  (no players – empty team test)
-- user4 and user5 do NOT manage a national team (requires-team error test).
--
-- The AddNationalPlayerController checks player.nation == team.name, so the
-- national team name must match the nation column of ws3_spieler.
-- All 960 seeded players have nation = "England", so user1 can nominate any
-- uninjured English player.  German players are added below for user2.
INSERT INTO ws3_verein
    (id, name, kurz, liga_id, user_id, user_id_actual, nationalteam, status)
VALUES
    (41, 'England',     'ENG', 1, 1, 1, '1', '1'),
    (42, 'Deutschland', 'GER', 2, 2, 2, '1', '1'),
    (43, 'Italy',       'ITA', 1, 3, 3, '1', '1');

-- German players (IDs 961-972, 2 per position_main) belonging to Team 2 so
-- that user2 (managing "Deutschland") can search and nominate them.
INSERT INTO ws3_spieler
    (id, vorname, nachname, verein_id, position, position_main, geburtstag,
     nation, w_staerke, w_technik, w_kondition, w_frische, w_zufriedenheit,
     vertrag_gehalt, vertrag_spiele, vertrag_torpraemie, marktwert, age, status)
VALUES
    (961, 'German', 'Keeper',     2, 'Torwart',    'T',  '1996-03-10', 'Deutschland', 78, 72, 82, 66, 58, 50000, 30, 1000, 1000000, 24, '1'),
    (962, 'German', 'Keeper2',    2, 'Torwart',    'T',  '1997-07-22', 'Deutschland', 73, 68, 78, 62, 55, 50000, 30, 1000,  900000, 23, '1'),
    (963, 'German', 'Defender',   2, 'Abwehr',     'LV', '1995-01-15', 'Deutschland', 76, 71, 80, 64, 59, 50000, 30, 1000,  950000, 25, '1'),
    (964, 'German', 'Defender2',  2, 'Abwehr',     'IV', '1996-09-03', 'Deutschland', 77, 70, 81, 65, 57, 50000, 30, 1000,  980000, 24, '1'),
    (965, 'German', 'Defender3',  2, 'Abwehr',     'RV', '1994-11-20', 'Deutschland', 74, 69, 79, 63, 56, 50000, 30, 1000,  920000, 26, '1'),
    (966, 'German', 'Midfielder', 2, 'Mittelfeld', 'ZM', '1996-05-18', 'Deutschland', 79, 74, 83, 67, 60, 50000, 30, 1000, 1100000, 24, '1'),
    (967, 'German', 'Midfielder2',2, 'Mittelfeld', 'DM', '1995-08-30', 'Deutschland', 75, 73, 80, 64, 58, 50000, 30, 1000,  950000, 25, '1'),
    (968, 'German', 'Midfielder3',2, 'Mittelfeld', 'OM', '1997-02-12', 'Deutschland', 72, 75, 77, 61, 54, 50000, 30, 1000,  880000, 23, '1'),
    (969, 'German', 'Winger',     2, 'Mittelfeld', 'LM', '1996-04-25', 'Deutschland', 71, 76, 76, 60, 53, 50000, 30, 1000,  850000, 24, '1'),
    (970, 'German', 'Striker',    2, 'Sturm',      'MS', '1995-06-08', 'Deutschland', 80, 77, 82, 68, 61, 50000, 30, 1000, 1200000, 25, '1'),
    (971, 'German', 'Striker2',   2, 'Sturm',      'LS', '1997-10-14', 'Deutschland', 76, 73, 79, 63, 55, 50000, 30, 1000,  950000, 23, '1'),
    (972, 'German', 'Striker3',   2, 'Sturm',      'RS', '1996-12-01', 'Deutschland', 74, 70, 78, 62, 54, 50000, 30, 1000,  900000, 24, '1');

-- Pre-nominate 5 English players to Team 41 ("England"), covering all 4
-- position groups, so the nationalteam page renders populated tables.
--   Player  1: Player1_T1  (Torwart,     T)   – Team 1
--   Player  3: Player1_LV1 (Abwehr,      LV)  – Team 1
--   Player  5: Player1_IV1 (Abwehr,      IV)  – Team 1
--   Player  9: Player1_LM1 (Mittelfeld,  LM)  – Team 1
--   Player 19: Player1_LS1 (Sturm,       LS)  – Team 1
INSERT INTO ws3_nationalplayer (team_id, player_id) VALUES
    (41,  1),
    (41,  3),
    (41,  5),
    (41,  9),
    (41, 19);

-- National team matches (IDs 25-26, after the 24 existing matches):
--   Match 25: England (41) vs Deutschland (42), completed, 3-1, yesterday.
--   Match 26: England (41) vs Deutschland (42), future, ~10 years ahead.
INSERT INTO ws3_spiel
    (id, spieltyp, datum, home_verein, gast_verein, home_user_id, gast_user_id,
     home_tore, gast_tore, berechnet, minutes, stadion_id, zuschauer)
VALUES
    (25, 'Freundschaft', @md1_date, 41, 42, 1, 2, 3, 1, '1', 90, NULL, 50000);

INSERT INTO ws3_spiel
    (id, spieltyp, datum, home_verein, gast_verein, home_user_id, gast_user_id,
     berechnet)
VALUES
    (26, 'Freundschaft', @future_ts, 41, 42, 1, 2, '0');

-- -----------------------------------------------------------------------------
-- High score ranking (page=highscore)
-- -----------------------------------------------------------------------------
-- Set distinct highscore values and registration dates on the frontend users
-- so the "Users High Score Ranking" page renders a deterministic result.
-- The page lists only active users with highscore > 0, ordered by
-- highscore DESC, datum_anmeldung ASC.
--
--   user1: 1500 pts, registered 2024-01-15  → rank 1
--   user2: 1200 pts, registered 2024-02-20  → rank 2 (tie-break: earlier date)
--   user3: 1200 pts, registered 2024-03-10  → rank 3 (same score, later date)
--   user4:  900 pts, registered 2024-04-05  → rank 4
--   user5:    0 pts                         → excluded (highscore must be > 0)
UPDATE ws3_user SET highscore = 1500, datum_anmeldung = UNIX_TIMESTAMP('2024-01-15') WHERE id = 1;
UPDATE ws3_user SET highscore = 1200, datum_anmeldung = UNIX_TIMESTAMP('2024-02-20') WHERE id = 2;
UPDATE ws3_user SET highscore = 1200, datum_anmeldung = UNIX_TIMESTAMP('2024-03-10') WHERE id = 3;
UPDATE ws3_user SET highscore = 900,  datum_anmeldung = UNIX_TIMESTAMP('2024-04-05') WHERE id = 4;
-- user5 keeps the default highscore = 0 and is excluded from the ranking.
