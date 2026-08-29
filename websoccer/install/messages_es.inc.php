<?php 

$messages["button_next"] = "Siguiente";
$messages["requires_value"] = "Requiere un valor";

$messages["check_title"] = "Comprobar requisitos del sistema";

$messages["check_head_requirement"] = "Requisito";
$messages["check_head_required_value"] = "Valor mínimo";
$messages["check_head_actual_value"] = "Valor actual";

$messages["check_req_yes"] = "Sí";
$messages["check_req_no"] = "No";
$messages["check_req_php"] = "PHP";
$messages["check_req_json"] = "El soporte JSON está habilitado en PHP";
$messages["check_req_gd"] = "La biblioteca GD de PHP está instalada";
$messages["check_req_safemode"] = "Configuración PHP: safe_mode";
$messages["check_req_off"] = "desactivado";
$messages["check_req_on"] = "activado";
$messages["check_req_writable"] = "El archivo/directorio es escribible (en Linux, ejecuta: CHMOD a+w <filename>): ";

$messages["check_req_error"] = "Los requisitos mínimos no se cumplen en este servidor web. No puedes instalar este software con la configuración actual. Contacta a tu proveedor de alojamiento o al fabricante.";

$messages["config_formtitle"] = "Completa el formulario";

$messages["label_db_host"] = "Servidor de base de datos (Host)";
$messages["label_db_host_help"] = "generalmente 'localhost'";
$messages["label_db_name"] = "Nombre de la base de datos";
$messages["label_db_user"] = "Usuario de la base de datos";
$messages["label_db_password"] = "Contraseña de la base de datos";
$messages["label_db_prefix"] = "Prefijo de tablas";
$messages["label_db_prefix_help"] = "opcional; Solo requerido si deseas migrar desde un proyecto anterior.";

$messages["label_projectname"] = "Nombre del proyecto";
$messages["label_projectname_help"] = "Se puede cambiar más tarde.";
$messages["label_serial"] = "Número de serie";
$messages["label_serial_help"] = "Puede obtenerse de la confirmación del pedido.";
$messages["label_url"] = "Dominio del sitio web";
$messages["label_url_help"] = "URL completa (dirección de internet) de este sitio web, sin ruta al script (ver campo siguiente).";
$messages["label_context_root"] = "Ruta al script (Context Root)";
$messages["label_context_root_help"] = "Ruta a la carpeta Websoccer en el servidor web, sin barra final &quot;/&quot;.";
$messages["label_systememail"] = "Correo electrónico del sistema";
$messages["label_systememail_help"] = "Dirección de remitente de todos los correos electrónicos enviados por el sistema. Se puede cambiar más tarde.";

$messages["err_already_installed"] = "Aparentemente, la instalación ya se ha ejecutado. Para reinstalar el software, vacía el archivo /admin/config/config.inc.php.";

$messages["invalid_db_credentials"] = "No se pudo conectar a la base de datos. Verifica los datos ingresados.";

$messages["predb_title"] = "¿Nueva instalación o migración?";

$messages["predb_label_new"] = "Instalar el software por primera vez en este servidor.";
$messages["predb_label_migrate"] = "Reutilizar y actualizar las tablas de la base de datos existentes de la versión anterior <i>H&amp;H WebSoccer 2.91</i>.";

$messages["predb_label_warning"] = "La carga de la siguiente página puede tardar algunos segundos. No hagas clic una segunda vez en el botón, sino espera hasta que la página se haya cargado completamente.";

$messages["user_formtitle"] = "Crear usuario para AdminCenter";
$messages["label_name"] = "Nombre de usuario";
$messages["label_password"] = "Contraseña";
$messages["label_email"] = "Correo electrónico";


$messages["final_success_alert"] = "¡Felicidades, el software se ha instalado correctamente!";
$messages["final_success_note"] = "¡Elimina ahora la carpeta <i>/install</i> dentro de tu directorio Websoccer en el servidor!";
$messages["final_link"] = "Iniciar sesión como administrador";
?>
