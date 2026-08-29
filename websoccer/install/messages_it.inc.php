<?php 

$messages["button_next"] = "Avanti";
$messages["requires_value"] = "Richiede un valore";

$messages["check_title"] = "Verifica requisiti di sistema";

$messages["check_head_requirement"] = "Requisito";
$messages["check_head_required_value"] = "Valore minimo";
$messages["check_head_actual_value"] = "Valore effettivo";

$messages["check_req_yes"] = "Sì";
$messages["check_req_no"] = "No";
$messages["check_req_php"] = "PHP";
$messages["check_req_json"] = "Il supporto JSON è abilitato in PHP";
$messages["check_req_gd"] = "La libreria GD di PHP è installata";
$messages["check_req_safemode"] = "Impostazione PHP: safe_mode";
$messages["check_req_off"] = "off";
$messages["check_req_on"] = "on";
$messages["check_req_writable"] = "Il file/la directory è scrivibile (per Linux, esegui: CHMOD a+w <filename>): ";

$messages["check_req_error"] = "I requisiti minimi non sono soddisfatti su questo server web. Non puoi installare questo software con la configurazione fornita. Contatta il tuo provider di hosting o il fornitore.";

$messages["config_formtitle"] = "Compila il modulo";

$messages["label_db_host"] = "Server database (Host)";
$messages["label_db_host_help"] = "di solito 'localhost'";
$messages["label_db_name"] = "Nome database";
$messages["label_db_user"] = "Utente database";
$messages["label_db_password"] = "Password database";
$messages["label_db_prefix"] = "Prefisso tabelle";
$messages["label_db_prefix_help"] = "opzionale; Richiesto solo se si desidera migrare da un progetto precedente.";

$messages["label_projectname"] = "Nome progetto";
$messages["label_projectname_help"] = "Può essere modificato in seguito.";
$messages["label_serial"] = "Numero di serie";
$messages["label_serial_help"] = "Può essere reperito nella conferma dell'ordine.";
$messages["label_url"] = "Dominio sito web";
$messages["label_url_help"] = "URL completo (indirizzo internet) di questo sito web, senza percorso allo script (vedi campo sotto).";
$messages["label_context_root"] = "Percorso allo script (Context Root)";
$messages["label_context_root_help"] = "Percorso della cartella Websoccer sul server web, senza barra finale &quot;/&quot;.";
$messages["label_systememail"] = "E-mail di sistema";
$messages["label_systememail_help"] = "Indirizzo mittente di tutte le e-mail inviate dal sistema. Può essere modificato in seguito.";

$messages["err_already_installed"] = "L'installazione è stata evidentemente già eseguita. Per reinstallare il software, svuota il file /admin/config/config.inc.php.";

$messages["invalid_db_credentials"] = "Impossibile connettersi al database. Verifica i dati inseriti.";

$messages["predb_title"] = "Nuova installazione o migrazione?";

$messages["predb_label_new"] = "Installa il software per la prima volta su questo server.";
$messages["predb_label_migrate"] = "Riusa e aggiorna le tabelle del database esistenti della vecchia versione <i>H&amp;H WebSoccer 2.91</i>.";

$messages["predb_label_warning"] = "Il caricamento della pagina successiva potrebbe richiedere alcuni secondi. Non fare clic una seconda volta sul pulsante, ma attendi il caricamento completo della pagina.";

$messages["user_formtitle"] = "Crea utente per AdminCenter";
$messages["label_name"] = "Nome utente";
$messages["label_password"] = "Password";
$messages["label_email"] = "E-mail";


$messages["final_success_alert"] = "Congratulazioni, il software è stato installato con successo!";
$messages["final_success_note"] = "Elimina ora la cartella <i>/install</i> all'interno della tua directory Websoccer sul server!";
$messages["final_link"] = "Accedi come amministratore";
?>
