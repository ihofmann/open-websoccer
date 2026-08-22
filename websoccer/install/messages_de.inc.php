<?php 

$messages["button_next"] = "Weiter";
$messages["requires_value"] = "Erfordert eine Eingabe";

$messages["check_title"] = "Systemanforderungen testen";


$messages["check_head_requirement"] = "Anforderung";
$messages["check_head_required_value"] = "Erforderlicher Wert";
$messages["check_head_actual_value"] = "Tatsächlicher Wert";

$messages["check_req_yes"] = "Ja";
$messages["check_req_no"] = "Nein";
$messages["check_req_php"] = "PHP";
$messages["check_req_json"] = "JSON-Unterstützung in PHP ist aktiviert";
$messages["check_req_gd"] = "PHP Bibliothek GD ist installiert";
$messages["check_req_safemode"] = "PHP-Konfigurtion: safe_mode";
$messages["check_req_off"] = "off";
$messages["check_req_on"] = "on";
$messages["check_req_writable"] = "Datei/Ordner hat Schreibrechte (in Linux, führe aus: CHMOD a+w <filename>): ";

$messages["check_req_error"] = "Die Mindestanforderungen sind auf diesem Webserver nicht erfüllt. Du kannst die Software mit dieser Konfiguration nicht installieren. Kontaktiere deinen Webhoster oder den Hersteller.";

$messages["config_formtitle"] = "Fülle das Formular komplett aus";

$messages["label_db_host"] = "Datenbank Server (Host)";
$messages["label_db_host_help"] = "Für gewöhnlich 'localhost'";
$messages["label_db_name"] = "Datenbank Name";
$messages["label_db_user"] = "Datenbank Benutzer (User)";
$messages["label_db_password"] = "Datenbank Passwort";
$messages["label_db_prefix"] = "Tabellenprefix";
$messages["label_db_prefix_help"] = "optional; Nur erforderlich, wenn du von einem alten Projekt migrierst.";

$messages["label_projectname"] = "Projektname";
$messages["label_projectname_help"] = "Kann später noch geändert werden.";
$messages["label_serial"] = "Seriennummer";
$messages["label_serial_help"] = "Kannst du der Bestellbestätigung entnehmen.";
$messages["label_url"] = "Domain";
$messages["label_url_help"] = "Vollständige Internetadresse zu deiner Website, ohne Pfadangabe (siehe nachfolgendes Feld).";
$messages["label_context_root"] = "Pfad zum Skript (Context Root)";
$messages["label_context_root_help"] = "Pfad zum Websoccer-Ordner auf dem Webserver, ohne endendes &quot;/&quot;.";
$messages["label_systememail"] = "System E-Mail";
$messages["label_systememail_help"] = "Absenderadresse aller vom System versendeten E-Mails. Kann später noch geändert werden.";

$messages["err_already_installed"] = "Die Installation wurde offensichtlich bereits ausgeführt. Für eine Neuinstallation musst du die Datei /admin/config/config.inc.php leeren.";

$messages["invalid_db_credentials"] = "Es konnte keine Verbindung mit der Datenbank aufgebaut werden. Prüfe die eingegebenen Daten.";

$messages["predb_title"] = "Neuinstallation oder Migration?";

$messages["predb_label_new"] = "Software komplett erstmals auf diesem Server installieren.";
$messages["predb_label_migrate"] = "Bestehende Originadatentabellen der Version <i>H&amp;H WebSoccer 2.91</i> anpassen und weiterverwenden.";

$messages["predb_label_warning"] = "Das Laden der nächsten Seite kann einige Sekunden in Anspruch nehmen. Klicke auf keinen Fall ein zweites mal auf den Button, sondern warte bis der Ladevorgang abgeschlossen ist.";

$messages["user_formtitle"] = "Benutzer für AdminCenter erstellen";
$messages["label_name"] = "Benutzername";
$messages["label_password"] = "Passwort";
$messages["label_email"] = "E-Mail";

$messages["final_success_alert"] = "Glückwunsch, die Software wurde erfolgreich installiert!";
$messages["final_success_note"] = "Lösche nun unbedingt das komplette Verzeichnis <i>/install</i> innerhalb deines Websoccer-Ordners auf diesem Server!";
$messages["final_link"] = "Als Administrator anmelden";
?>
