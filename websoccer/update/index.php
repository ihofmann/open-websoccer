<?php 
/******************************************************

  This file is part of OpenWebSoccer-Sim.

  OpenWebSoccer-Sim is free software: you can redistribute it 
  and/or modify it under the terms of the 
  GNU Lesser General Public License 
  as published by the Free Software Foundation, either version 3 of
  the License, or any later version.

  OpenWebSoccer-Sim is distributed in the hope that it will be
  useful, but WITHOUT ANY WARRANTY; without even the implied
  warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
  See the GNU Lesser General Public License for more details.

  You should have received a copy of the GNU Lesser General Public 
  License along with OpenWebSoccer-Sim.  
  If not, see <http://www.gnu.org/licenses/>.

******************************************************/


error_reporting(E_ALL);
define("BASE_FOLDER", __DIR__ ."/..");

define("WRITABLE_FOLDERS", "generated/");
define("DEFAULT_DB_PREFIX", "ws3");

define("CONFIGFILE", BASE_FOLDER . "/generated/config.inc.php");
define("CONFIGFILE_OLD", BASE_FOLDER . "/admin/config/config.inc.php");

define("DDL_FILE", "update_ddl.sql");

session_start();
$supportedLanguages = array("de" => "Deutsch", "en" => "English");

ignore_user_abort(TRUE);
set_time_limit(0);

include(BASE_FOLDER . "/classes/DbConnection.class.php");

/**
 * Step 1: Welcome Screen -> Language Selection
 */
function printWelcomeScreen() {
	global $supportedLanguages;
	
	echo "<h2>Sprache wählen / Choose language</h2>";
	
	echo "<form method=\"post\">";
	$first = TRUE;
	foreach ($supportedLanguages as $langId => $langLabel) {
		echo "<label class=\"form-check form-check-label\">";
		echo "<input type=\"radio\" class=\"form-check-input\" name=\"lang\" id=\"$langId\" value=\"$langId\"";
		if ($first) {
			echo " checked";
			$first = FALSE;
		}
		echo "> $langLabel";
		echo "</label>";
	}
	
	echo "<button type=\"submit\" class=\"btn\">Wählen / Choose</button>";
	echo "<input type=\"hidden\" name=\"action\" value=\"actionSetLanguage\">";
	echo "</form>";
}

function actionSetLanguage() {
	if (!isset($_POST["lang"])) {
		global $errors;
		$errors[] = "Please select a language.";
		return "printWelcomeScreen";
	}
	
	global $supportedLanguages;
	$lang = $_POST["lang"];
	if (key_exists($lang, $supportedLanguages)) {
		$_SESSION["lang"] = $lang;
		return "printSystemCheck";
	}
	
	return "printWelcomeScreen";
}

/**
 * Step 2: System Check
 */
function printSystemCheck($messages) {
	echo "<h2>". $messages["check_title"] . "</h2>";
	
	?>
	
	<?php
	
	$requirments = array();
	
	$writableFiles = explode(",", WRITABLE_FOLDERS);
	foreach ($writableFiles as $writableFile) {
		$file = BASE_FOLDER . "/" . $writableFile;
		
		$requirments[] = array(
				"requirement" => $messages["check_req_writable"] . " <i>" . $writableFile . "</i>",
				"min" => $messages["check_req_yes"],
				"actual" => (is__writable($file)) ? $messages["check_req_yes"] : $messages["check_req_no"],
				"status" => (is__writable($file)) ? "success" : "error"
		);
	}
	
	?>
	
	<table class="table">
		<thead>
			<tr>
				<th><?php echo $messages["check_head_requirement"] ?></th>
				<th><?php echo $messages["check_head_required_value"] ?></th>
				<th><?php echo $messages["check_head_actual_value"] ?></th>
			</tr>
		</thead>
		<tbody>
		<?php 
		$valid = TRUE;
		foreach($requirments as $requirement) {
			echo "<tr class=\"".  $requirement["status"] . "\">";
			echo "<td>" . $requirement["requirement"] . "</td>";
			echo "<td>" . $requirement["min"] . "</td>";
			echo "<td>" . $requirement["actual"] . "</td>";
			echo "</tr>";
			
			if ($requirement["status"] == "error") {
				$valid = FALSE;
			}
		}
		?>
		</tbody>
	</table>
	
	<?php 
	
	if ($valid) {
		echo "<form method=\"post\">";
		echo "<button type=\"submit\" class=\"btn\">". $messages["button_next"] . "</button>";
		echo "<input type=\"hidden\" name=\"action\" value=\"actionMoveFiles\">";
		echo "</form>";
	} else {
		echo "<p>". $messages["check_req_error"] . "</p>";
	}
}


function actionMoveFiles() {

	include(CONFIGFILE);

	$db = DbConnection::getInstance();
	$db->connect($conf["db_host"], $conf["db_user"], $conf["db_passwort"], $conf["db_name"]);
	$newsTable = $conf["db_prefix"] . "_news";
	$columnResult = $db->connection->query("SHOW COLUMNS FROM " . $newsTable . " LIKE 'bild_id'");
	if ($columnResult->num_rows > 0) {
		$db->connection->query("ALTER TABLE " . $newsTable . " DROP COLUMN bild_id");
	}
	if ($db->connection->errno) {
		$error = $db->connection->error;
		$db->close();
		throw new Exception("Database Query Error: " . $error);
	}
	$db->close();

	$fileNames = array("config.inc.php", "adminlog.php", "imprint.php", "entitylog.php");
	$oldDir = BASE_FOLDER . "/admin/config/";
	$newDir = BASE_FOLDER . "/generated/";
	
	foreach ($fileNames as $fileName) {
		if (file_exists($oldDir . $fileName)) {
			rename($oldDir . $fileName, $newDir . $fileName);
		}
	}

	return "printFinalPage";
}


/**
 * Final page
 */
function printFinalPage($messages) {
	include(CONFIGFILE);
	?>
	
	<div class="alert alert-success"><strong><?php echo $messages["final_success_alert"]; ?></strong></div>
	
	<div class="alert"><strong><?php echo $messages["final_success_note"]; ?></strong></div>
	
	<p><i class="bi bi-arrow-right"></i> <a href="<?php echo $conf["context_root"]; ?>/admin"><?php echo $messages["final_link"]; ?></a></p>
	<?php 
}
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <title>Open WebSoccer-Sim Update Installation</title>
    <link href="../assets/admincenter.css" rel="stylesheet" media="screen">
    <link rel="shortcut icon" type="image/x-icon" href="../favicon.ico" />
    <meta charset="UTF-8">
    <style type="text/css">
      body {
        padding-top: 100px;
        padding-bottom: 40px;
      }
    </style>
  </head>
  <body>
  
	<div class="container">
	
		<h1>Open WebSoccer-Sim Update Installation</h1>
		
		<hr>
		
		<?php 
		
		$errors = array();
		
		$messagesIncluded = FALSE;
		if(isset($_SESSION["lang"])) {
			include("messages_" . $_SESSION["lang"] . ".inc.php");
			$messagesIncluded = $_SESSION["lang"];
		}
		
		$action = (isset($_REQUEST["action"])) ? $_REQUEST["action"] : "";
		if (!strlen($action) || substr($action, 0, 6) !== "action") {
			$view = "printWelcomeScreen";
		} else {
			$view = $action();
		}
		
		if(isset($_SESSION["lang"]) && $_SESSION["lang"] !== $messagesIncluded) {
			include("messages_" . $_SESSION["lang"] . ".inc.php");
		}
		
		if (count($errors)) {
			foreach($errors as $error) {
				echo "<div class=\"alert alert-danger\">$error</div>";
			}
		}
		
		if (isset($messages)) {
			$view($messages);
		} else {
			$view();
		}
		
		?>
	  
      <hr>

      <footer>
        <p>Powered by <a href="http://www.websoccer-sim.com" target="_blank">Open WebSoccer-Sim</a></p>
      </footer>		  
	</div>
	
    <script src="../assets/admincenter.js"></script>
  </body>
</html>

<?php 
// real is_writable (http://www.php.net/manual/en/function.is-writable.php#73596)
function is__writable($path) {
	//will work in despite of Windows ACLs bug
	//NOTE: use a trailing slash for folders!!!
	//see http://bugs.php.net/bug.php?id=27609
	//see http://bugs.php.net/bug.php?id=30931

	if ($path[strlen($path)-1]=='/') // recursively return a temporary file path
		return is__writable($path.uniqid(mt_rand()).'.tmp');
	else if (is_dir($path))
		return is__writable($path.'/'.uniqid(mt_rand()).'.tmp');
	// check tmp file for read/write capabilities
	$rm = file_exists($path);
	$f = @fopen($path, 'a');
	if ($f===false)
		return false;
	fclose($f);
	if (!$rm)
		unlink($path);
	return true;
}
?>