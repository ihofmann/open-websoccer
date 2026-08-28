-- Schema updates applied by the /update installer for existing databases.
-- The same changes are part of install/ws3_ddl_full.sql for new installations.
-- Statements are idempotent MODIFY COLUMN operations.

ALTER TABLE ws3_liga DROP COLUMN admin_id;

ALTER TABLE ws3_saison MODIFY platz_1_id INT(10) NOT NULL DEFAULT 0;
ALTER TABLE ws3_saison MODIFY platz_2_id INT(10) NOT NULL DEFAULT 0;
ALTER TABLE ws3_saison MODIFY platz_3_id INT(10) NOT NULL DEFAULT 0;
ALTER TABLE ws3_saison MODIFY platz_4_id INT(10) NOT NULL DEFAULT 0;
ALTER TABLE ws3_saison MODIFY platz_5_id INT(10) NOT NULL DEFAULT 0;

ALTER TABLE ws3_verein MODIFY finanz_budget INT(11) NOT NULL DEFAULT 0;
ALTER TABLE ws3_verein MODIFY preis_stehen SMALLINT(4) NOT NULL DEFAULT 0;
ALTER TABLE ws3_verein MODIFY preis_sitz SMALLINT(4) NOT NULL DEFAULT 0;
ALTER TABLE ws3_verein MODIFY preis_haupt_stehen SMALLINT(4) NOT NULL DEFAULT 0;
ALTER TABLE ws3_verein MODIFY preis_haupt_sitze SMALLINT(4) NOT NULL DEFAULT 0;
ALTER TABLE ws3_verein MODIFY preis_vip SMALLINT(4) NOT NULL DEFAULT 0;

ALTER TABLE ws3_spieler MODIFY geburtstag DATE NULL;
ALTER TABLE ws3_spieler MODIFY w_staerke TINYINT(3) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler MODIFY w_technik TINYINT(3) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler MODIFY w_kondition TINYINT(3) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler MODIFY w_frische TINYINT(3) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler MODIFY w_zufriedenheit TINYINT(3) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler MODIFY vertrag_gehalt INT(10) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler MODIFY vertrag_spiele SMALLINT(5) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler MODIFY vertrag_torpraemie INT(10) NOT NULL DEFAULT 0;

-- Unix timestamps after 2038-01-19 do not fit in signed INT.
ALTER TABLE ws3_transfer MODIFY datum BIGINT NOT NULL;
ALTER TABLE ws3_transfer_offer MODIFY submitted_date BIGINT NOT NULL;
ALTER TABLE ws3_stadium_construction MODIFY started BIGINT NOT NULL;
ALTER TABLE ws3_stadium_construction MODIFY deadline BIGINT NOT NULL;
ALTER TABLE ws3_youthmatch MODIFY matchdate BIGINT NOT NULL;
ALTER TABLE ws3_youthmatch_request MODIFY matchdate BIGINT NOT NULL;
ALTER TABLE ws3_userabsence MODIFY from_date BIGINT NOT NULL;
ALTER TABLE ws3_userabsence MODIFY to_date BIGINT NOT NULL;
ALTER TABLE ws3_premiumpayment MODIFY created_date BIGINT NOT NULL;
ALTER TABLE ws3_premiumstatement MODIFY created_date BIGINT NOT NULL;

-- AdminCenter e-mail second factor: verification code, failed attempts, lockout.
ALTER TABLE ws3_admin ADD verification_code VARCHAR(6);
ALTER TABLE ws3_admin ADD login_attempts INT(11) NOT NULL DEFAULT 0;
ALTER TABLE ws3_admin ADD blocked_until INT(11) NOT NULL DEFAULT 0;


CREATE TABLE IF NOT EXISTS ws3_pages (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(64) NOT NULL,
  language VARCHAR(10) NOT NULL,
  content MEDIUMTEXT NOT NULL,
  UNIQUE KEY pages_type_language (type, language)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ws3_adminlog (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_name VARCHAR(255) NOT NULL,
  ip VARCHAR(45) NULL,
  created_date BIGINT NOT NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ws3_entitylog (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  created_date BIGINT NOT NULL,
  username VARCHAR(255) NOT NULL,
  ip VARCHAR(45) NULL,
  type VARCHAR(32) NOT NULL,
  entity VARCHAR(255) NOT NULL,
  entity_value MEDIUMTEXT NOT NULL,
  INDEX entitylog_created_date (created_date)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ws3_jobs (
  id VARCHAR(64) NOT NULL PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  name_de VARCHAR(255) NULL,
  class VARCHAR(255) NOT NULL,
  `interval` INT(10) NOT NULL,
  last_ping BIGINT NOT NULL DEFAULT 0,
  stop TINYINT(1) NOT NULL DEFAULT 1,
  error TEXT NULL,
  inittime BIGINT NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

INSERT IGNORE INTO ws3_pages (type, language, content) VALUES
('termsandconditions', 'de', '<h2>Teilnahme am Spiel</h2>
<i class="bi bi-chevron-right"></i> Es ist nur ein Benutzerkonto pro Mitspieler erlaubt.

<i class="bi bi-chevron-right"></i> Das Benutzerkonto darf nicht an Dritte weitergegeben werden. Vermuteter Missbrauch des eigenen Kontos muss unverzüglich gemeldet werden.

<i class="bi bi-chevron-right"></i> Der frei wählbare Benutzername darf keine Schutzrechte Dritter verletzen. Insbesondere dürfen keine Namen von Vereinen oder Spieler aus der realen Fussballwelt benutzt werden.

<i class="bi bi-chevron-right"></i> Die Mitgliedschaft kann jederzeit und ohne Angabe von Gründen von beiden Vertragsparteien gekündigt werden.

<i class="bi bi-chevron-right"></i> Der Betreiber behält sich das Recht vor, diese Teilnahmebestimmungen jederzeit zu ändern oder zu ergänzen.

<h2>Datenschutz</h2>
<i class="bi bi-chevron-right"></i> Der Betreiber speichert personenbezogene Daten nur soweit rechtlich zulässig und sofern sie für den Spielbetrieb nötig sind. Darunter fallen unter anderem die IP-Adresse des Nutzers und seine E-Mail-Adresse.

<i class="bi bi-chevron-right"></i> Der Nutzer hat jederzeit das Recht, Art und Umfang seiner gespeicherten Daten unentgeltlich bei dem Betreiber zu erfragen.

<i class="bi bi-chevron-right"></i> Für den Betrieb der Webseite ist es erforderlich, sogenannte "Cookies" auf Ihrem Gerät zu speichern. Dabei handelt es sich um kleine Textdateien, die Informationen über Sie zur Wiedererkennung für das System enthalten.

    '),
('termsandconditions', 'en', '<h2>Game Membership</h2>
<i class="bi bi-chevron-right"></i> Only one user account per member is permitted.

<i class="bi bi-chevron-right"></i> The user account must not be shared with third parties. Suspected abuse must be reported immediately.

<i class="bi bi-chevron-right"></i> Your chosen username must not violate any third-party rights. In particular, you may not use names of clubs or players from the real soccer world.

<i class="bi bi-chevron-right"></i> The membership can be canceled by either party at any time without prior notice.

<i class="bi bi-chevron-right"></i> The provider reserves the right to change these terms and conditions at any time.

<h2>Data Privacy</h2>
<i class="bi bi-chevron-right"></i> The provider stores personal data only as permitted by law and required to operate the service. This includes the user''s IP and email address.

<i class="bi bi-chevron-right"></i> Users may request free information about the type and amount of data stored by the provider.

<i class="bi bi-chevron-right"></i> This website requires cookies to function properly. Cookies are small text files that help the system identify your account.

    ');

INSERT IGNORE INTO ws3_jobs
  (id, name, name_de, class, `interval`, last_ping, stop, error, inittime)
VALUES
  ('addplyr', 'Add players without team to transfer market', 'Vereinslose Spieler auf die Transferliste setzen', 'AddPlayerWithoutTeamToTransfermarketJob', 5, 0, 1, '', 0),
  ('extransf', 'Execute open transfers', 'Offene Spielertransfers ausführen', 'ExecuteTransfersJob', 5, 0, 1, '', 0),
  ('sim', 'Simulate open matches', 'Offene Spiele simulieren', 'SimulateMatchesJob', 1, 0, 1, '', 0),
  ('usractv', 'Compute and update user inactivity', 'Benutzerinaktivität berechnen und aktualisieren', 'UserInactivityCheckJob', 20, 0, 1, '', 0),
  ('stats', 'Compute and update league statistics', 'Ligastatistiken berechnen und aktualisieren', 'UpdateStatisticsJob', 30, 0, 1, '', 0),
  ('stadium', 'Accept stadium construction works and training camp bookings', 'Fällige Stadionerweiterungen und Trainingslager ausführen', 'AcceptStadiumConstructionWorkJob', 30, 0, 1, '', 0);
