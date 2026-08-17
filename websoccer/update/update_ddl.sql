-- Schema updates applied by the /update installer for existing databases.
-- The same changes are part of install/ws3_ddl_full.sql for new installations.
-- Statements are idempotent MODIFY COLUMN operations.

ALTER TABLE ws3_liga MODIFY admin_id SMALLINT(5) NOT NULL DEFAULT 0;

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
