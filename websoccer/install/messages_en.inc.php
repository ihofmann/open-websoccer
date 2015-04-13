<?php 

$messages["button_next"] = "Next";
$messages["requires_value"] = "Requires a value";

$messages["check_title"] = "Check System Requirements";

$messages["check_head_requirement"] = "Requirement";
$messages["check_head_required_value"] = "Minimum Value";
$messages["check_head_actual_value"] = "Actual Value";

$messages["check_req_yes"] = "Yes";
$messages["check_req_no"] = "No";
$messages["check_req_php"] = "PHP";
$messages["check_req_json"] = "JSON support is enabled in PHP";
$messages["check_req_gd"] = "PHP library GD is installed";
$messages["check_req_safemode"] = "PHP setting: safe_mode";
$messages["check_req_off"] = "off";
$messages["check_req_on"] = "on";
$messages["check_req_writable"] = "File/directory is writable (for Linux, execute: CHMOD a+w <filename>): ";

$messages["check_req_error"] = "The minimum requirements are not fulfilled on this web server. You cannot install this software with the given setup. Contact your web hoster or the vendor.";

$messages["config_formtitle"] = "Complete the form";

$messages["label_db_host"] = "Database Server (Host)";
$messages["label_db_host_help"] = "usually 'localhost'";
$messages["label_db_name"] = "Database Name";
$messages["label_db_user"] = "Database User";
$messages["label_db_password"] = "Database Password";
$messages["label_db_prefix"] = "Table Prefix";
$messages["label_db_prefix_help"] = "optional; Only required if you want to migrate from a previous project.";

$messages["label_projectname"] = "Project Name";
$messages["label_projectname_help"] = "Can be changed later.";
$messages["label_serial"] = "Serial Number";
$messages["label_serial_help"] = "Can be taken from the order confirmation.";
$messages["label_url"] = "Website Domain";
$messages["label_url_help"] = "Complete URL (internet address) of this website, without path to script (see field below).";
$messages["label_context_root"] = "Path to Script (Context Root)";
$messages["label_context_root_help"] = "Path to Websoccer-folder on web server, without ending &quot;/&quot;.";
$messages["label_systememail"] = "System E-Mail";
$messages["label_systememail_help"] = "Sender address of all e-mails sent by the system. Can be changed later.";

$messages["err_already_installed"] = "Apparently, the installation has been already executed. In order to re-install the software, please empty file /admin/config/config.inc.php.";

$messages["invalid_db_credentials"] = "Could not conncect to the database. Check the entered data.";

$messages["predb_title"] = "New installation or migration?";

$messages["predb_label_new"] = "Install software for the first time on this server.";
$messages["predb_label_migrate"] = "Reusage and update existing data base tables of old version <i>H&amp;H WebSoccer 2.91</i>.";

$messages["predb_label_warning"] = "The loading of the next page can take some seconds. Do not click a second time on the button, but wait until the page has fully loaded.";

$messages["user_formtitle"] = "Create User for AdminCenter";
$messages["label_name"] = "User Name";
$messages["label_password"] = "Password";
$messages["label_email"] = "E-Mail";

$messages["final_success_alert"] = "Congratulations, the software has been successfully installed!";
$messages["final_success_note"] = "Delete now the folder <i>/install</i> within your Websoccer drectory on the server!";
$messages["final_link"] = "Log on as administrator";
?>