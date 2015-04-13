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

$entity = strtolower(trim($_REQUEST["entity"]));
if (!isset($adminpage[$entity])) {
	throw new Exception("Illegal call - unknown entity");
}

$page = json_decode($adminpage[$entity], true);

// check permission
if (!$admin["r_admin"] && !$admin["r_demo"] && !$admin[$page["permissionrole"]]) {
  throw new Exception($i18n->getMessage("error_access_denied"));
}

// get module config
$configfile = FOLDER_MODULES ."/". $page["module"] ."/". MODULE_CONFIG_FILENAME;
if (!file_exists($configfile)) {
	throw new Exception("File does not exist: " . $configfile);
}
$xml = simplexml_load_file($configfile);
$entityConfig = $xml->xpath("//adminpage[@id = '". $entity . "']/entity[1]");
if (!$entityConfig) {
	throw new Exception("No entity config found.");
}
$overviewConfig = $entityConfig[0]->xpath("overview[1]");
if (!$overviewConfig) {
	throw new Exception("No overview config found.");
}

// shall delete and edit be logged?
$loggingEnabled = (boolean) $overviewConfig[0]->attributes()->logging;
if ($loggingEnabled) {
	$loggingColumns = (string) $overviewConfig[0]->attributes()->loggingcolumns;
	if (!strlen($loggingColumns)) {
		throw new Exception($i18n->getMessage("entitylogging_nologgingcolumns"));
	}
}

if (isset($_REQUEST['id'])) $id = (int) $_REQUEST['id'];

echo "<h1>". $i18n->getMessage("entity_". $entity)  ."</h1>";

// remove alias
$tablePrefix = $website->getConfig("db_prefix") ."_";
$mainTable = $tablePrefix . $entityConfig[0]->attributes()->dbtable;
$spaceTablePos = strrpos($mainTable, " ");
$mainTableAlias = ($spaceTablePos) ? substr($mainTable, $spaceTablePos) . "." : "";
$dbTableWithoutPrefix = ModuleConfigHelper::removeAliasFromDbTableName($entityConfig[0]->attributes()->dbtable);
$dbTable = $tablePrefix . $dbTableWithoutPrefix;

// show overview by default
$showOverview = TRUE;

// process add/edit form action
if ($show == "add" || $show == "edit") {
	$showOverview = FALSE;
	$enableFileUpload = FALSE;
	
	// field config
	$fields = $entityConfig[0]->xpath("editform/field");
	$formFields = array();
	foreach($fields as $field) {
		$attrs = $field->attributes();
		
		if ($show == "add" && (boolean) $attrs["editonly"]) {
			continue;
		}
		
		// check permission
		$roles = (string) $attrs["roles"];
		if (strlen($roles) && (!isset($admin["r_admin"]) || !$admin["r_admin"])) {
			$rolesArr = explode(",", $roles);
			$hasRole = FALSE;
			foreach ($rolesArr as $requiredRole) {
				if (isset($admin[$requiredRole]) && $admin[$requiredRole]) {
					$hasRole = TRUE;
					break;
				}
			}
			
			if ($hasRole === FALSE) {
				continue;
			}
		}
		
		$fieldId = (string) $attrs["id"];
		$fieldInfo = array();
		$fieldInfo["type"] = (string) $attrs["type"];
		$fieldInfo["required"] = ($attrs["required"] == "true" && !($show == "edit" && $fieldInfo["type"] == "password"));
		$fieldInfo["readonly"] = (boolean) $attrs["readonly"];
		$fieldInfo["jointable"] = (string) $attrs["jointable"];
		$fieldInfo["entity"] = (string) $attrs["entity"];
		$fieldInfo["labelcolumns"] = (string) $attrs["labelcolumns"];
		$fieldInfo["selection"] = (string) $attrs["selection"];
		$fieldInfo["converter"] = (string) $attrs["converter"];
		$fieldInfo["validator"] = (string) $attrs["validator"];
		$fieldInfo["default"] = (string) $attrs["default"];
		
		if ($fieldInfo["type"] == "file") {
			$enableFileUpload = TRUE;
		}
	
		$formFields[$fieldId] = $fieldInfo;
	}
	$labelPrefix = "entity_". $entity ."_";
	
	// save
	if ($action == "save") {
		try {
			if ($admin['r_demo']) {
				throw new Exception($i18n->getMessage("validationerror_no_changes_as_demo"));
			}
	
			// validate
			$dbcolumns = array();
			foreach ($formFields as $fieldId => $fieldInfo) {
				
				if ($fieldInfo["readonly"]) {
					continue;
				}
				
				if ($fieldInfo["type"] == "timestamp") {
					$dateObj = DateTime::createFromFormat($website->getConfig("date_format") .", H:i", 
							$_POST[$fieldId ."_date"] .", ". $_POST[$fieldId ."_time"]);
					$fieldValue = ($dateObj) ? $dateObj->getTimestamp() : 0;
				} elseif ($fieldInfo["type"] == "boolean") {
					$fieldValue = (isset($_POST[$fieldId])) ? "1" : "0";
				} else {
					$fieldValue = (isset($_POST[$fieldId])) ? $_POST[$fieldId] : "";
				}
				
				FormBuilder::validateField($i18n, $fieldId, $fieldInfo, $fieldValue, $labelPrefix);
					
				// apply converter
				if (strlen($fieldInfo["converter"])) {
					$converter = new $fieldInfo["converter"]($i18n, $website);
					$fieldValue = $converter->toDbValue($fieldValue);
				}
				
				// convert date
				if (strlen($fieldValue) && $fieldInfo["type"] == "date") {
					$dateObj = DateTime::createFromFormat($website->getConfig("date_format"), $fieldValue);
					$fieldValue = $dateObj->format("Y-m-d");
				} else if ($fieldInfo["type"] == "timestamp" && $fieldInfo["readonly"] && $show == "add") {
					$fieldValue = $website->getNowAsTimestamp();
				} else if ($fieldInfo["type"] == "file") {
					if (isset($_FILES[$fieldId]) && isset($_FILES[$fieldId]["tmp_name"]) && strlen($_FILES[$fieldId]["tmp_name"])) {
						$fieldValue = md5($entity . "-". $website->getNowAsTimestamp());
						$fieldValue .= "." . FileUploadHelper::uploadImageFile($i18n, $fieldId, $fieldValue, $entity);
					} else {
						continue;
					}

				}
				
				// do not store read-only, except generated timestamp on adding
				if (!$fieldInfo["readonly"] or $fieldInfo["readonly"] && $fieldInfo["type"] == "timestamp"  && $show == "add") {
					$dbcolumns[$fieldId] = $fieldValue;
				}
				
			}
	
			if ($show == "add") {
				$db->queryInsert($dbcolumns, $dbTable);
			} else {
				$whereCondition = "id = %d";
				$parameter = $id;
				$db->queryUpdate($dbcolumns, $dbTable, $whereCondition, $parameter);
				
				// log action
				if ($loggingEnabled) {
					$result = $db->querySelect($loggingColumns, $dbTable, $whereCondition, $parameter);
					$item = $result->fetch_array(MYSQLI_ASSOC);
					$result->free();
						
					logAdminAction($website, LOG_TYPE_EDIT, $admin["name"], $entity, json_encode($item));
				}
			}
	
			echo createSuccessMessage($i18n->getMessage("alert_save_success"), "");
	
			$showOverview = TRUE;
		} catch (Exception $e) {
			echo createErrorMessage($i18n->getMessage("subpage_error_alertbox_title"), $e->getMessage());
		}
	}
}

if ($show == "add") {
	include(__DIR__ . "/manage-add.inc.php");
} else if($show == "edit") {
	include(__DIR__ . "/manage-edit.inc.php");
} 

if ($showOverview) {
	include(__DIR__ . "/manage-overview.inc.php");
}

?>
