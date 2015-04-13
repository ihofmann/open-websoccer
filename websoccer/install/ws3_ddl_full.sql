CREATE TABLE ws3_admin (
  id SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40) NULL,
  passwort VARCHAR(64) NULL,
  passwort_neu VARCHAR(64) NULL,
  passwort_neu_angefordert INT(11) NOT NULL DEFAULT 0,
  passwort_salt VARCHAR(5) NULL,
  email VARCHAR(100) NULL,
  lang VARCHAR(2) NULL,
  r_admin ENUM('1','0') NOT NULL DEFAULT '0',
  r_adminuser ENUM('1','0') NOT NULL DEFAULT '0',
  r_user ENUM('1','0') NOT NULL DEFAULT '0',
  r_daten ENUM('1','0') NOT NULL DEFAULT '0',
  r_staerken ENUM('1','0') NOT NULL DEFAULT '0',
  r_spiele ENUM('1','0') NOT NULL DEFAULT '0',
  r_news ENUM('1','0') NOT NULL DEFAULT '0',
  r_faq ENUM('1','0') NOT NULL DEFAULT '0',
  r_umfrage ENUM('1','0') NOT NULL DEFAULT '0',
  r_kalender ENUM('1','0') NOT NULL DEFAULT '0',
  r_seiten ENUM('1','0') NOT NULL DEFAULT '0',
  r_design ENUM('1','0') NOT NULL DEFAULT '0',
  r_demo ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_user (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nick VARCHAR(50) NULL,
  passwort VARCHAR(64) NULL,
  passwort_neu VARCHAR(64) NULL,
  passwort_neu_angefordert INT(11) NOT NULL DEFAULT 0,  
  passwort_salt VARCHAR(5) NULL,
  tokenid VARCHAR(255) NULL,
  lang VARCHAR(2) DEFAULT 'de',
  email VARCHAR(150) NULL,
  datum_anmeldung INT(11) NOT NULL DEFAULT 0,
  schluessel VARCHAR(10) NULL,
  wunschverein VARCHAR(250) NULL,
  name VARCHAR(80) NULL,
  wohnort VARCHAR(50) NULL,
  land VARCHAR(40) NULL,
  geburtstag DATE NULL,
  beruf VARCHAR(50) NULL,
  interessen VARCHAR(250) NULL,
  lieblingsverein VARCHAR(100) NULL,
  homepage VARCHAR(250) NULL,
  icq VARCHAR(20) NULL,
  aim VARCHAR(30) NULL,
  yim VARCHAR(30) NULL,
  msn VARCHAR(30) NULL,
  lastonline INT(11) NOT NULL DEFAULT 0,
  lastaction VARCHAR(150) NULL,
  highscore INT(10) NOT NULL DEFAULT 0,
  fanbeliebtheit TINYINT(3) NOT NULL DEFAULT '50',
  c_showemail ENUM('1','0') NOT NULL DEFAULT '0',
  email_transfers ENUM('1','0') NOT NULL DEFAULT '0',
  email_pn ENUM('1','0') NOT NULL DEFAULT '0',
  history TEXT NULL,
  ip VARCHAR(25) NULL,
  ip_time INT(11) NOT NULL DEFAULT 0,
  c_hideinonlinelist ENUM('1','0') NOT NULL DEFAULT '0',
  premium_balance INT(6) NOT NULL DEFAULT 0,
  picture VARCHAR(255) NULL,
  status ENUM('1','2','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_user_inactivity (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  login TINYINT(3) NOT NULL DEFAULT '0',
  login_last INT(11) NOT NULL,
  login_check INT(11) NOT NULL,
  aufstellung TINYINT(3) NOT NULL DEFAULT '0',
  transfer TINYINT(3) NOT NULL DEFAULT '0',
  transfer_check INT(11) NOT NULL,
  vertragsauslauf TINYINT(3) NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_briefe (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  empfaenger_id INT(10) NOT NULL,
  absender_id INT(10) NOT NULL,
  absender_name VARCHAR(50) NULL,
  datum INT(10) NOT NULL,
  betreff VARCHAR(50) NULL,
  nachricht TEXT NULL,
  gelesen ENUM('1','0') NOT NULL DEFAULT '0',
  typ ENUM('eingang','ausgang') NOT NULL DEFAULT 'eingang'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_news (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  datum INT(10) NOT NULL,
  autor_id SMALLINT(5) NOT NULL,
  bild_id INT(10) NOT NULL,
  titel VARCHAR(100) NULL,
  nachricht TEXT NULL,
  linktext1 VARCHAR(100) NULL,
  linkurl1 VARCHAR(250) NULL,
  linktext2 VARCHAR(100) NULL,
  linkurl2 VARCHAR(250) NULL,
  linktext3 VARCHAR(100) NULL,
  linkurl3 VARCHAR(250) NULL,
  c_br ENUM('1','0') NOT NULL DEFAULT '0',
  c_links ENUM('1','0') NOT NULL DEFAULT '0',
  c_smilies ENUM('1','0') NOT NULL DEFAULT '0',
  status ENUM('1','2','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_liga (
  id SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NULL,
  kurz VARCHAR(5) NULL,
  land VARCHAR(25) NULL,
  p_steh TINYINT(3) NOT NULL,
  p_sitz TINYINT(3) NOT NULL,
  p_haupt_steh TINYINT(3) NOT NULL,
  p_haupt_sitz TINYINT(3) NOT NULL,
  p_vip TINYINT(3) NOT NULL,
  preis_steh SMALLINT(5) NOT NULL,
  preis_sitz SMALLINT(5) NOT NULL,
  preis_vip SMALLINT(5) NOT NULL,
  admin_id SMALLINT(5) NOT NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_tabelle_markierung (
  id SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  liga_id SMALLINT(5) NOT NULL,
  bezeichnung VARCHAR(50) NULL,
  farbe VARCHAR(10) NULL,
  platz_von SMALLINT(5) NOT NULL,
  platz_bis SMALLINT(5) NOT NULL,
  target_league_id INT(10) NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_saison (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(20) NULL,
  liga_id SMALLINT(5) NOT NULL,
  platz_1_id INT(10) NOT NULL,
  platz_2_id INT(10) NOT NULL,
  platz_3_id INT(10) NOT NULL,
  platz_4_id INT(10) NOT NULL,
  platz_5_id INT(10) NOT NULL,
  beendet ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_verein (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NULL,
  kurz VARCHAR(5) NULL,
  bild VARCHAR(100) NULL,
  liga_id SMALLINT(5) NULL,
  user_id INT(10) NULL,
  stadion_id INT(10) NULL,
  sponsor_id INT(10) NULL,
  training_id INT(5) NULL,
  platz TINYINT(2) NULL,
  sponsor_spiele SMALLINT(5) NOT NULL DEFAULT 0,
  finanz_budget INT(11) NOT NULL,
  preis_stehen SMALLINT(4) NOT NULL,
  preis_sitz SMALLINT(4) NOT NULL,
  preis_haupt_stehen SMALLINT(4) NOT NULL,
  preis_haupt_sitze SMALLINT(4) NOT NULL,
  preis_vip SMALLINT(4) NOT NULL,
  last_steh INT(6) NOT NULL DEFAULT 0,
  last_sitz INT(6) NOT NULL DEFAULT 0,
  last_haupt_steh INT(6) NOT NULL DEFAULT 0,
  last_haupt_sitz INT(6) NOT NULL DEFAULT 0,
  last_vip INT(6) NOT NULL DEFAULT 0,
  meisterschaften SMALLINT(4) NOT NULL DEFAULT 0,
  st_tore INT(6) NOT NULL DEFAULT 0,
  st_gegentore INT(6) NOT NULL DEFAULT 0,
  st_spiele SMALLINT(5) NOT NULL DEFAULT 0,
  st_siege SMALLINT(5) NOT NULL DEFAULT 0,
  st_niederlagen SMALLINT(5) NOT NULL DEFAULT 0,
  st_unentschieden SMALLINT(5) NOT NULL DEFAULT 0,
  st_punkte INT(6) NOT NULL DEFAULT 0,
  sa_tore INT(6) NOT NULL DEFAULT 0,
  sa_gegentore INT(6) NOT NULL DEFAULT 0,
  sa_spiele SMALLINT(5) NOT NULL DEFAULT 0,
  sa_siege SMALLINT(5) NOT NULL DEFAULT 0,
  sa_niederlagen SMALLINT(5) NOT NULL DEFAULT 0,
  sa_unentschieden SMALLINT(5) NOT NULL DEFAULT 0,
  sa_punkte INT(6) NOT NULL DEFAULT 0,
  min_target_rank SMALLINT(3) NOT NULL DEFAULT 0,
  history TEXT NULL,
  scouting_last_execution INT(11) NOT NULL DEFAULT 0,
  nationalteam ENUM('1', '0') NOT NULL DEFAULT '0',
  captain_id INT(10) NULL,
  strength TINYINT(3) NOT NULL DEFAULT 0,
  user_id_actual INT(10) NULL,
  interimmanager ENUM('1', '0') NOT NULL DEFAULT '0',
  status ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_spieler (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  vorname VARCHAR(30) NULL,
  nachname VARCHAR(30) NULL,
  kunstname VARCHAR(30) NULL,
  geburtstag DATE NOT NULL,
  verein_id INT(10) NULL,
  position ENUM('Torwart','Abwehr','Mittelfeld','Sturm') NOT NULL DEFAULT 'Mittelfeld',
  position_main ENUM('T','LV','IV', 'RV', 'LM', 'DM', 'ZM', 'OM', 'RM', 'LS', 'MS', 'RS') NULL,
  position_second ENUM('T','LV','IV', 'RV', 'LM', 'DM', 'ZM', 'OM', 'RM', 'LS', 'MS', 'RS') NULL,
  nation VARCHAR(30) NULL,
  picture VARCHAR(128) NULL,
  verletzt TINYINT(3) NOT NULL DEFAULT 0,
  gesperrt TINYINT(3) NOT NULL DEFAULT 0,
  gesperrt_cups TINYINT(3) NOT NULL DEFAULT 0,
  gesperrt_nationalteam TINYINT(3) NOT NULL DEFAULT 0,
  transfermarkt ENUM('1','0') NOT NULL DEFAULT '0',
  transfer_start INT(11) NOT NULL DEFAULT 0,
  transfer_ende INT(11) NOT NULL DEFAULT 0,
  transfer_mindestgebot INT(11) NOT NULL DEFAULT 0,
  w_staerke TINYINT(3) NOT NULL,
  w_technik TINYINT(3) NOT NULL,
  w_kondition TINYINT(3) NOT NULL,
  w_frische TINYINT(3) NOT NULL,
  w_zufriedenheit TINYINT(3) NOT NULL,
  einzeltraining ENUM('1','0') NOT NULL DEFAULT '0',
  note_last REAL(4,2) NOT NULL DEFAULT 0,
  note_schnitt REAL(4,2) NOT NULL DEFAULT 0,
  vertrag_gehalt INT(10) NOT NULL,
  vertrag_spiele SMALLINT(5) NOT NULL,
  vertrag_torpraemie INT(10) NOT NULL,
  marktwert INT(10) NOT NULL DEFAULT 0,
  st_tore INT(6) NOT NULL DEFAULT 0,
  st_assists INT(6) NOT NULL DEFAULT 0,
  st_spiele SMALLINT(5) NOT NULL DEFAULT 0,
  st_karten_gelb SMALLINT(5) NOT NULL DEFAULT 0,
  st_karten_gelb_rot SMALLINT(5) NOT NULL DEFAULT 0,
  st_karten_rot SMALLINT(5) NOT NULL DEFAULT 0,
  sa_tore INT(6) NOT NULL DEFAULT 0,
  sa_assists INT(6) NOT NULL DEFAULT 0,
  sa_spiele SMALLINT(5) NOT NULL DEFAULT 0,
  sa_karten_gelb SMALLINT(5) NOT NULL DEFAULT 0,
  sa_karten_gelb_rot SMALLINT(5) NOT NULL DEFAULT 0,
  sa_karten_rot SMALLINT(5) NOT NULL DEFAULT 0,
  history TEXT NULL,
  unsellable ENUM('1','0') NOT NULL DEFAULT '0',
  lending_fee INT(6) NOT NULL DEFAULT 0,
  lending_matches TINYINT NOT NULL DEFAULT 0,
  lending_owner_id INT(10) NULL,
  age TINYINT(3) NULL,
  status ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_transfer_angebot (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  spieler_id INT(10) NOT NULL,
  verein_id INT(10) NULL,
  user_id INT(10) NOT NULL,
  datum INT(11) NOT NULL,
  abloese INT(11) NOT NULL,
  handgeld INT(11) NOT NULL DEFAULT 0,
  vertrag_spiele SMALLINT(5) NOT NULL,
  vertrag_gehalt INT(7) NOT NULL,
  vertrag_torpraemie SMALLINT(5) NOT NULL DEFAULT 0,
  ishighest ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_stadion (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NULL,
  stadt VARCHAR(30) NULL,
  land VARCHAR(20) NULL,
  p_steh INT(6) NOT NULL,
  p_sitz INT(6) NOT NULL,
  p_haupt_steh INT(6) NOT NULL,
  p_haupt_sitz INT(6) NOT NULL,
  p_vip INT(6) NOT NULL,
  level_pitch TINYINT(2) NOT NULL DEFAULT 3,
  level_videowall TINYINT(2) NOT NULL DEFAULT 1,
  level_seatsquality TINYINT(2) NOT NULL DEFAULT 5,
  level_vipquality TINYINT(2) NOT NULL DEFAULT 5,
  maintenance_pitch TINYINT(2) NOT NULL DEFAULT 1,
  maintenance_videowall TINYINT(2) NOT NULL DEFAULT 1,
  maintenance_seatsquality TINYINT(2) NOT NULL DEFAULT 1,
  maintenance_vipquality TINYINT(2) NOT NULL DEFAULT 1,
  picture VARCHAR(128) NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_konto (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  verein_id INT(10) NOT NULL,
  absender VARCHAR(150) NULL,
  betrag INT(10) NOT NULL,
  datum INT(11) NOT NULL,
  verwendung VARCHAR(200) NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_sponsor (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NULL,
  bild VARCHAR(100) NULL,
  liga_id SMALLINT(5) NOT NULL,
  b_spiel INT(10) NOT NULL,
  b_heimzuschlag INT(10) NOT NULL,
  b_sieg INT(10) NOT NULL,
  b_meisterschaft INT(10) NOT NULL,
  max_teams SMALLINT(5) NOT NULL,
  min_platz TINYINT(3) NOT NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_training (
  id SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(30) NULL,
  w_staerke TINYINT(3) NOT NULL,
  w_technik TINYINT(3) NOT NULL,
  w_kondition TINYINT(3) NOT NULL,
  w_frische TINYINT(3) NOT NULL,
  w_zufriedenheit TINYINT(3) NOT NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_trainingslager (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NULL,
  land VARCHAR(30) NULL,
  bild VARCHAR(100) NULL,
  preis_spieler_tag INT(10) NOT NULL,
  p_staerke TINYINT(3) NOT NULL,
  p_technik TINYINT(3) NOT NULL,
  p_kondition TINYINT(3) NOT NULL,
  p_frische TINYINT(3) NOT NULL,
  p_zufriedenheit TINYINT(3) NOT NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_trainingslager_belegung (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  verein_id INT(10) NOT NULL,
  lager_id INT(10) NOT NULL,
  datum_start INT(10) NOT NULL,
  datum_ende INT(10) NOT NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_aufstellung (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  verein_id INT(10) NOT NULL,
  datum INT(11) NOT NULL,
  offensive TINYINT(3) NULL DEFAULT 50,
  spieler1 INT(10) NOT NULL,
  spieler2 INT(10) NOT NULL,
  spieler3 INT(10) NOT NULL,
  spieler4 INT(10) NOT NULL,
  spieler5 INT(10) NOT NULL,
  spieler6 INT(10) NOT NULL,
  spieler7 INT(10) NOT NULL,
  spieler8 INT(10) NOT NULL,
  spieler9 INT(10) NOT NULL,
  spieler10 INT(10) NOT NULL,
  spieler11 INT(10) NOT NULL,
  ersatz1 INT(10) NULL,
  ersatz2 INT(10) NULL,
  ersatz3 INT(10) NULL,
  ersatz4 INT(10) NULL,
  ersatz5 INT(10) NULL,
  w1_raus INT(10) NULL,
  w1_rein INT(10) NULL,
  w1_minute TINYINT(2) NULL,
  w2_raus INT(10) NULL,
  w2_rein INT(10) NULL,
  w2_minute TINYINT(2) NULL,
  w3_raus INT(10) NULL,
  w3_rein INT(10) NULL,
  w3_minute TINYINT(2) NULL,
  setup VARCHAR(16) NULL,
  w1_condition VARCHAR(16) NULL,
  w2_condition VARCHAR(16) NULL,
  w3_condition VARCHAR(16) NULL,
  longpasses ENUM('1', '0') NOT NULL DEFAULT '0',
  counterattacks ENUM('1', '0') NOT NULL DEFAULT '0',
  freekickplayer INT(10) NULL,
  w1_position VARCHAR(4) NULL,
  w2_position VARCHAR(4) NULL,
  w3_position VARCHAR(4) NULL,
  spieler1_position VARCHAR(4) NOT NULL,
  spieler2_position VARCHAR(4) NOT NULL,
  spieler3_position VARCHAR(4) NOT NULL,
  spieler4_position VARCHAR(4) NOT NULL,
  spieler5_position VARCHAR(4) NOT NULL,
  spieler6_position VARCHAR(4) NOT NULL,
  spieler7_position VARCHAR(4) NOT NULL,
  spieler8_position VARCHAR(4) NOT NULL,
  spieler9_position VARCHAR(4) NOT NULL,
  spieler10_position VARCHAR(4) NOT NULL,
  spieler11_position VARCHAR(4) NOT NULL,
  match_id INT(10) NULL REFERENCES ws3_spiel(id) ON DELETE CASCADE,
  templatename VARCHAR(24) NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_spiel (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  spieltyp ENUM('Ligaspiel','Pokalspiel','Freundschaft') NOT NULL DEFAULT 'Ligaspiel',
  elfmeter ENUM('1','0') NOT NULL DEFAULT '0',
  pokalname VARCHAR(30) NULL,
  pokalrunde VARCHAR(30) NULL,
  pokalgruppe VARCHAR(64) NULL,
  liga_id SMALLINT(5) NULL,
  saison_id INT(10) NULL,
  spieltag TINYINT(3) NULL,
  datum INT(10) NOT NULL,
  stadion_id INT(10) NULL,
  minutes TINYINT(3) NULL,
  player_with_ball INT(10) NULL,
  prev_player_with_ball INT(10) NULL,
  home_verein INT(10) NOT NULL,
  home_noformation ENUM('1','0') DEFAULT '0',
  home_offensive TINYINT(3) NULL,
  home_offensive_changed TINYINT(2) NOT NULL DEFAULT 0,
  home_tore TINYINT(2) NULL,
  home_spieler1 INT(10) NULL,
  home_spieler2 INT(10) NULL,
  home_spieler3 INT(10) NULL,
  home_spieler4 INT(10) NULL,
  home_spieler5 INT(10) NULL,
  home_spieler6 INT(10) NULL,
  home_spieler7 INT(10) NULL,
  home_spieler8 INT(10) NULL,
  home_spieler9 INT(10) NULL,
  home_spieler10 INT(10) NULL,
  home_spieler11 INT(10) NULL,
  home_ersatz1 INT(10) NULL,
  home_ersatz2 INT(10) NULL,
  home_ersatz3 INT(10) NULL,
  home_ersatz4 INT(10) NULL,
  home_ersatz5 INT(10) NULL,
  home_w1_raus INT(10) NULL,
  home_w1_rein INT(10) NULL,
  home_w1_minute TINYINT(2) NULL,
  home_w2_raus INT(10) NULL,
  home_w2_rein INT(10) NULL,
  home_w2_minute TINYINT(2) NULL,
  home_w3_raus INT(10) NULL,
  home_w3_rein INT(10) NULL,
  home_w3_minute TINYINT(2) NULL,
  gast_verein INT(10) NOT NULL,
  gast_tore TINYINT(2) NULL,
  guest_noformation ENUM('1','0') DEFAULT '0',
  gast_offensive TINYINT(3) NULL,
  gast_offensive_changed TINYINT(2) NOT NULL DEFAULT 0,
  gast_spieler1 INT(10) NULL,
  gast_spieler2 INT(10) NULL,
  gast_spieler3 INT(10) NULL,
  gast_spieler4 INT(10) NULL,
  gast_spieler5 INT(10) NULL,
  gast_spieler6 INT(10) NULL,
  gast_spieler7 INT(10) NULL,
  gast_spieler8 INT(10) NULL,
  gast_spieler9 INT(10) NULL,
  gast_spieler10 INT(10) NULL,
  gast_spieler11 INT(10) NULL,
  gast_ersatz1 INT(10) NULL,
  gast_ersatz2 INT(10) NULL,
  gast_ersatz3 INT(10) NULL,
  gast_ersatz4 INT(10) NULL,
  gast_ersatz5 INT(10) NULL,
  gast_w1_raus INT(10) NULL,
  gast_w1_rein INT(10) NULL,
  gast_w1_minute TINYINT(2) NULL,
  gast_w2_raus INT(10) NULL,
  gast_w2_rein INT(10) NULL,
  gast_w2_minute TINYINT(2) NULL,
  gast_w3_raus INT(10) NULL,
  gast_w3_rein INT(10) NULL,
  gast_w3_minute TINYINT(2) NULL,
  bericht TEXT NULL,
  zuschauer INT(6) NULL,
  berechnet ENUM('1','0') NOT NULL DEFAULT '0',
  soldout ENUM('1','0') NOT NULL DEFAULT '0',
  home_setup VARCHAR(16) NULL,
  home_w1_condition VARCHAR(16) NULL,
  home_w2_condition VARCHAR(16) NULL,
  home_w3_condition VARCHAR(16) NULL,
  gast_setup VARCHAR(16) NULL,
  gast_w1_condition VARCHAR(16) NULL,
  gast_w2_condition VARCHAR(16) NULL,
  gast_w3_condition VARCHAR(16) NULL,
  home_longpasses ENUM('1', '0') NOT NULL DEFAULT '0',
  home_counterattacks ENUM('1', '0') NOT NULL DEFAULT '0',
  gast_longpasses ENUM('1', '0') NOT NULL DEFAULT '0',
  gast_counterattacks ENUM('1', '0') NOT NULL DEFAULT '0',
  home_morale TINYINT(3) NOT NULL DEFAULT 0,
  gast_morale TINYINT(3) NOT NULL DEFAULT 0,
  home_user_id INT(10) NULL,
  gast_user_id INT(10) NULL,
  home_freekickplayer INT(10) NULL,
  home_w1_position VARCHAR(4) NULL,
  home_w2_position VARCHAR(4) NULL,
  home_w3_position VARCHAR(4) NULL,
  gast_freekickplayer INT(10) NULL,
  gast_w1_position VARCHAR(4) NULL,
  gast_w2_position VARCHAR(4) NULL,
  gast_w3_position VARCHAR(4) NULL,
  blocked ENUM('1', '0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_spiel_berechnung (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  spiel_id INT(10) NOT NULL,
  spieler_id INT(10) NOT NULL,
  team_id INT(10) NOT NULL,
  position VARCHAR(20) NULL,
  note REAL(4,2) NOT NULL,
  minuten_gespielt TINYINT(2) NOT NULL DEFAULT 0,
  karte_gelb TINYINT(1) NOT NULL DEFAULT 0,
  karte_rot TINYINT(1) NOT NULL DEFAULT 0,
  verletzt TINYINT(2) NOT NULL DEFAULT 0,
  gesperrt TINYINT(2) NOT NULL DEFAULT 0,
  tore TINYINT(2) NOT NULL DEFAULT 0,
  feld ENUM('1','Ersatzbank','Ausgewechselt') NOT NULL DEFAULT '1',
  position_main VARCHAR(5) NULL,
  age TINYINT(2) NULL,
  w_staerke TINYINT(3) NULL,
  w_technik TINYINT(3) NULL,
  w_kondition TINYINT(3) NULL,
  w_frische TINYINT(3) NULL,
  w_zufriedenheit TINYINT(3) NULL,
  ballcontacts TINYINT(3) NULL,
  wontackles TINYINT(3) NULL,
  shoots TINYINT(3) NULL,
  passes_successed TINYINT(3) NULL,
  passes_failed TINYINT(3) NULL,
  assists TINYINT(3) NULL,
  name VARCHAR(128) NULL,
  losttackles TINYINT(3) NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_spiel_text (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  aktion ENUM(  'Tor',  'Auswechslung',  'Zweikampf_gewonnen',  'Zweikampf_verloren',  'Pass_daneben',  'Torschuss_daneben',  'Torschuss_auf_Tor',  'Karte_gelb',  'Karte_rot',  'Karte_gelb_rot',  'Verletzung', 'Elfmeter_erfolg',  'Elfmeter_verschossen', 'Taktikaenderung', 'Ecke', 'Freistoss_daneben', 'Freistoss_treffer', 'Tor_mit_vorlage' ),
  nachricht VARCHAR(250) NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_transfer (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  spieler_id INT(10) NOT NULL,
  seller_user_id INT(10) NULL,
  seller_club_id INT(10) NULL,
  buyer_user_id INT(10) NULL,
  buyer_club_id INT(10) NOT NULL,
  datum INT(11) NOT NULL,
  bid_id INT(11) NOT NULL DEFAULT 0,
  directtransfer_amount INT(10) NOT NULL,
  directtransfer_player1 INT(10) NOT NULL DEFAULT 0,
  directtransfer_player2 INT(10) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_session (
  session_id CHAR(32) NOT NULL PRIMARY KEY,
  session_data TEXT NOT NULL,
  expires INT(11) NOT NULL
)  DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_matchreport (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  match_id INT(10) NOT NULL,
  message_id INT(10) NOT NULL,
  minute TINYINT(3) NOT NULL,
  goals VARCHAR(8) NULL,
  playernames VARCHAR(128) NULL,
  active_home TINYINT(1) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_trainer (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL,
  salary INT(10) NOT NULL,
  p_technique TINYINT(3) NOT NULL DEFAULT '0',
  p_stamina TINYINT(3) NOT NULL DEFAULT '0',
  premiumfee INT(10) NOT NULL DEFAULT 0
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_training_unit (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  team_id INT(10) NOT NULL,
  trainer_id INT(10) NOT NULL,
  focus ENUM('TE','STA','MOT','FR') NOT NULL DEFAULT 'TE',
  intensity TINYINT(3) NOT NULL DEFAULT '50',
  date_executed INT(10) NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_cup (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(64) NOT NULL UNIQUE,
  winner_id INT(10) NULL,
  logo VARCHAR(128) NULL,
  winner_award INT(10) NOT NULL DEFAULT 0,
  second_award INT(10) NOT NULL DEFAULT 0,
  perround_award INT(10) NOT NULL DEFAULT 0,
  archived ENUM('1','0') NOT NULL DEFAULT '0'
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

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
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_cup_round_pending (
  team_id INT(10) NOT NULL,
  cup_round_id INT(10) NOT NULL,
  PRIMARY KEY(team_id, cup_round_id)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

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
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_cup_round_group_next (
  cup_round_id INT(10) NOT NULL,
  groupname VARCHAR(64) NOT NULL,
  rank INT(4) NOT NULL DEFAULT 0,
  target_cup_round_id INT(10) NOT NULL,
  PRIMARY KEY(cup_round_id, groupname, rank)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

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
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

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
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_notification (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  eventdate INT(11) NOT NULL,
  eventtype VARCHAR(128) NULL,
  message_key VARCHAR(255) NULL,
  message_data VARCHAR(255) NULL,
  target_pageid VARCHAR(128) NULL,
  target_querystr VARCHAR(255) NULL,
  seen ENUM('1','0') NOT NULL DEFAULT '0',
  team_id INT(10) NULL REFERENCES ws3_verein(id) ON DELETE CASCADE
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

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
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

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
  home_s1_position VARCHAR(4) NULL,
  home_s2_out INT(10) NULL,
  home_s2_in INT(10) NULL,
  home_s2_minute TINYINT(3) NULL,
  home_s2_condition VARCHAR(16) NULL,
  home_s2_position VARCHAR(4) NULL,
  home_s3_out INT(10) NULL,
  home_s3_in INT(10) NULL,
  home_s3_minute TINYINT(3) NULL,
  home_s3_condition VARCHAR(16) NULL,
  home_s3_position VARCHAR(4) NULL,
  guest_team_id INT(10) NOT NULL,
  guest_noformation ENUM('1','0') DEFAULT '0',
  guest_s1_out INT(10) NULL,
  guest_s1_in INT(10) NULL,
  guest_s1_minute TINYINT(3) NULL,
  guest_s1_condition VARCHAR(16) NULL,
  guest_s1_position VARCHAR(4) NULL,
  guest_s2_out INT(10) NULL,
  guest_s2_in INT(10) NULL,
  guest_s2_minute TINYINT(3) NULL,
  guest_s2_condition VARCHAR(16) NULL,
  guest_s2_position VARCHAR(4) NULL,
  guest_s3_out INT(10) NULL,
  guest_s3_in INT(10) NULL,
  guest_s3_minute TINYINT(3) NULL,
  guest_s3_condition VARCHAR(16) NULL,
  guest_s3_position VARCHAR(4) NULL,
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
  reliability TINYINT(3) NOT NULL DEFAULT 100,
  premiumfee INT(10) NOT NULL DEFAULT 0
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
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_nationalplayer (
  team_id INT(10) NOT NULL,
  player_id INT(10) NOT NULL,
  PRIMARY KEY (team_id, player_id)
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_premiumstatement (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  action_id VARCHAR(255) NULL,
  amount INT(10) NOT NULL,
  created_date INT(11) NOT NULL,
  subject_data VARCHAR(255) NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

CREATE TABLE ws3_premiumpayment (
  id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT(10) NOT NULL,
  amount INT(10) NOT NULL,
  created_date INT(11) NOT NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

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
  rank TINYINT(3) NULL,
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
  rank TINYINT(3) NULL,
  date_recorded INT(10) NOT NULL
) DEFAULT CHARSET=utf8, ENGINE=InnoDB;

ALTER TABLE ws3_user_inactivity ADD CONSTRAINT ws3_user_inactivity_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;
ALTER TABLE ws3_briefe ADD CONSTRAINT ws3_briefe_user_id_fk FOREIGN KEY (absender_id) REFERENCES ws3_user(id) ON DELETE CASCADE;
ALTER TABLE ws3_verein ADD CONSTRAINT ws3_verein_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;
ALTER TABLE ws3_verein ADD CONSTRAINT ws3_verein_stadion_id_fk FOREIGN KEY (stadion_id) REFERENCES ws3_stadion(id) ON DELETE SET NULL;
ALTER TABLE ws3_verein ADD CONSTRAINT ws3_verein_sponsor_id_fk FOREIGN KEY (sponsor_id) REFERENCES ws3_sponsor(id) ON DELETE SET NULL;
ALTER TABLE ws3_verein ADD CONSTRAINT ws3_verein_liga_id_fk FOREIGN KEY (liga_id) REFERENCES ws3_liga(id) ON DELETE CASCADE;
ALTER TABLE ws3_spieler ADD CONSTRAINT ws3_spieler_verein_id_fk FOREIGN KEY (verein_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_konto ADD CONSTRAINT ws3_konto_verein_id_fk FOREIGN KEY (verein_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer_angebot ADD CONSTRAINT ws3_transfer_angebot_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;
ALTER TABLE ws3_trainingslager_belegung ADD CONSTRAINT ws3_trainingslager_belegung_fk FOREIGN KEY (lager_id) REFERENCES ws3_trainingslager(id) ON DELETE CASCADE;
ALTER TABLE ws3_trainingslager_belegung ADD CONSTRAINT ws3_trainingslager_verein_fk FOREIGN KEY (verein_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_aufstellung ADD CONSTRAINT ws3_aufstellung_verein_id_fk FOREIGN KEY (verein_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_spiel ADD CONSTRAINT ws3_spiel_saison_id_fk FOREIGN KEY (saison_id) REFERENCES ws3_saison(id) ON DELETE CASCADE;
ALTER TABLE ws3_spiel ADD CONSTRAINT ws3_spiel_home_id_fk FOREIGN KEY (home_verein) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_spiel ADD CONSTRAINT ws3_spiel_gast_id_fk FOREIGN KEY (gast_verein) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_spiel_berechnung ADD CONSTRAINT ws3_berechnung_spiel_id_fk FOREIGN KEY (spiel_id) REFERENCES ws3_spiel(id) ON DELETE CASCADE;
ALTER TABLE ws3_spiel_berechnung ADD CONSTRAINT ws3_berechnung_spieler_id_fk FOREIGN KEY (spieler_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer ADD CONSTRAINT ws3_transfer_spieler_id_fk FOREIGN KEY (spieler_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer ADD CONSTRAINT ws3_transfer_selleruser_fk FOREIGN KEY (seller_user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;
ALTER TABLE ws3_transfer ADD CONSTRAINT ws3_transfer_sellerclub_fk FOREIGN KEY (seller_club_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer ADD CONSTRAINT ws3_transfer_buyeruser_fk FOREIGN KEY (buyer_user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;
ALTER TABLE ws3_transfer ADD CONSTRAINT ws3_transfer_buyerclub_fk FOREIGN KEY (buyer_club_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_matchreport ADD CONSTRAINT ws3_matchreport_spiel_id_fk FOREIGN KEY (match_id) REFERENCES ws3_spiel(id) ON DELETE CASCADE;
ALTER TABLE ws3_matchreport ADD CONSTRAINT ws3_matchreport_message_id_fk FOREIGN KEY (message_id) REFERENCES ws3_spiel_text(id) ON DELETE CASCADE;
ALTER TABLE ws3_training_unit ADD CONSTRAINT ws3_training_verein_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup ADD CONSTRAINT ws3_cup_winner_id_fk FOREIGN KEY (winner_id) REFERENCES ws3_verein(id) ON DELETE SET NULL;
ALTER TABLE ws3_cup_round ADD CONSTRAINT ws3_cupround_cup_id_fk FOREIGN KEY (cup_id) REFERENCES ws3_cup(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round ADD CONSTRAINT ws3_cupround_fromwinners_id_fk FOREIGN KEY (from_winners_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round ADD CONSTRAINT ws3_cupround_fromloosers_id_fk FOREIGN KEY (from_loosers_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round_pending ADD CONSTRAINT ws3_cuproundpending_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round_pending ADD CONSTRAINT ws3_cuproundpending_round_fk FOREIGN KEY (cup_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round_group ADD CONSTRAINT ws3_cupgroup_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round_group_next ADD CONSTRAINT ws3_groupnext_round_fk FOREIGN KEY (cup_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;
ALTER TABLE ws3_cup_round_group_next ADD CONSTRAINT ws3_groupnext_tagetround_fk FOREIGN KEY (target_cup_round_id) REFERENCES ws3_cup_round(id) ON DELETE CASCADE;
ALTER TABLE ws3_team_league_statistics ADD CONSTRAINT ws3_statistics_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_team_league_statistics ADD CONSTRAINT ws3_statistics_season_id_fk FOREIGN KEY (season_id) REFERENCES ws3_saison(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer_offer ADD CONSTRAINT ws3_toffer_spieler_id_fk FOREIGN KEY (player_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer_offer ADD CONSTRAINT ws3_toffer_selleruser_fk FOREIGN KEY (sender_user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer_offer ADD CONSTRAINT ws3_toffer_sellerclub_fk FOREIGN KEY (sender_club_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_transfer_offer ADD CONSTRAINT ws3_toffer_buyerclub_fk FOREIGN KEY (receiver_club_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_notification ADD CONSTRAINT ws3_notification_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthplayer ADD CONSTRAINT ws3_youthplayer_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch_request ADD CONSTRAINT ws3_youthrequest_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch ADD CONSTRAINT ws3_youthmatch_home_id_fk FOREIGN KEY (home_team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch ADD CONSTRAINT ws3_youthmatch_guest_id_fk FOREIGN KEY (guest_team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch_player ADD CONSTRAINT ws3_ymatchplayer_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch_player ADD CONSTRAINT ws3_ymatchplayer_player_id_fk FOREIGN KEY (player_id) REFERENCES ws3_youthplayer(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch_player ADD CONSTRAINT ws3_ymatchplayer_match_id_fk FOREIGN KEY (match_id) REFERENCES ws3_youthmatch(id) ON DELETE CASCADE;
ALTER TABLE ws3_youthmatch_reportitem ADD CONSTRAINT ws3_ymatchreport_match_id_fk FOREIGN KEY (match_id) REFERENCES ws3_youthmatch(id) ON DELETE CASCADE;
ALTER TABLE ws3_stadium_construction ADD CONSTRAINT ws3_construction_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_stadium_construction ADD CONSTRAINT ws3_construction_builder_id_fk FOREIGN KEY (builder_id) REFERENCES ws3_stadium_builder(id) ON DELETE CASCADE;
ALTER TABLE ws3_teamoftheday ADD CONSTRAINT ws3_teamofday_season_id_fk FOREIGN KEY (season_id) REFERENCES ws3_saison(id) ON DELETE CASCADE;
ALTER TABLE ws3_teamoftheday ADD CONSTRAINT ws3_teamofday_player_id_fk FOREIGN KEY (player_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;
ALTER TABLE ws3_nationalplayer ADD CONSTRAINT ws3_nationalp_player_id_fk FOREIGN KEY (player_id) REFERENCES ws3_spieler(id) ON DELETE CASCADE;
ALTER TABLE ws3_nationalplayer ADD CONSTRAINT ws3_nationalp_team_id_fk FOREIGN KEY (team_id) REFERENCES ws3_verein(id) ON DELETE CASCADE;
ALTER TABLE ws3_premiumstatement ADD CONSTRAINT ws3_premium_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;
ALTER TABLE ws3_premiumpayment ADD CONSTRAINT ws3_premiumpayment_user_id_fk FOREIGN KEY (user_id) REFERENCES ws3_user(id) ON DELETE CASCADE;
ALTER TABLE ws3_verein ADD CONSTRAINT ws3_verein_original_user_id_fk FOREIGN KEY (user_id_actual) REFERENCES ws3_user(id) ON DELETE SET NULL;
ALTER TABLE ws3_spiel ADD CONSTRAINT ws3_match_home_user_id_fk FOREIGN KEY (home_user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;
ALTER TABLE ws3_spiel ADD CONSTRAINT ws3_match_guest_user_id_fk FOREIGN KEY (gast_user_id) REFERENCES ws3_user(id) ON DELETE SET NULL;

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

INSERT INTO ws3_spiel_text (aktion, nachricht) VALUES
('Tor', '<b>Tor von {sp1}!</b>'),
('Tor', '<b>{sp1} schießt..... TOR!</b>'),
('Tor', '<b>TOR - wunderschön gemacht von {sp1}</b>'),
('Tor', '<b>{sp1} schießt auf das Tor... und der Ball ist drin!</b>'),
('Auswechslung', '<i>{sp1} kommt für {sp2}.</i>'),
('Zweikampf_gewonnen', '{sp1} geht auf seinen Gegenspieler zu und gewinnt den Zweikampf!'),
('Zweikampf_gewonnen', '{sp1} in einem Zweikampf.... gewonnen!'),
('Zweikampf_gewonnen', '{sp1} läuft mit dem Ball am Fuß auf seinen Gegenspieler zu... und gewinnt den Zweikampf.'),
('Zweikampf_gewonnen', '{sp1} nimmt seinem Gegenspieler gekonnt den Ball von den Füßen.'),
('Zweikampf_verloren', '{sp1} geht auf {sp2} zu... und verliert den Zweikampf.'),
('Zweikampf_verloren', '{sp1} in einem Zweikampf.... und verliert ihn.'),
('Zweikampf_verloren', '{sp1} geht mit dem Ball am Fuß auf seinen Gegenspieler zu... und verliert ihn.'),
('Zweikampf_verloren', '{sp1} sieht seinen Gegenspieler gegenüber und lässt sich den Ball abnehmen.'),
('Pass_daneben', 'Flanke von {sp1}... in die Wolken!'),
('Pass_daneben', '{sp1} passt den Ball in die Mitte... genau auf die Füße des Gegners.'),
('Pass_daneben', '{sp1} passt den Ball steil nach vorne... Abschlag!'),
('Pass_daneben', 'Pass von {sp1}... ins Seitenaus.'),
('Torschuss_daneben', '{sp1} hat freie Bahn und schießt... weit über das Tor.'),
('Torschuss_daneben', '{sp1} schießt..... daneben.'),
('Torschuss_daneben', '{sp1} schießt auf das Tor... aber genau auf den Torwart.'),
('Torschuss_daneben', 'Kopfball {sp1}... daneben.'),
('Torschuss_daneben', '{sp1} haut mit aller Kraft auf den Ball... Abschlag.'),
('Torschuss_daneben', '{sp1} schießt..... in die Wolken.'),
('Torschuss_auf_Tor', '{sp1} schießt..... Glanzparade des Torwarts!'),
('Torschuss_auf_Tor', '{sp1} schießt auf das Tor... aber der Torwart macht einen Hechtsprung und hat den Ball.'),
('Torschuss_auf_Tor', '{sp1} hat freie Bahn und schießt... aber der Torwart kann den Ball gerade noch so um den Pfosten drehen.'),
('Torschuss_auf_Tor', '{sp1} kommt zum Kopfball... ganz knapp daneben.'),
('Tor', '<b>{sp1} kommt zum Kopfball... und da flattert der Ball im Netz!</b>'),
('Karte_gelb', '{sp1} bekommt nach einem Foul die gelbe Karte.'),
('Karte_gelb', '{sp1} sieht die gelbe Karte.'),
('Karte_gelb', '{sp1} haut seinen Gegenspieler um und bekommt dafür die gelbe Karte.'),
('Karte_rot', '<i>{sp1} springt von hinten in die Beine seines Gegenspielers und sieht sofort die Rote Karte.</i>'),
('Karte_rot', '<i>{sp1} haut seinen Gegenspieler um und sieht dafür die Rote Karte.</i>'),
('Karte_rot', '<i>{sp1} bekommt die Rote Karte wegen Prügelei.</i>'),
('Karte_gelb_rot', '<i>{sp1} sieht die Gelb-Rote Karte und muss vom Platz.</i>'),
('Karte_gelb_rot', '<i>{sp1} haut seinen Gegenspieler um und bekommt dafür die Gelb-Rote Karte.</i>'),
('Karte_rot', '<i>{sp1} sieht nach einem bösen Foul die Rote Karte und muss vom Platz.</i>'),
('Verletzung', '<i>{sp1} ist verletzt und muss vom Spielfeld getragen werden.</i>'),
('Verletzung', '<i>{sp1} hat sich verletzt und kann nicht mehr weiterspielen.</i>'),
('Elfmeter_erfolg', '{sp1} tritt an: Und trifft!'),
('Elfmeter_verschossen', '{sp1} tritt an: Aber {sp2} hält den Ball!!'),
('Elfmeter_verschossen', '{sp1} legt sich den Ball zurecht. Etwas unsicherer Anlauf... und haut den Ball über das Tor.'),
('Taktikaenderung', '{sp1} ändert die Taktik.'),
('Ecke', 'Ecke für {ma1}. {sp1} spielt auf {sp2}...'),
('Freistoss_daneben', 'Freistoß für {ma1}! {sp1} schießt, aber zu ungenau.'),
('Freistoss_treffer', '{sp1} tritt den direkten Freistoß und trifft!'),
('Tor_mit_vorlage', 'Tooor für {ma1}! {sp2} legt auf {sp1} ab, der nur noch einschieben muss.');
