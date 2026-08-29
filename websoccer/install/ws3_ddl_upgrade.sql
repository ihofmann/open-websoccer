  
ALTER TABLE ws3_admin ADD passwort_salt VARCHAR(5);
ALTER TABLE ws3_admin MODIFY passwort VARCHAR(64);
ALTER TABLE ws3_admin MODIFY passwort_neu VARCHAR(64);
ALTER TABLE ws3_admin ADD lang VARCHAR(2);
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

    '),
('imprint', 'de', '<h2>Impressum</h2>
<p>Angaben gemäß § 5 TMG</p>
<p>[Name des Betreibers]<br>
[Straße, Hausnummer]<br>
[PLZ, Ort]</p>
<p>Kontakt:<br>
Telefon: [Telefonnummer]<br>
E-Mail: [E-Mail-Adresse]</p>
<p>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV:<br>
[Name, Anschrift]</p>
    '),
('imprint', 'en', '<h2>Imprint</h2>
<p>Information according to § 5 TMG</p>
<p>[Operator name]<br>
[Street, number]<br>
[Postal code, city]</p>
<p>Contact:<br>
Phone: [phone number]<br>
Email: [email address]</p>
<p>Responsible for the content according to § 55 Abs. 2 RStV:<br>
[Name, address]</p>
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

ALTER TABLE ws3_user ADD passwort_salt VARCHAR(5);
ALTER TABLE ws3_user ADD tokenid VARCHAR(255);
ALTER TABLE ws3_user ADD lang VARCHAR(2) DEFAULT 'de';
ALTER TABLE ws3_user MODIFY passwort VARCHAR(64);
ALTER TABLE ws3_user MODIFY passwort_neu VARCHAR(64);
ALTER TABLE ws3_user ADD c_hideinonlinelist ENUM('1','0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_user ADD premium_balance INT(6) NOT NULL DEFAULT 0;
ALTER TABLE ws3_user ADD picture VARCHAR(255) NULL;

ALTER TABLE ws3_transfer_angebot ADD verein_id INT(10);

ALTER TABLE ws3_spieler ADD unsellable ENUM('1','0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_spieler ADD position_main ENUM('T','LV','IV', 'RV', 'LM', 'DM', 'ZM', 'OM', 'RM', 'LS', 'MS', 'RS') NULL;
ALTER TABLE ws3_spieler ADD picture VARCHAR(128) NULL;
ALTER TABLE ws3_spieler ADD position_second ENUM('T','LV','IV', 'RV', 'LM', 'DM', 'ZM', 'OM', 'RM', 'LS', 'MS', 'RS') NULL;
ALTER TABLE ws3_spieler ADD lending_fee INT(6) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler ADD lending_matches TINYINT NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler ADD lending_owner_id INT(10) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler ADD gesperrt_cups TINYINT(3) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler ADD gesperrt_nationalteam TINYINT(3) NOT NULL DEFAULT 0;

ALTER TABLE ws3_aufstellung ADD offensive TINYINT(3) NULL DEFAULT 50;
ALTER TABLE ws3_aufstellung ADD setup VARCHAR(16) NULL;
ALTER TABLE ws3_aufstellung ADD w1_condition VARCHAR(16) NULL;
ALTER TABLE ws3_aufstellung ADD w2_condition VARCHAR(16) NULL;
ALTER TABLE ws3_aufstellung ADD w3_condition VARCHAR(16) NULL;
ALTER TABLE ws3_aufstellung ADD longpasses ENUM('1', '0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_aufstellung ADD counterattacks ENUM('1', '0') NOT NULL DEFAULT '0';

ALTER TABLE ws3_spiel_berechnung ADD position_main VARCHAR(5) NULL;
ALTER TABLE ws3_spiel_berechnung ADD age TINYINT(2) NULL;
ALTER TABLE ws3_spiel_berechnung ADD w_staerke TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD w_technik TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD w_kondition TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD w_frische TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD w_zufriedenheit TINYINT(3) NULL;

ALTER TABLE ws3_spiel_berechnung ADD ballcontacts TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD wontackles TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD shoots TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD passes_successed TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD passes_failed TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD assists TINYINT(3) NULL;
ALTER TABLE ws3_spiel_berechnung ADD name VARCHAR(128) NULL;

ALTER TABLE ws3_spiel ADD minutes TINYINT(3) NULL;
ALTER TABLE ws3_spiel ADD player_with_ball INT(10) NULL;
ALTER TABLE ws3_spiel ADD prev_player_with_ball INT(10) NULL;
ALTER TABLE ws3_spiel ADD home_offensive TINYINT(3) NULL;
ALTER TABLE ws3_spiel ADD gast_offensive TINYINT(3) NULL;
ALTER TABLE ws3_spiel ADD home_offensive_changed TINYINT(2) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spiel ADD gast_offensive_changed TINYINT(2) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spiel ADD pokalgruppe VARCHAR(64) NULL;
ALTER TABLE ws3_spiel ADD soldout ENUM('1','0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_spiel ADD home_setup VARCHAR(16) NULL;
ALTER TABLE ws3_spiel ADD home_w1_condition VARCHAR(16) NULL;
ALTER TABLE ws3_spiel ADD home_w2_condition VARCHAR(16) NULL;
ALTER TABLE ws3_spiel ADD home_w3_condition VARCHAR(16) NULL;
ALTER TABLE ws3_spiel ADD gast_setup VARCHAR(16) NULL;
ALTER TABLE ws3_spiel ADD gast_w1_condition VARCHAR(16) NULL;
ALTER TABLE ws3_spiel ADD gast_w2_condition VARCHAR(16) NULL;
ALTER TABLE ws3_spiel ADD gast_w3_condition VARCHAR(16) NULL;
ALTER TABLE ws3_spiel ADD home_noformation ENUM('1','0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_spiel ADD guest_noformation ENUM('1','0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_spiel ADD home_longpasses ENUM('1', '0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_spiel ADD home_counterattacks ENUM('1', '0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_spiel ADD gast_longpasses ENUM('1', '0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_spiel ADD gast_counterattacks ENUM('1', '0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_spiel ADD home_morale TINYINT(3) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spiel ADD gast_morale TINYINT(3) NOT NULL DEFAULT 0;

ALTER TABLE ws3_spiel_text CHANGE  aktion  aktion 
	ENUM(  'Tor',  'Auswechslung',  'Zweikampf_gewonnen',  'Zweikampf_verloren',  'Pass_daneben',  'Torschuss_daneben',  'Torschuss_auf_Tor',  'Karte_gelb',  'Karte_rot',  'Karte_gelb_rot',  'Verletzung', 'Elfmeter_erfolg',  'Elfmeter_verschossen' );

ALTER TABLE ws3_tabelle_markierung ADD target_league_id INT(10) NULL;

ALTER TABLE ws3_verein ADD min_target_rank SMALLINT(3) NOT NULL DEFAULT 0;
ALTER TABLE ws3_verein ADD scouting_last_execution INT(11) NOT NULL DEFAULT 0;
ALTER TABLE ws3_verein ADD nationalteam ENUM('1', '0') NOT NULL DEFAULT '0';
ALTER TABLE ws3_verein ADD captain_id INT(10) NOT NULL DEFAULT 0;
ALTER TABLE ws3_verein ADD interimmanager ENUM('1', '0') NOT NULL DEFAULT '0';

ALTER TABLE ws3_stadion ADD level_pitch TINYINT(2) NOT NULL DEFAULT 3;
ALTER TABLE ws3_stadion ADD level_videowall TINYINT(2) NOT NULL DEFAULT 1;
ALTER TABLE ws3_stadion ADD level_seatsquality TINYINT(2) NOT NULL DEFAULT 5;
ALTER TABLE ws3_stadion ADD level_vipquality TINYINT(2) NOT NULL DEFAULT 5;
ALTER TABLE ws3_stadion ADD maintenance_pitch TINYINT(2) NOT NULL DEFAULT 1;
ALTER TABLE ws3_stadion ADD maintenance_videowall TINYINT(2) NOT NULL DEFAULT 1;
ALTER TABLE ws3_stadion ADD maintenance_seatsquality TINYINT(2) NOT NULL DEFAULT 1;
ALTER TABLE ws3_stadion ADD maintenance_vipquality TINYINT(2) NOT NULL DEFAULT 1;
	
CREATE TABLE ws3_transfer (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  spieler_id INT(10) NOT NULL,
  seller_user_id INT(10) NULL,
  seller_club_id INT(10) NULL,
  buyer_user_id INT(10) NOT NULL,
  buyer_club_id INT(10) NOT NULL,
  datum INT(11) NOT NULL,
  bid_id INT(11) NOT NULL,
  directtransfer_amount INT(10) NOT NULL,
  directtransfer_player1 INT(10) NOT NULL DEFAULT 0,
  directtransfer_player2 INT(10) NOT NULL DEFAULT 0
);

CREATE TABLE ws3_session (
  session_id CHAR(32) NOT NULL PRIMARY KEY,
  session_data TEXT NOT NULL,
  expires INT(11) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE ws3_matchreport (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  match_id INT(10) NOT NULL,
  message_id INT(10) NOT NULL,
  minute TINYINT(3) NOT NULL,
  goals VARCHAR(8) NULL,
  playernames VARCHAR(128) NULL,
  active_home TINYINT(1) NOT NULL DEFAULT 0
);

CREATE TABLE ws3_trainer (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  salary INT(10) NOT NULL,
  p_technique TINYINT(3) NOT NULL DEFAULT '0',
  p_stamina TINYINT(3) NOT NULL DEFAULT '0'
);

CREATE TABLE ws3_training_unit (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  team_id INT(10) NOT NULL,
  trainer_id INT(10) NOT NULL,
  focus ENUM('TE','STA','MOT','FR') NOT NULL DEFAULT 'TE',
  intensity TINYINT(3) NOT NULL DEFAULT '50',
  date_executed INT(10) NOT NULL DEFAULT '0'
);

CREATE TABLE ws3_cup (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL UNIQUE,
  winner_id INT(10) NULL DEFAULT 0,
  logo VARCHAR(128) NULL,
  winner_award INT(10) NOT NULL DEFAULT 0,
  second_award INT(10) NOT NULL DEFAULT 0,
  perround_award INT(10) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_cup_round (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  cup_id INT(10) NOT NULL,
  name VARCHAR(64) NOT NULL,
  from_winners_round_id INT(10) NULL,
  from_loosers_round_id INT(10) NULL,
  firstround_date INT(11) NOT NULL,
  secondround_date INT(11) NULL,
  finalround ENUM('1','0') NOT NULL DEFAULT '0',
  groupmatches ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_cup_round_pending (
  team_id INT(10) NOT NULL,
  cup_round_id INT(10) NOT NULL,
  PRIMARY KEY(team_id, cup_round_id)
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_cup_round_group (
  cup_round_id INT(10) NOT NULL,
  team_id INT(10) NOT NULL,
  name VARCHAR(64) NOT NULL,
  tab_points INT(4) NOT NULL DEFAULT 0,
  tab_goals INT(4) NOT NULL DEFAULT 0,
  tab_goalsreceived INT(4) NOT NULL DEFAULT 0,
  tab_wins INT(4) NOT NULL DEFAULT 0,
  tab_draws INT(4) NOT NULL DEFAULT 0,
  tab_losses INT(4) NOT NULL DEFAULT 0,
  PRIMARY KEY(cup_round_id, team_id)
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_cup_round_group_next (
  cup_round_id INT(10) NOT NULL,
  groupname VARCHAR(64) NOT NULL,
  `rank` INT(4) NOT NULL DEFAULT 0,
  target_cup_round_id INT(10) NOT NULL,
  PRIMARY KEY(cup_round_id, groupname, `rank`)
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_team_league_statistics (
  team_id INT(10) NOT NULL,
  season_id INT(10) NOT NULL,
  total_points INT(6) NOT NULL DEFAULT 0,
  total_goals INT(6) NOT NULL DEFAULT 0,
  total_goalsreceived INT(6) NOT NULL DEFAULT 0,
  total_goalsdiff INT(6) NOT NULL DEFAULT 0,
  total_wins INT(6) NOT NULL DEFAULT 0,
  total_draws INT(6) NOT NULL DEFAULT 0,
  total_losses INT(6) NOT NULL DEFAULT 0,
  home_points INT(6) NOT NULL DEFAULT 0,
  home_goals INT(6) NOT NULL DEFAULT 0,
  home_goalsreceived INT(6) NOT NULL DEFAULT 0,
  home_goalsdiff INT(6) NOT NULL DEFAULT 0,
  home_wins INT(6) NOT NULL DEFAULT 0,
  home_draws INT(6) NOT NULL DEFAULT 0,
  home_losses INT(6) NOT NULL DEFAULT 0,
  guest_points INT(6) NOT NULL DEFAULT 0,
  guest_goals INT(6) NOT NULL DEFAULT 0,
  guest_goalsreceived INT(6) NOT NULL DEFAULT 0,
  guest_goalsdiff INT(6) NOT NULL DEFAULT 0,
  guest_wins INT(6) NOT NULL DEFAULT 0,
  guest_draws INT(6) NOT NULL DEFAULT 0,
  guest_losses INT(6) NOT NULL DEFAULT 0,
  PRIMARY KEY(team_id, season_id)
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_transfer_offer (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  player_id INT(10) NOT NULL,
  sender_user_id INT(10) NOT NULL,
  sender_club_id INT(10) NOT NULL,
  receiver_club_id INT(10) NOT NULL,
  submitted_date INT(11) NOT NULL,
  offer_amount INT(10) NOT NULL,
  offer_message VARCHAR(255) NULL,
  offer_player1 INT(10) NOT NULL DEFAULT 0,
  offer_player2 INT(10) NOT NULL DEFAULT 0,
  rejected_date INT(11) NOT NULL DEFAULT 0,
  rejected_message VARCHAR(255) NULL,
  rejected_allow_alternative ENUM('1','0') NOT NULL DEFAULT '0',
  admin_approval_pending ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_notification (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  eventdate INT(11) NOT NULL,
  eventtype VARCHAR(128) NULL,
  message_key VARCHAR(255) NULL,
  message_data VARCHAR(255) NULL,
  target_pageid VARCHAR(128) NULL,
  target_querystr VARCHAR(255) NULL,
  seen ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_youthplayer (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  team_id INT(10) NOT NULL,
  firstname VARCHAR(32) NOT NULL,
  lastname VARCHAR(32) NOT NULL,
  age TINYINT NOT NULL,
  position ENUM('Torwart','Abwehr','Mittelfeld','Sturm') NOT NULL,
  nation VARCHAR(32) NULL,
  strength TINYINT(3) NOT NULL,
  strength_last_change TINYINT(3) NOT NULL DEFAULT 0,
  st_goals SMALLINT(5) NOT NULL DEFAULT 0,
  st_matches SMALLINT(5) NOT NULL DEFAULT 0,
  st_assists SMALLINT(5) NOT NULL DEFAULT 0,
  st_cards_yellow SMALLINT(5) NOT NULL DEFAULT 0,
  st_cards_yellow_red SMALLINT(5) NOT NULL DEFAULT 0,
  st_cards_red SMALLINT(5) NOT NULL DEFAULT 0,
  transfer_fee INT(10) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_youthscout (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(32) NOT NULL,
  expertise TINYINT(3) NOT NULL,
  fee INT(10) NOT NULL,
  speciality ENUM('Torwart','Abwehr','Mittelfeld','Sturm') NULL
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_youthmatch_request (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  team_id INT(10) NOT NULL,
  matchdate INT(11) NOT NULL,
  reward INT(10) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_youthmatch (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  matchdate INT(11) NOT NULL,
  home_team_id INT(10) NOT NULL,
  home_noformation ENUM('1','0') DEFAULT '0',
  home_s1_out INT(10) NULL,
  home_s1_in INT(10) NULL,
  home_s1_minute TINYINT(3) NULL,
  home_s1_condition VARCHAR(16) NULL,
  home_s2_out INT(10) NULL,
  home_s2_in INT(10) NULL,
  home_s2_minute TINYINT(3) NULL,
  home_s2_condition VARCHAR(16) NULL,
  home_s3_out INT(10) NULL,
  home_s3_in INT(10) NULL,
  home_s3_minute TINYINT(3) NULL,
  home_s3_condition VARCHAR(16) NULL,
  guest_team_id INT(10) NOT NULL,
  guest_noformation ENUM('1','0') DEFAULT '0',
  guest_s1_out INT(10) NULL,
  guest_s1_in INT(10) NULL,
  guest_s1_minute TINYINT(3) NULL,
  guest_s1_condition VARCHAR(16) NULL,
  guest_s2_out INT(10) NULL,
  guest_s2_in INT(10) NULL,
  guest_s2_minute TINYINT(3) NULL,
  guest_s2_condition VARCHAR(16) NULL,
  guest_s3_out INT(10) NULL,
  guest_s3_in INT(10) NULL,
  guest_s3_minute TINYINT(3) NULL,
  guest_s3_condition VARCHAR(16) NULL,
  home_goals TINYINT(2) NULL,
  guest_goals TINYINT(2) NULL,
  simulated ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_youthmatch_player (
  match_id INT(10) NOT NULL,
  team_id INT(10) NOT NULL,
  player_id INT(10) NOT NULL,
  playernumber TINYINT(2) NOT NULL,
  position VARCHAR(24) NOT NULL,
  position_main VARCHAR(8) NOT NULL,
  grade REAL(4,2) NOT NULL DEFAULT 3.0,
  minutes_played TINYINT(2) NOT NULL DEFAULT 0,
  card_yellow TINYINT(1) NOT NULL DEFAULT 0,
  card_red TINYINT(1) NOT NULL DEFAULT 0,
  goals TINYINT(2) NOT NULL DEFAULT 0,
  state ENUM('1','Ersatzbank','Ausgewechselt') NOT NULL DEFAULT '1',
  strength TINYINT(3) NOT NULL,
  ballcontacts TINYINT(3) NOT NULL DEFAULT 0,
  wontackles TINYINT(3) NOT NULL DEFAULT 0,
  shoots TINYINT(3) NOT NULL DEFAULT 0,
  passes_successed TINYINT(3) NOT NULL DEFAULT 0,
  passes_failed TINYINT(3) NOT NULL DEFAULT 0,
  assists TINYINT(3) NOT NULL DEFAULT 0,
  name VARCHAR(128) NOT NULL,
  FOREIGN KEY (match_id) REFERENCES ws3_youthmatch(id) ON DELETE CASCADE,
  PRIMARY KEY (match_id, player_id)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_youthmatch_reportitem (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  match_id INT(10) NOT NULL,
  minute TINYINT(3) NOT NULL,
  message_key VARCHAR(32) NOT NULL,
  message_data VARCHAR(255) NULL,
  home_on_ball ENUM('1','0') NOT NULL DEFAULT '0',
  FOREIGN KEY (match_id) REFERENCES ws3_youthmatch(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_stadium_builder (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(32) NOT NULL,
  picture VARCHAR(128) NULL,
  fixedcosts INT(10) NOT NULL DEFAULT 0,
  cost_per_seat INT(10) NOT NULL DEFAULT 0,
  construction_time_days TINYINT(3) NOT NULL DEFAULT 0,
  construction_time_days_min TINYINT(3) NOT NULL DEFAULT 0,
  min_stadium_size INT(10) NOT NULL DEFAULT 0,
  max_stadium_size INT(10) NOT NULL DEFAULT 0,
  reliability TINYINT(3) NOT NULL DEFAULT 100
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_stadium_construction (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  team_id INT(10) NOT NULL,
  builder_id INT(10) NOT NULL,
  started INT(11) NOT NULL,
  deadline INT(11) NOT NULL,
  p_steh INT(6) NOT NULL DEFAULT 0,
  p_sitz INT(6) NOT NULL DEFAULT 0,
  p_haupt_steh INT(6) NOT NULL DEFAULT 0,
  p_haupt_sitz INT(6) NOT NULL DEFAULT 0,
  p_vip INT(6) NOT NULL DEFAULT 0,
  FOREIGN KEY (builder_id) REFERENCES ws3_stadium_builder(id) ON DELETE RESTRICT
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_teamoftheday (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  season_id INT(10) NOT NULL,
  matchday TINYINT(3) NOT NULL,
  statistic_id INT(10) NOT NULL,
  player_id INT(10) NOT NULL,
  position_main VARCHAR(20) NULL
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_nationalplayer (
  team_id INT(10) NOT NULL,
  player_id INT(10) NOT NULL,
  PRIMARY KEY (team_id, player_id)
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_premiumstatement (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  action_id VARCHAR(255) NULL,
  amount INT(10) NOT NULL,
  created_date INT(11) NOT NULL,
  subject_data VARCHAR(255) NULL
) DEFAULT CHARSET=utf8;

CREATE TABLE ws3_premiumpayment (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  amount INT(10) NOT NULL,
  created_date INT(11) NOT NULL
) DEFAULT CHARSET=utf8;

INSERT INTO ws3_spiel_text (aktion, nachricht) VALUES
('Elfmeter_erfolg', '{sp1} tritt an: Und trifft!'),
('Elfmeter_verschossen', '{sp1} tritt an: Aber {sp2} hält den Ball!!'),
('Elfmeter_verschossen', '{sp1} legt sich den Ball zurecht. Etwas unsicherer Anlauf... und haut den Ball über das Tor.');

-- delete alle existing formations because order of players has changed.
DELETE FROM ws3_aufstellung WHERE 1;


-- UPDATE 5.0.0
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE ws3_user ENGINE=InnoDB;

ALTER TABLE ws3_user_inactivity ENGINE=InnoDB;
ALTER TABLE ws3_user_inactivity MODIFY login_last INT(11) NOT NULL DEFAULT 0;
ALTER TABLE ws3_user_inactivity MODIFY login_check INT(11) NOT NULL DEFAULT 0;
ALTER TABLE ws3_user_inactivity MODIFY transfer_check INT(11) NOT NULL DEFAULT 0;
ALTER TABLE ws3_user_inactivity ADD CONSTRAINT user_inactivity_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;

ALTER TABLE ws3_briefe ENGINE=InnoDB;
ALTER TABLE ws3_briefe ADD CONSTRAINT briefe_user_id_fk FOREIGN KEY (absender_id) REFERENCES ws3_user(id) ON DELETE CASCADE;

ALTER TABLE ws3_stadion ENGINE=InnoDB;
ALTER TABLE ws3_sponsor ENGINE=InnoDB;
ALTER TABLE ws3_liga ENGINE=InnoDB;

ALTER TABLE ws3_verein ENGINE=InnoDB;
ALTER TABLE ws3_verein MODIFY liga_id SMALLINT(5) NULL;
ALTER TABLE ws3_verein MODIFY user_id INT(10) NULL;
ALTER TABLE ws3_verein MODIFY stadion_id INT(10) NULL;
ALTER TABLE ws3_verein MODIFY sponsor_id INT(10) NULL;
ALTER TABLE ws3_verein ADD CONSTRAINT verein_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;
ALTER TABLE ws3_verein ADD CONSTRAINT verein_stadion_id_fk FOREIGN KEY (stadion_id) REFERENCES ws3_stadion(id) ON DELETE SET NULL;
ALTER TABLE ws3_verein ADD CONSTRAINT verein_sponsor_id_fk FOREIGN KEY (sponsor_id) REFERENCES ws3_sponsor(id) ON DELETE SET NULL;
ALTER TABLE ws3_verein ADD CONSTRAINT verein_liga_id_fk FOREIGN KEY (liga_id) REFERENCES ws3_liga(id) ON DELETE CASCADE;

ALTER TABLE ws3_spieler ENGINE=InnoDB;
ALTER TABLE ws3_spieler MODIFY verein_id INT(10) NULL;
ALTER TABLE ws3_spieler ADD CONSTRAINT spieler_verein_id_fk FOREIGN KEY (verein_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_konto ENGINE=InnoDB;
ALTER TABLE ws3_konto ADD CONSTRAINT konto_verein_id_fk FOREIGN KEY (verein_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_transfer_angebot ENGINE=InnoDB;
ALTER TABLE ws3_transfer_angebot ADD CONSTRAINT transfer_angebot_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;

ALTER TABLE ws3_trainingslager ENGINE=InnoDB;
ALTER TABLE ws3_trainingslager_belegung ENGINE=InnoDB;
ALTER TABLE ws3_trainingslager_belegung ADD CONSTRAINT trainingslager_belegung_fk FOREIGN KEY (lager_id) REFERENCES ws3_trainingslager(id) ON DELETE CASCADE;
ALTER TABLE ws3_trainingslager_belegung ADD CONSTRAINT trainingslager_verein_fk FOREIGN KEY (verein_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_aufstellung ENGINE=InnoDB;
ALTER TABLE ws3_aufstellung MODIFY ersatz1 INT(10) NULL;
ALTER TABLE ws3_aufstellung MODIFY ersatz2 INT(10) NULL;
ALTER TABLE ws3_aufstellung MODIFY ersatz3 INT(10) NULL;
ALTER TABLE ws3_aufstellung MODIFY ersatz4 INT(10) NULL;
ALTER TABLE ws3_aufstellung MODIFY ersatz5 INT(10) NULL;
ALTER TABLE ws3_aufstellung ADD CONSTRAINT aufstellung_verein_id_fk FOREIGN KEY (verein_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_saison ENGINE=InnoDB;
ALTER TABLE ws3_spiel ENGINE=InnoDB;
ALTER TABLE ws3_spiel MODIFY saison_id INT(10) NULL;
ALTER TABLE ws3_spiel MODIFY spieltag TINYINT(3) NULL;
ALTER TABLE ws3_spiel MODIFY home_tore TINYINT(2) NULL;
ALTER TABLE ws3_spiel MODIFY gast_tore TINYINT(2) NULL;
ALTER TABLE ws3_spiel MODIFY zuschauer INT(6) NULL;
ALTER TABLE ws3_spiel ADD CONSTRAINT spiel_saison_id_fk FOREIGN KEY (saison_id) REFERENCES ws3_saison(id) ON DELETE CASCADE;
ALTER TABLE ws3_spiel ADD CONSTRAINT spiel_home_id_fk FOREIGN KEY (home_verein) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_spiel ADD CONSTRAINT spiel_gast_id_fk FOREIGN KEY (gast_verein) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_spiel_berechnung ENGINE=InnoDB;
ALTER TABLE ws3_spiel_berechnung ADD CONSTRAINT berechnung_spiel_id_fk FOREIGN KEY (spiel_id) REFERENCES ws3_spiel(id) ON DELETE CASCADE;
ALTER TABLE ws3_spiel_berechnung ADD CONSTRAINT berechnung_spieler_id_fk FOREIGN KEY (spieler_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;

ALTER TABLE ws3_transfer ENGINE=InnoDB;
ALTER TABLE ws3_transfer MODIFY seller_user_id INT(10) NULL;
ALTER TABLE ws3_transfer MODIFY buyer_user_id INT(10) NULL;
ALTER TABLE ws3_transfer ADD CONSTRAINT transfer_spieler_id_fk FOREIGN KEY (spieler_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer ADD CONSTRAINT transfer_selleruser_fk FOREIGN KEY (seller_user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;
ALTER TABLE ws3_transfer ADD CONSTRAINT transfer_sellerclub_fk FOREIGN KEY (seller_club_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer ADD CONSTRAINT transfer_buyeruser_fk FOREIGN KEY (buyer_user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;
ALTER TABLE ws3_transfer ADD CONSTRAINT transfer_buyerclub_fk FOREIGN KEY (buyer_club_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_spiel_text ENGINE=InnoDB;
ALTER TABLE ws3_matchreport ENGINE=InnoDB;
ALTER TABLE ws3_matchreport ADD CONSTRAINT matchreport_spiel_id_fk FOREIGN KEY (match_id) REFERENCES ws3_spiel(id) ON DELETE CASCADE;
ALTER TABLE ws3_matchreport ADD CONSTRAINT matchreport_message_id_fk FOREIGN KEY (message_id) REFERENCES ws3_spiel_text(id) ON DELETE CASCADE;

ALTER TABLE ws3_training_unit ENGINE=InnoDB;
ALTER TABLE ws3_training_unit ADD CONSTRAINT training_verein_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_cup ENGINE=InnoDB;
ALTER TABLE ws3_cup MODIFY winner_id INT(10) NULL;
ALTER TABLE ws3_cup ADD CONSTRAINT cup_winner_id_fk FOREIGN KEY (winner_id) REFERENCES ws3_verein(id) ON DELETE SET NULL;

ALTER TABLE ws3_cup_round ENGINE=InnoDB;
ALTER TABLE ws3_cup_round ADD CONSTRAINT cupround_cup_id_fk FOREIGN KEY (cup_id) REFERENCES ws3_cup(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round ADD CONSTRAINT cupround_fromwinners_id_fk FOREIGN KEY (from_winners_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round ADD CONSTRAINT cupround_fromloosers_id_fk FOREIGN KEY (from_loosers_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;

ALTER TABLE ws3_cup_round_pending ENGINE=InnoDB;
ALTER TABLE ws3_cup_round_pending ADD CONSTRAINT cuproundpending_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round_pending ADD CONSTRAINT cuproundpending_round_fk FOREIGN KEY (cup_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;

ALTER TABLE ws3_cup_round_group ENGINE=InnoDB;
ALTER TABLE ws3_cup_round_group ADD CONSTRAINT cupgroup_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_cup_round_group_next ENGINE=InnoDB;
ALTER TABLE ws3_cup_round_group_next ADD CONSTRAINT groupnext_round_fk FOREIGN KEY (cup_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round_group_next ADD CONSTRAINT groupnext_tagetround_fk FOREIGN KEY (target_cup_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;

ALTER TABLE ws3_team_league_statistics ENGINE=InnoDB;
ALTER TABLE ws3_team_league_statistics ADD CONSTRAINT statistics_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_team_league_statistics ADD CONSTRAINT statistics_season_id_fk FOREIGN KEY (season_id) REFERENCES ws3_saison(id) ON DELETE CASCADE;

ALTER TABLE ws3_transfer_offer ENGINE=InnoDB;
ALTER TABLE ws3_transfer_offer ADD CONSTRAINT toffer_spieler_id_fk FOREIGN KEY (player_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer_offer ADD CONSTRAINT toffer_selleruser_fk FOREIGN KEY (sender_user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer_offer ADD CONSTRAINT toffer_sellerclub_fk FOREIGN KEY (sender_club_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer_offer ADD CONSTRAINT toffer_buyerclub_fk FOREIGN KEY (receiver_club_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_notification ENGINE=InnoDB;
ALTER TABLE ws3_notification ADD CONSTRAINT notification_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;

ALTER TABLE ws3_youthplayer ENGINE=InnoDB;
ALTER TABLE ws3_youthplayer ADD CONSTRAINT youthplayer_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_youthmatch_request ENGINE=InnoDB;
ALTER TABLE ws3_youthmatch_request ADD CONSTRAINT youthrequest_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_youthmatch ENGINE=InnoDB;
ALTER TABLE ws3_youthmatch ADD CONSTRAINT youthmatch_home_id_fk FOREIGN KEY (home_team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch ADD CONSTRAINT youthmatch_guest_id_fk FOREIGN KEY (guest_team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_youthmatch_player ENGINE=InnoDB;
ALTER TABLE ws3_youthmatch_player ADD CONSTRAINT ymatchplayer_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch_player ADD CONSTRAINT ymatchplayer_player_id_fk FOREIGN KEY (player_id) REFERENCES ws3_youthplayer(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch_player ADD CONSTRAINT ymatchplayer_match_id_fk FOREIGN KEY (match_id) REFERENCES ws3_youthmatch(id) ON DELETE CASCADE;

ALTER TABLE ws3_youthmatch_reportitem ENGINE=InnoDB;
ALTER TABLE ws3_youthmatch_reportitem ADD CONSTRAINT ymatchreport_match_id_fk FOREIGN KEY (match_id) REFERENCES ws3_youthmatch(id) ON DELETE CASCADE;

ALTER TABLE ws3_stadium_builder ENGINE=InnoDB;
ALTER TABLE ws3_stadium_construction ENGINE=InnoDB;
ALTER TABLE ws3_stadium_construction ADD CONSTRAINT construction_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_stadium_construction ADD CONSTRAINT construction_builder_id_fk FOREIGN KEY (builder_id) REFERENCES ws3_stadium_builder(id) ON DELETE CASCADE;

ALTER TABLE ws3_teamoftheday ENGINE=InnoDB;
ALTER TABLE ws3_teamoftheday ADD CONSTRAINT teamofday_season_id_fk FOREIGN KEY (season_id) REFERENCES ws3_saison(id) ON DELETE CASCADE;
ALTER TABLE ws3_teamoftheday ADD CONSTRAINT teamofday_player_id_fk FOREIGN KEY (player_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;

ALTER TABLE ws3_nationalplayer ENGINE=InnoDB;
ALTER TABLE ws3_nationalplayer ADD CONSTRAINT nationalp_player_id_fk FOREIGN KEY (player_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;
ALTER TABLE ws3_nationalplayer ADD CONSTRAINT nationalp_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_premiumstatement ENGINE=InnoDB;
ALTER TABLE ws3_premiumstatement ADD CONSTRAINT premium_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;

ALTER TABLE ws3_premiumpayment ENGINE=InnoDB;
ALTER TABLE ws3_premiumpayment ADD CONSTRAINT premiumpayment_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE ws3_spiel_text CHANGE  aktion  aktion 
	ENUM(  'Tor',  'Auswechslung',  'Zweikampf_gewonnen',  'Zweikampf_verloren',  'Pass_daneben',  'Torschuss_daneben',  'Torschuss_auf_Tor',  'Karte_gelb',  'Karte_rot',  'Karte_gelb_rot',  'Verletzung', 'Elfmeter_erfolg',  'Elfmeter_verschossen', 'Taktikaenderung', 'Ecke', 'Freistoss_daneben', 'Freistoss_treffer', 'Tor_mit_vorlage' );
INSERT INTO ws3_spiel_text (aktion, nachricht) VALUES
('Taktikaenderung', '{sp1} ändert die Taktik.'),
('Ecke', 'Ecke für {ma1}. {sp1} spielt auf {sp2}...'),
('Freistoss_daneben', 'Freistoß für {ma1}! {sp1} schießt, aber zu ungenau.'),
('Freistoss_treffer', '{sp1} tritt den direkten Freistoß und trifft!'),
('Tor_mit_vorlage', 'Tooor für {ma1}! {sp2} legt auf {sp1} ab, der nur noch einschieben muss.');
	
ALTER TABLE ws3_verein ADD strength TINYINT(3) NOT NULL DEFAULT 0;
ALTER TABLE ws3_verein ADD user_id_actual INT(10) NULL;
ALTER TABLE ws3_verein ADD CONSTRAINT verein_original_user_id_fk FOREIGN KEY (user_id_actual) REFERENCES ws3_user(id) ON DELETE SET NULL;

ALTER TABLE ws3_spiel_berechnung ADD losttackles TINYINT(3) NULL;

ALTER TABLE ws3_stadion ADD picture VARCHAR(128) NULL;

ALTER TABLE ws3_spiel ADD home_user_id INT(10) NULL;
ALTER TABLE ws3_spiel ADD gast_user_id INT(10) NULL;
ALTER TABLE ws3_spiel ADD CONSTRAINT match_home_user_id_fk FOREIGN KEY (home_user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;
ALTER TABLE ws3_spiel ADD CONSTRAINT match_guest_user_id_fk FOREIGN KEY (gast_user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;
	
ALTER TABLE ws3_cup ADD archived ENUM('1','0') NOT NULL DEFAULT '0';

ALTER TABLE ws3_transfer_angebot ADD ishighest ENUM('1','0') NOT NULL DEFAULT '0';

ALTER TABLE ws3_trainer ADD premiumfee INT(10) NOT NULL DEFAULT 0;
ALTER TABLE ws3_stadium_builder ADD premiumfee INT(10) NOT NULL DEFAULT 0;

ALTER TABLE ws3_spieler ADD age TINYINT(3) NULL;

ALTER TABLE ws3_notification ADD team_id INT(10) NULL REFERENCES ws3_verein(id) ON DELETE CASCADE;

ALTER TABLE ws3_aufstellung ADD freekickplayer INT(10) NULL;
ALTER TABLE ws3_aufstellung ADD w1_position VARCHAR(4) NULL;
ALTER TABLE ws3_aufstellung ADD w2_position VARCHAR(4) NULL;
ALTER TABLE ws3_aufstellung ADD w3_position VARCHAR(4) NULL;
ALTER TABLE ws3_aufstellung ADD spieler1_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler2_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler3_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler4_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler5_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler6_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler7_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler8_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler9_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler10_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD spieler11_position VARCHAR(4) NOT NULL;
ALTER TABLE ws3_aufstellung ADD match_id INT(10) NULL REFERENCES ws3_spiel(id) ON DELETE CASCADE;
ALTER TABLE ws3_aufstellung ADD templatename VARCHAR(24) NULL;

ALTER TABLE ws3_spiel ADD home_freekickplayer INT(10) NULL;
ALTER TABLE ws3_spiel ADD home_w1_position VARCHAR(4) NULL;
ALTER TABLE ws3_spiel ADD home_w2_position VARCHAR(4) NULL;
ALTER TABLE ws3_spiel ADD home_w3_position VARCHAR(4) NULL;

ALTER TABLE ws3_spiel ADD gast_freekickplayer INT(10) NULL;
ALTER TABLE ws3_spiel ADD gast_w1_position VARCHAR(4) NULL;
ALTER TABLE ws3_spiel ADD gast_w2_position VARCHAR(4) NULL;
ALTER TABLE ws3_spiel ADD gast_w3_position VARCHAR(4) NULL;

ALTER TABLE ws3_youthmatch ADD home_s1_position VARCHAR(4) NULL;
ALTER TABLE ws3_youthmatch ADD home_s2_position VARCHAR(4) NULL;
ALTER TABLE ws3_youthmatch ADD home_s3_position VARCHAR(4) NULL;
ALTER TABLE ws3_youthmatch ADD guest_s1_position VARCHAR(4) NULL;
ALTER TABLE ws3_youthmatch ADD guest_s2_position VARCHAR(4) NULL;
ALTER TABLE ws3_youthmatch ADD guest_s3_position VARCHAR(4) NULL;

CREATE TABLE ws3_useractionlog (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  action_id VARCHAR(255) NULL,
  created_date INT(11) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_shoutmessage (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  message VARCHAR(255) NOT NULL,
  created_date INT(11) NOT NULL,
  match_id INT(10) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE,
  FOREIGN KEY (match_id) REFERENCES ws3_spiel(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_userabsence (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  deputy_id INT(10) NULL,
  from_date INT(11) NOT NULL,
  to_date INT(11) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE,
  FOREIGN KEY (deputy_id) REFERENCES ws3_user(id) ON DELETE SET NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_leaguehistory (
  team_id INT(10) NOT NULL,
  season_id INT(10) NOT NULL,
  user_id INT(10) NULL,
  matchday TINYINT(3) NULL,
  `rank` TINYINT(3) NULL,
  FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE,
  FOREIGN KEY (season_id) REFERENCES ws3_saison(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE SET NULL,
  PRIMARY KEY(team_id, season_id, matchday)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_randomevent (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  message VARCHAR(255) NULL,
  effect ENUM('money', 'player_injured', 'player_blocked', 'player_happiness', 'player_fitness', 'player_stamina') NOT NULL,
  effect_money_amount INT(10) NOT NULL DEFAULT 0,
  effect_blocked_matches INT(10) NOT NULL DEFAULT 0,
  effect_skillchange TINYINT(3) NOT NULL DEFAULT 0,
  weight TINYINT(3) NOT NULL DEFAULT 1
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_randomevent_occurrence (
  user_id INT(10) NOT NULL,
  team_id INT(10) NOT NULL,
  event_id INT(10) NOT NULL,
  occurrence_date INT(10) NOT NULL,
  FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES ws3_randomevent(id) ON DELETE CASCADE,
  PRIMARY KEY(user_id, team_id, occurrence_date)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_badge (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(128) NOT NULL,
  description VARCHAR(255) NULL,
  level ENUM('bronze', 'silver', 'gold') NOT NULL DEFAULT 'bronze',
  event ENUM('membership_since_x_days', 'win_with_x_goals_difference', 'completed_season_at_x', 'x_trades', 'cupwinner', 'stadium_construction_by_x') NOT NULL,
  event_benchmark INT(10) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_badge_user (
  user_id INT(10) NOT NULL REFERENCES ws3_user(id) ON DELETE CASCADE,
  badge_id INT(10) NOT NULL REFERENCES ws3_badge(id) ON DELETE CASCADE,
  date_rewarded INT(10) NOT NULL,
  PRIMARY KEY(user_id, badge_id)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_achievement (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL REFERENCES ws3_user(id) ON DELETE CASCADE,
  team_id INT(10) NOT NULL REFERENCES ws3_verein(id) ON DELETE CASCADE,
  season_id INT(10) NULL REFERENCES ws3_saison(id) ON DELETE CASCADE,
  cup_round_id INT(10) NULL REFERENCES ws3_cup_round(id) ON DELETE CASCADE,
  `rank` TINYINT(3) NULL,
  date_recorded INT(10) NOT NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_stadiumbuilding (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  picture VARCHAR(255) NULL,
  required_building_id INT(10) NULL,
  costs INT(10) NOT NULL,
  premiumfee INT(10) NOT NULL DEFAULT 0,
  construction_time_days TINYINT(3) NOT NULL DEFAULT 0,
  effect_training TINYINT(3) NOT NULL DEFAULT 0,
  effect_youthscouting TINYINT(3) NOT NULL DEFAULT 0,
  effect_tickets TINYINT(3) NOT NULL DEFAULT 0,
  effect_fanpopularity TINYINT(3) NOT NULL DEFAULT 0,
  effect_injury TINYINT(3) NOT NULL DEFAULT 0,
  effect_income INT(10) NOT NULL DEFAULT 0,
  FOREIGN KEY (required_building_id) REFERENCES ws3_stadiumbuilding(id) ON DELETE SET NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_buildings_of_team (
  building_id INT(10) NOT NULL,
  team_id INT(10) NOT NULL,
  construction_deadline INT(11) NULL,
  FOREIGN KEY (building_id) REFERENCES ws3_stadiumbuilding(id) ON DELETE CASCADE,
  FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE,
  PRIMARY KEY (building_id, team_id)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

-- update player's age
UPDATE ws3_spieler SET age = TIMESTAMPDIFF(YEAR,geburtstag,CURDATE()) WHERE 1;

ALTER TABLE ws3_spiel ADD blocked ENUM('1', '0') NOT NULL DEFAULT '0';

ALTER TABLE ws3_spieler ADD st_assists INT(6) NOT NULL DEFAULT 0;
ALTER TABLE ws3_spieler ADD sa_assists INT(6) NOT NULL DEFAULT 0;

ALTER TABLE ws3_news DROP COLUMN bild_id;
ALTER TABLE ws3_liga DROP COLUMN admin_id;

-- Allow creating AdminCenter records without filling every NOT NULL column.
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

-- Additional match report messages
INSERT INTO ws3_spiel_text (aktion, nachricht) VALUES
('Tor', '<b>{sp1} schießt aus kurzer Distanz - Tor!</b>'),
('Tor', '<b>{sp1} überlistet den Torwart und schießt ein!</b>'),
('Tor', '<b>{sp1} schießt kraftvoll unter die Latte - Tor!</b>'),
('Tor', '<b>{sp1} trifft per Fallrückzieher!</b>'),
('Tor', '<b>{sp1} trifft per Kopfball aus kurzer Distanz!</b>'),
('Tor', '<b>{sp1} trifft mit einem Lupfer!</b>'),
('Tor', '<b>{sp1} trifft mit einem Volleyschuss!</b>'),
('Tor', '<b>{sp1} schießt in die Maschen!</b>'),
('Tor', '<b>{sp1} schießt unter dem Torwart hindurch - Tor!</b>'),
('Tor', '<b>{sp1} trifft nach einem Schuss aus der Drehung!</b>'),
('Tor', '<b>{sp1} trifft per Rebound!</b>'),
('Tor', '<b>{sp1} trifft mit einem feinen Lupfer!</b>'),
('Tor', '<b>{sp1} schießt aus der Distanz und trifft!</b>'),
('Tor', '<b>{sp1} trifft den Ball perfekt - Tor!</b>'),
('Tor', '<b>{sp1} schießt flach ins Tor - Treffer!</b>'),
('Tor', '<b>{sp1} trifft aus der Drehung!</b>'),
('Tor', '<b>{sp1} schießt platziert ins rechte Eck - Tor!</b>'),
('Tor', '<b>{sp1} trifft per Distanzschuss!</b>'),
('Tor', '<b>{sp1} schießt aus spitzem Winkel - und trifft!</b>'),
('Tor', '<b>{sp1} schießt platziert ins linke Eck - Tor!</b>'),
('Auswechslung', '<i>{sp1} wechselt für {sp2} ein.</i>'),
('Auswechslung', '<i>{sp2} wird durch {sp1} ausgetauscht.</i>'),
('Auswechslung', '<i>{sp1} kommt für {sp2} ins Spiel.</i>'),
('Auswechslung', '<i>Einwechslung: {sp1} für {sp2}.</i>'),
('Auswechslung', '<i>{sp1} wechselt ein und ersetzt {sp2}.</i>'),
('Auswechslung', '<i>{sp2} wird ausgewechselt, {sp1} kommt rein.</i>'),
('Auswechslung', '<i>{sp2} wird ausgewechselt, für ihn kommt {sp1}.</i>'),
('Auswechslung', '<i>{sp1} kommt ins Spiel für {sp2}.</i>'),
('Auswechslung', '<i>{sp1} wird für den müden {sp2} eingewechselt.</i>'),
('Auswechslung', '<i>{sp1} wird für {sp2} eingewechselt.</i>'),
('Auswechslung', '<i>{sp1} kommt ins Spiel, {sp2} geht raus.</i>'),
('Auswechslung', '<i>Für {sp2} kommt {sp1}.</i>'),
('Auswechslung', '<i>{sp2} muss raus, {sp1} kommt.</i>'),
('Auswechslung', '<i>{sp1} wird eingewechselt und ersetzt {sp2}.</i>'),
('Auswechslung', '<i>{sp2} wird durch {sp1} ersetzt.</i>'),
('Auswechslung', '<i>{sp1} kommt zur frischen Luft für {sp2}.</i>'),
('Auswechslung', '<i>Frische Kräfte: {sp1} für {sp2}.</i>'),
('Auswechslung', '<i>{sp1} betritt den Rasen für {sp2}.</i>'),
('Auswechslung', '<i>Trainer wechselt: {sp1} für {sp2}.</i>'),
('Auswechslung', '<i>{sp1} übernimmt für {sp2}.</i>'),
('Zweikampf_gewonnen', '{sp1} ist im Zweikampf stärker als {sp2}.'),
('Zweikampf_gewonnen', '{sp1} gewinnt das Laufduell gegen {sp2}.'),
('Zweikampf_gewonnen', '{sp1} im Zweikampf gegen {sp2} - Ballgewinn!'),
('Zweikampf_gewonnen', '{sp1} blockt {sp2} und erobert den Ball.'),
('Zweikampf_gewonnen', '{sp1} gewinnt das Duell mit {sp2}.'),
('Zweikampf_gewonnen', '{sp1} klärt gegen {sp2} und hat den Ball.'),
('Zweikampf_gewonnen', '{sp1} drängt {sp2} ab und gewinnt den Ball.'),
('Zweikampf_gewonnen', '{sp1} entreißt {sp2} den Ball.'),
('Zweikampf_gewonnen', '{sp1} behauptet den Ball gegen {sp2}.'),
('Zweikampf_gewonnen', '{sp1} nimmt {sp2} den Ball ab.'),
('Zweikampf_gewonnen', '{sp1} raubt {sp2} den Ball im Zweikampf.'),
('Zweikampf_gewonnen', '{sp1} gewinnt den Ball von {sp2}.'),
('Zweikampf_gewonnen', '{sp1} setzt sich gegen {sp2} durch.'),
('Zweikampf_gewonnen', '{sp1} setzt sich im Zweikampf mit {sp2} durch.'),
('Zweikampf_gewonnen', '{sp1} bleibt gegen {sp2} am Ball.'),
('Zweikampf_gewonnen', '{sp1} ist im Duell mit {sp2} stärker.'),
('Zweikampf_gewonnen', '{sp1} ist im Tackling gegen {sp2} erfolgreich.'),
('Zweikampf_gewonnen', '{sp1} klärt im Zweikampf gegen {sp2}.'),
('Zweikampf_gewonnen', '{sp1} ist gegen {sp2} zur Stelle und gewinnt den Ball.'),
('Zweikampf_gewonnen', '{sp1} gewinnt den Ball im Zweikampf gegen {sp2}.'),
('Zweikampf_verloren', '{sp1} verliert das Duell mit {sp2}.'),
('Zweikampf_verloren', '{sp2} drängt {sp1} vom Ball ab.'),
('Zweikampf_verloren', '{sp2} setzt sich gegen {sp1} durch.'),
('Zweikampf_verloren', '{sp1} kann sich gegen {sp2} nicht durchsetzen.'),
('Zweikampf_verloren', '{sp1} muss sich {sp2} im Zweikampf geschlagen geben.'),
('Zweikampf_verloren', '{sp1} kann den Ball gegen {sp2} nicht behaupten.'),
('Zweikampf_verloren', '{sp1} unterliegt {sp2} im Duell.'),
('Zweikampf_verloren', '{sp2} nimmt {sp1} den Ball ab.'),
('Zweikampf_verloren', '{sp1} kann gegen {sp2} nicht bestehen.'),
('Zweikampf_verloren', '{sp1} verliert den Zweikampf gegen {sp2}.'),
('Zweikampf_verloren', '{sp1} verliert das Laufduell gegen {sp2}.'),
('Zweikampf_verloren', '{sp2} entreißt {sp1} den Ball.'),
('Zweikampf_verloren', '{sp2} ist im Tackling gegen {sp1} erfolgreich.'),
('Zweikampf_verloren', '{sp1} verliert das Tackling gegen {sp2}.'),
('Zweikampf_verloren', '{sp2} gewinnt den Ball von {sp1}.'),
('Zweikampf_verloren', '{sp2} ist gegen {sp1} zur Stelle und gewinnt den Ball.'),
('Zweikampf_verloren', '{sp1} ist im Zweikampf gegen {sp2} unterlegen.'),
('Zweikampf_verloren', '{sp1} unterliegt {sp2} im Tackling.'),
('Zweikampf_verloren', '{sp1} unterliegt {sp2} im Zweikampf.'),
('Zweikampf_verloren', '{sp2} bleibt gegen {sp1} am Ball.'),
('Pass_daneben', 'Der Ball von {sp1} geht verloren.'),
('Pass_daneben', '{sp1} spielt einen Fehlpass.'),
('Pass_daneben', 'Der Pass von {sp1} kommt nicht an.'),
('Pass_daneben', '{sp1} spielt den Ball zu ungenau nach vorne.'),
('Pass_daneben', '{sp1} findet keinen Abnehmer für seinen Pass.'),
('Pass_daneben', '{sp1} verliert den Ball bei der Passgabe.'),
('Pass_daneben', '{sp1} spielt den Ball zu ungenau.'),
('Pass_daneben', '{sp1} spielt den Ball zu weit vorweg.'),
('Pass_daneben', '{sp1} verzockt den Pass.'),
('Pass_daneben', '{sp1} spielt den Ball über das Feld, aber niemand ist da.'),
('Pass_daneben', '{sp1} verpasst den Steilpass.'),
('Pass_daneben', '{sp1} passt den Ball hinter den Mitspieler.'),
('Pass_daneben', '{sp1} versucht einen Pass, der abgefangen wird.'),
('Pass_daneben', '{sp1} passt den Ball in den Lauf des Gegners.'),
('Pass_daneben', '{sp1} verliert den Ball beim Passversuch.'),
('Pass_daneben', '{sp1} spielt einen schlechten Pass.'),
('Pass_daneben', '{sp1} findet mit seinem Pass keinen Mitspieler.'),
('Pass_daneben', '{sp1} findet mit der Flanke keinen Abnehmer.'),
('Pass_daneben', '{sp1} schickt den Pass in den Rücken des Gegners.'),
('Pass_daneben', '{sp1} passt direkt zum Gegner.'),
('Torschuss_daneben', '{sp1} zieht ab, aber der Ball geht über die Latte.'),
('Torschuss_daneben', '{sp1} trifft nur die Bande.'),
('Torschuss_daneben', '{sp1} verpasst die Chance und schießt daneben.'),
('Torschuss_daneben', '{sp1} schießt in die Wolken.'),
('Torschuss_daneben', '{sp1} verfehlt das Tor deutlich.'),
('Torschuss_daneben', '{sp1} zieht ab, aber der Ball geht am Tor vorbei.'),
('Torschuss_daneben', '{sp1} verzieht den Schuss.'),
('Torschuss_daneben', '{sp1} schießt den Ball am Kasten vorbei.'),
('Torschuss_daneben', '{sp1} zielt schlecht und schießt daneben.'),
('Torschuss_daneben', '{sp1} trifft den Ball nicht sauber - vorbei.'),
('Torschuss_daneben', '{sp1} schießt am Tor vorbei.'),
('Torschuss_daneben', '{sp1} schießt am linken Pfosten vorbei.'),
('Torschuss_daneben', '{sp1} setzt den Schuss neben das Tor.'),
('Torschuss_daneben', '{sp1} trifft den Ball nicht richtig - daneben.'),
('Torschuss_daneben', '{sp1} schießt aus spitzem Winkel vorbei.'),
('Torschuss_daneben', '{sp1} verfehlt das Ziel aus kurzer Distanz.'),
('Torschuss_daneben', '{sp1} verzieht den Abschluss.'),
('Torschuss_daneben', '{sp1} setzt den Kopfball über das Tor.'),
('Torschuss_daneben', '{sp1} schießt weit am Kasten vorbei.'),
('Torschuss_daneben', '{sp1} schießt am rechten Pfosten vorbei.'),
('Torschuss_auf_Tor', '{sp1} zieht ab, der Torwart wehrt den Ball ab.'),
('Torschuss_auf_Tor', '{sp1} zielt aufs Tor, der Keeper ist zur Stelle.'),
('Torschuss_auf_Tor', '{sp1} kommt zum Kopfball, der Torwart fängt ihn.'),
('Torschuss_auf_Tor', '{sp1} schießt aus kurzer Distanz, der Torwart rettet.'),
('Torschuss_auf_Tor', '{sp1} kommt zum Schuss, der Torwart fängt den Ball.'),
('Torschuss_auf_Tor', '{sp1} versucht einen Lupfer, der Torwart fängt ihn.'),
('Torschuss_auf_Tor', '{sp1} bringt den Ball aufs Tor, der Torwart hält.'),
('Torschuss_auf_Tor', '{sp1} schießt, doch der Keeper kann den Ball halten.'),
('Torschuss_auf_Tor', '{sp1} schießt, der Torwart taucht ab und hält.'),
('Torschuss_auf_Tor', '{sp1} schießt, aber der Torwart ist zur Stelle.'),
('Torschuss_auf_Tor', '{sp1} zielt gut, doch der Torwart pariert glänzend.'),
('Torschuss_auf_Tor', '{sp1} testet den Torwart, der hält aber.'),
('Torschuss_auf_Tor', '{sp1} bringt den Ball gefährlich aufs Tor, aber der Torwart hält.'),
('Torschuss_auf_Tor', '{sp1} zieht ab, der Torwart kann den Ball abwehren.'),
('Torschuss_auf_Tor', '{sp1} zielt aufs rechte Eck, der Torwart hält.'),
('Torschuss_auf_Tor', '{sp1} zieht ab, der Torwart kann den Ball gerade noch abwehren.'),
('Torschuss_auf_Tor', '{sp1} versucht es, doch der Keeper ist auf dem Posten.'),
('Torschuss_auf_Tor', '{sp1} schießt aus der Distanz, der Torwart pariert.'),
('Torschuss_auf_Tor', '{sp1} zieht ab, der Torwart pariert.'),
('Torschuss_auf_Tor', '{sp1} zielt aufs linke Eck, der Torwart hält.'),
('Karte_gelb', '{sp1} sieht Gelb wegen eines taktischen Fouls.'),
('Karte_gelb', '{sp1} foult {sp2} - Gelbe Karte.'),
('Karte_gelb', '{sp1} hält {sp2} fest und wird verwarnt.'),
('Karte_gelb', '{sp1} wird nach einem Foul an {sp2} mit Gelb verwarnt.'),
('Karte_gelb', '{sp1} foult {sp2} und sieht Gelb.'),
('Karte_gelb', '{sp1} bekommt die gelbe Karte für ein Foul.'),
('Karte_gelb', '{sp1} kriegt für das Foul die gelbe Karte.'),
('Karte_gelb', '{sp1} bekommt die gelbe Karte nach einem Foul.'),
('Karte_gelb', '{sp1} sieht Gelb für ein Foul.'),
('Karte_gelb', '{sp1} sieht nach einem Foul an {sp2} Gelb.'),
('Karte_gelb', '{sp1} foult {sp2} und erhält die gelbe Karte.'),
('Karte_gelb', '{sp1} foult {sp2} und bekommt die gelbe Karte.'),
('Karte_gelb', '{sp1} wird nach einem Foul an {sp2} verwarnt.'),
('Karte_gelb', '{sp1} sieht nach einem taktischen Foul Gelb.'),
('Karte_gelb', '{sp1} kassiert für das Foul die gelbe Karte.'),
('Karte_gelb', '{sp1} bekommt die gelbe Karte für ein Foul an {sp2}.'),
('Karte_gelb', '{sp1} wird nach einem taktischen Foul mit Gelb verwarnt.'),
('Karte_gelb', '{sp1} wird nach einem taktischen Foul verwarnt.'),
('Karte_gelb', '{sp1} bekommt nach einem Foul an {sp2} Gelb.'),
('Karte_gelb', '{sp1} wird wegen Foulspiels verwarnt.'),
('Karte_rot', '<i>{sp1} muss nach dem Foul an {sp2} vom Platz.</i>'),
('Karte_rot', '<i>{sp1} wird nach einer Tätlichkeit des Feldes verwiesen.</i>'),
('Karte_rot', '<i>{sp1} wird nach einem brutalen Foul des Feldes verwiesen.</i>'),
('Karte_rot', '<i>{sp1} bekommt nach dem Foul an {sp2} die rote Karte.</i>'),
('Karte_rot', '<i>{sp1} sieht nach einem schlimmen Foul die rote Karte.</i>'),
('Karte_rot', '<i>{sp1} foult {sp2} und fliegt vom Feld.</i>'),
('Karte_rot', '<i>{sp1} fliegt wegen eines brutalen Fouls vom Platz.</i>'),
('Karte_rot', '<i>{sp1} bekommt die rote Karte für eine Notbremse.</i>'),
('Karte_rot', '<i>{sp1} bekommt nach einer groben Unsportlichkeit Rot.</i>'),
('Karte_rot', '<i>{sp1} foult {sp2} grob und sieht Rot.</i>'),
('Karte_rot', '<i>{sp1} muss nach einem schweren Foul vom Platz.</i>'),
('Karte_rot', '<i>{sp1} foult {sp2} übel und sieht die rote Karte.</i>'),
('Karte_rot', '<i>{sp1} muss nach einer Tätlichkeit vom Feld.</i>'),
('Karte_rot', '<i>{sp1} sieht die rote Karte für ein schweres Foul.</i>'),
('Karte_rot', '<i>{sp1} sieht die rote Karte nach einem Foul an {sp2}.</i>'),
('Karte_rot', '<i>{sp1} muss nach einem Notbremse-Foul vom Platz.</i>'),
('Karte_rot', '<i>{sp1} sieht nach einer Tätlichkeit die rote Karte.</i>'),
('Karte_rot', '<i>{sp1} bekommt die rote Karte für ein grobes Foul.</i>'),
('Karte_rot', '<i>{sp1} sieht Rot wegen einer Tätlichkeit.</i>'),
('Karte_rot', '<i>{sp1} bekommt für das brutale Foul die rote Karte.</i>'),
('Karte_gelb_rot', '<i>{sp1} foult erneut {sp2} und sieht Gelb-Rot.</i>'),
('Karte_gelb_rot', '<i>{sp1} sieht die zweite gelbe Karte.</i>'),
('Karte_gelb_rot', '<i>{sp1} sieht Gelb-Rot nach einem erneuten Foul.</i>'),
('Karte_gelb_rot', '<i>{sp1} kassiert für das wiederholte Foul Gelb-Rot.</i>'),
('Karte_gelb_rot', '<i>{sp1} sieht die Gelb-Rote Karte nach einem Foul an {sp2}.</i>'),
('Karte_gelb_rot', '<i>{sp1} bekommt nach einem Foul Gelb-Rot.</i>'),
('Karte_gelb_rot', '<i>{sp1} bekommt die zweite gelbe Karte nach einem Foul.</i>'),
('Karte_gelb_rot', '<i>{sp1} foult erneut und sieht Gelb-Rot.</i>'),
('Karte_gelb_rot', '<i>{sp1} sieht nach einem weiteren Foul an {sp2} Gelb-Rot.</i>'),
('Karte_gelb_rot', '<i>{sp1} bekommt die Gelb-Rote Karte und muss raus.</i>'),
('Karte_gelb_rot', '<i>{sp1} kassiert die Gelb-Rote Karte.</i>'),
('Karte_gelb_rot', '<i>{sp1} sieht nach einem weiteren Foul Gelb-Rot.</i>'),
('Karte_gelb_rot', '<i>{sp1} kriegt Gelb-Rot für ein taktisches Foul.</i>'),
('Karte_gelb_rot', '<i>{sp1} wird nach einem weiteren Foul mit Gelb-Rot vom Feld gestellt.</i>'),
('Karte_gelb_rot', '<i>{sp1} bekommt die zweite gelbe Karte und muss vom Platz.</i>'),
('Karte_gelb_rot', '<i>{sp1} kassiert Gelb-Rot für ein taktisches Foul.</i>'),
('Karte_gelb_rot', '<i>{sp1} bekommt nach einem erneuten Foul die Gelb-Rote Karte.</i>'),
('Karte_gelb_rot', '<i>{sp1} foult {sp2} und fliegt mit Gelb-Rot vom Platz.</i>'),
('Karte_gelb_rot', '<i>{sp1} sieht nach der zweiten Verwarnung Rot.</i>'),
('Karte_gelb_rot', '<i>{sp1} muss nach Gelb-Rot vom Platz.</i>'),
('Verletzung', '<i>{sp1} zieht sich eine Verletzung zu und muss raus.</i>'),
('Verletzung', '<i>{sp1} humpelt vom Platz und muss behandelt werden.</i>'),
('Verletzung', '<i>{sp1} muss verletzt vom Spielfeld.</i>'),
('Verletzung', '<i>{sp1} muss nach der Verletzung raus.</i>'),
('Verletzung', '<i>{sp1} humpelt und muss vom Platz.</i>'),
('Verletzung', '<i>{sp1} zieht sich eine Blessur zu und muss raus.</i>'),
('Verletzung', '<i>{sp1} muss verletzt ausgewechselt werden.</i>'),
('Verletzung', '<i>{sp1} ist nach dem Tackling verletzt.</i>'),
('Verletzung', '<i>{sp1} kann nach der Verletzung nicht mehr laufen.</i>'),
('Verletzung', '<i>{sp1} ist nach dem Zusammenprall verletzt.</i>'),
('Verletzung', '<i>{sp1} zieht sich eine Knieverletzung zu.</i>'),
('Verletzung', '<i>{sp1} zieht sich eine Bänderverletzung zu.</i>'),
('Verletzung', '<i>{sp1} hat sich am Knöchel verletzt.</i>'),
('Verletzung', '<i>{sp1} zieht sich eine Oberschenkelverletzung zu.</i>'),
('Verletzung', '<i>{sp1} muss nach dem Zusammenstoß vom Feld.</i>'),
('Verletzung', '<i>{sp1} ist nach dem Foul nicht mehr in der Lage weiterzuspielen.</i>'),
('Verletzung', '<i>{sp1} kann das Spiel nicht mehr fortsetzen.</i>'),
('Verletzung', '<i>{sp1} ist verletzt und muss behandelt werden.</i>'),
('Verletzung', '<i>{sp1} kann das Spiel nach der Verletzung nicht fortsetzen.</i>'),
('Verletzung', '<i>{sp1} ist verletzt und muss ausgewechselt werden.</i>'),
('Elfmeter_erfolg', '{sp1} macht den Elfmeter rein.'),
('Elfmeter_erfolg', '{sp1} trifft den Elfmeter platziert.'),
('Elfmeter_erfolg', '{sp1} schießt den Penalty rein.'),
('Elfmeter_erfolg', '{sp1} schießt den Elfmeter rein.'),
('Elfmeter_erfolg', '{sp1} macht den Penalty rein.'),
('Elfmeter_erfolg', '{sp1} verwandelt den Strafstoß.'),
('Elfmeter_erfolg', '{sp1} trifft den Strafstoß sicher.'),
('Elfmeter_erfolg', '{sp1} trifft vom Elfmeterpunkt.'),
('Elfmeter_erfolg', '{sp1} schießt den Penalty sicher rein.'),
('Elfmeter_erfolg', '{sp1} schießt den Elfmeter unhaltbar.'),
('Elfmeter_erfolg', '{sp1} verwandelt den Elfmeter platziert.'),
('Elfmeter_erfolg', '{sp1} verwandelt den Penalty.'),
('Elfmeter_erfolg', '{sp1} schießt den Strafstoß rein.'),
('Elfmeter_erfolg', '{sp1} verwandelt den Elfmeter souverän.'),
('Elfmeter_erfolg', '{sp1} verwandelt den Elfmeter sicher.'),
('Elfmeter_erfolg', '{sp1} trifft vom Punkt!'),
('Elfmeter_erfolg', '{sp1} trifft vom Punkt sicher.'),
('Elfmeter_erfolg', '{sp1} trifft den Elfmeter!'),
('Elfmeter_erfolg', '{sp1} verwandelt vom Punkt.'),
('Elfmeter_erfolg', '{sp1} schießt den Elfmeter kalt verwandelt.'),
('Elfmeter_verschossen', '{sp1} trifft die Latte beim Elfmeter.'),
('Elfmeter_verschossen', '{sp1} schießt den Penalty über die Latte.'),
('Elfmeter_verschossen', '{sp1} schießt den Penalty zu ungenau.'),
('Elfmeter_verschossen', '{sp1} verpasst den Elfmeter knapp.'),
('Elfmeter_verschossen', '{sp1} tritt an, aber {sp2} pariert glänzend.'),
('Elfmeter_verschossen', '{sp1} schießt den Elfmeter daneben.'),
('Elfmeter_verschossen', '{sp1} tritt an, aber {sp2} hält.'),
('Elfmeter_verschossen', '{sp1} vergibt die Riesenchance vom Punkt.'),
('Elfmeter_verschossen', '{sp1} tritt an, aber der Ball geht daneben.'),
('Elfmeter_verschossen', '{sp1} schießt den Strafstoß über das Tor.'),
('Elfmeter_verschossen', '{sp1} schießt den Elfmeter an den Pfosten.'),
('Elfmeter_verschossen', '{sp1} schießt den Strafstoß an die Latte.'),
('Elfmeter_verschossen', '{sp1} schießt den Penalty am Tor vorbei.'),
('Elfmeter_verschossen', '{sp1} trifft den Pfosten.'),
('Elfmeter_verschossen', '{sp1} schießt den Strafstoß daneben.'),
('Elfmeter_verschossen', '{sp1} vergibt vom Elfmeterpunkt.'),
('Elfmeter_verschossen', '{sp1} tritt an, aber {sp2} fängt den Ball.'),
('Elfmeter_verschossen', '{sp1} verpasst den Strafstoß.'),
('Elfmeter_verschossen', '{sp1} vergibt den Elfmeter.'),
('Elfmeter_verschossen', '{sp1} kann den Strafstoß nicht reinmachen.'),
('Taktikaenderung', '{sp1} ordnet eine Defensiv-Taktik an.'),
('Taktikaenderung', '{sp1} wechselt zu einer anderen Spielweise.'),
('Taktikaenderung', '{sp1} ordnet eine taktische Korrektur an.'),
('Taktikaenderung', '{sp1} ordnet eine Offensiv-Taktik an.'),
('Taktikaenderung', '{sp1} wechselt die Taktik.'),
('Taktikaenderung', '{sp1} wechselt die Formation.'),
('Taktikaenderung', '{sp1} stellt die Taktik um.'),
('Taktikaenderung', '{sp1} wechselt in eine andere Formation.'),
('Taktikaenderung', '{sp1} stellt die Mannschaft neu auf.'),
('Taktikaenderung', '{sp1} ändert die Aufstellung des Teams.'),
('Taktikaenderung', '{sp1} ändert die Spielweise des Teams.'),
('Taktikaenderung', '{sp1} ändert die Taktik auf defensiver.'),
('Taktikaenderung', '{sp1} ordnet eine taktische Umstellung an.'),
('Taktikaenderung', '{sp1} ändert die Taktik auf offensiver.'),
('Taktikaenderung', '{sp1} ändert die Formation.'),
('Taktikaenderung', '{sp1} wechselt die taktische Ausrichtung.'),
('Taktikaenderung', '{sp1} ändert die taktische Ausrichtung.'),
('Taktikaenderung', '{sp1} ändert die Formation der Mannschaft.'),
('Taktikaenderung', '{sp1} wechselt in eine defensivere Formation.'),
('Taktikaenderung', '{sp1} stellt die Mannschaft taktisch um.'),
('Ecke', '{sp1} spielt die Ecke kurz.'),
('Ecke', '{sp1} bringt die Ecke scharf vor das Tor.'),
('Ecke', '{sp1} schlägt die Ecke vor das Tor.'),
('Ecke', '{sp1} führt die Ecke variantenreich aus.'),
('Ecke', '{sp1} führt die Ecke kurz aus.'),
('Ecke', '{sp1} spielt die Ecke zu {sp2}.'),
('Ecke', '{sp1} bringt die Ecke in die Gefahrenzone.'),
('Ecke', '{sp1} führt die Ecke aus.'),
('Ecke', '{sp1} spielt die Ecke auf den zweiten Pfosten.'),
('Ecke', '{sp1} spielt die Ecke auf den ersten Pfosten.'),
('Ecke', '{sp1} schlägt die Ecke hoch rein.'),
('Ecke', '{sp1} schlägt die Ecke rein.'),
('Ecke', '{sp1} bringt die Ecke zur Mitte.'),
('Ecke', '{sp1} führt die Ecke kurz aus auf {sp2}.'),
('Ecke', '{sp1} schlägt die Ecke in den Strafraum.'),
('Ecke', '{sp1} schlägt die Ecke direkt aufs Tor.'),
('Ecke', '{sp1} spielt die Ecke auf {sp2}.'),
('Ecke', '{sp1} führt die Ecke von links aus.'),
('Ecke', '{sp1} bringt die Ecke in den Rückraum.'),
('Ecke', '{sp1} bringt die Ecke in den Strafraum.'),
('Freistoss_daneben', '{sp1} trifft mit dem Freistoß nur die Bande.'),
('Freistoss_daneben', '{sp1} schießt den Freistoß am Tor vorbei.'),
('Freistoss_daneben', '{sp1} tritt den Freistoß, aber der Ball geht daneben.'),
('Freistoss_daneben', '{sp1} verpasst den Freistoß knapp.'),
('Freistoss_daneben', '{sp1} schießt den Freistoß zu schwach.'),
('Freistoss_daneben', '{sp1} trifft den Freistoß nicht sauber.'),
('Freistoss_daneben', '{sp1} verpasst den Freistoß deutlich.'),
('Freistoss_daneben', '{sp1} schießt den Freistoß an die Mauer.'),
('Freistoss_daneben', '{sp1} tritt an, aber der Ball geht über das Tor.'),
('Freistoss_daneben', '{sp1} tritt den Freistoß, aber {sp2} hält.'),
('Freistoss_daneben', '{sp1} trifft den Freistoß nicht richtig.'),
('Freistoss_daneben', '{sp1} verzieht den direkten Freistoß.'),
('Freistoss_daneben', '{sp1} tritt den Freistoß, aber der Ball geht vorbei.'),
('Freistoss_daneben', '{sp1} schießt den Freistoß über das Tor.'),
('Freistoss_daneben', '{sp1} schießt den Freistoß an den Pfosten.'),
('Freistoss_daneben', '{sp1} vergibt den Freistoß aus aussichtsreicher Position.'),
('Freistoss_daneben', '{sp1} schießt den Freistoß in die Wolken.'),
('Freistoss_daneben', '{sp1} vergibt die Freistoßchance.'),
('Freistoss_daneben', '{sp1} schießt den Freistoß in die Mauer.'),
('Freistoss_daneben', '{sp1} schießt den Freistoß an die Latte.'),
('Freistoss_treffer', '{sp1} schießt den Freistoß unhaltbar rein.'),
('Freistoss_treffer', '{sp1} trifft den Freistoß direkt!'),
('Freistoss_treffer', '{sp1} macht den Freistoß rein.'),
('Freistoss_treffer', '{sp1} schießt den Freistoß um die Mauer herum.'),
('Freistoss_treffer', '{sp1} verwandelt den Freistoß!'),
('Freistoss_treffer', '{sp1} schießt den Freistoß ins Tor.'),
('Freistoss_treffer', '{sp1} trifft den Freistoß kraftvoll.'),
('Freistoss_treffer', '{sp1} trifft den Freistoß überlegt.'),
('Freistoss_treffer', '{sp1} macht den direkten Freistoß rein.'),
('Freistoss_treffer', '{sp1} schießt den Freistoß flach ins Tor.'),
('Freistoss_treffer', '{sp1} trifft den Freistoß perfekt.'),
('Freistoss_treffer', '{sp1} verwandelt den Freistoß souverän.'),
('Freistoss_treffer', '{sp1} trifft per Freistoß!'),
('Freistoss_treffer', '{sp1} verwandelt den Freistoß sicher.'),
('Freistoss_treffer', '{sp1} trifft den direkten Freistoß platziert.'),
('Freistoss_treffer', '{sp1} schießt den Freistoß ins lange Eck.'),
('Freistoss_treffer', '{sp1} trifft den Freistoß über die Mauer.'),
('Freistoss_treffer', '{sp1} verwandelt den Freistoß mit Bravour.'),
('Freistoss_treffer', '{sp1} schießt den Freistoß unhaltbar.'),
('Tor_mit_vorlage', '{sp1} erzielt nach schönem Zuspiel von {sp2}.'),
('Tor_mit_vorlage', '{sp1} trifft nach schöner Vorlage von {sp2}.'),
('Tor_mit_vorlage', '{sp1} erzielt nach Vorlage von {sp2} das Tor.'),
('Tor_mit_vorlage', '{sp2} legt den Ball ideal auf {sp1}, der trifft.'),
('Tor_mit_vorlage', '{sp2} spielt {sp1} frei, der trifft.'),
('Tor_mit_vorlage', '{sp1} macht nach Vorlage von {sp2} das Tor.'),
('Tor_mit_vorlage', '{sp2} legt auf {sp1} ab, der trifft!'),
('Tor_mit_vorlage', '{sp1} trifft nach tollem Pass von {sp2}.'),
('Tor_mit_vorlage', '{sp1} verwandelt die Vorlage von {sp2}.'),
('Tor_mit_vorlage', '{sp2} spielt {sp1} an, der trifft.'),
('Tor_mit_vorlage', '{sp1} macht das Tor nach einer Vorlage von {sp2}.'),
('Tor_mit_vorlage', '{sp1} schießt nach Vorlage von {sp2} zum Tor.'),
('Tor_mit_vorlage', '{sp1} schießt nach Vorlage von {sp2} rein.'),
('Tor_mit_vorlage', '{sp1} trifft nach Vorlage von {sp2}.'),
('Tor_mit_vorlage', '{sp1} verwertet die Vorlage von {sp2} zum Tor.'),
('Tor_mit_vorlage', '{sp1} trifft nach perfekter Vorlage von {sp2}.'),
('Tor_mit_vorlage', '{sp2} schickt {sp1}, der trifft.'),
('Tor_mit_vorlage', '{sp2} spielt einen tollen Pass auf {sp1}, der trifft.'),
('Tor_mit_vorlage', '{sp2} spielt {sp1} mustergültig an, der trifft.'),
('Tor_mit_vorlage', '{sp2} bedient {sp1}, der zum Tor abschließt.');

