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

if (isset($id) && $id) {
	$del_id = array($id);
}

if ($admin["r_demo"]) {
	throw new Exception($i18n->getMessage("error_access_denied"));
}

if (count($del_id)) {
	$dependencies = ModuleConfigHelper::findDependentEntities($dbTableWithoutPrefix);
	
	foreach ($del_id as $deleteId) {
		
		// log action
		if ($loggingEnabled) {
			$result = $db->querySelect($loggingColumns, $dbTable, "id = %d", $deleteId);
			$item = $result->fetch_array(MYSQLI_ASSOC);
			$result->free();
			
			logAdminAction($website, LOG_TYPE_DELETE, $admin["name"], $entity, json_encode($item));
		}
		
		// delete item
		$db->queryDelete($dbTable, "id = %d", $deleteId);
		
		foreach ($dependencies as $dependency) {
			$fromTable = $website->getConfig("db_prefix") . "_" . $dependency["dbtable"];
			$whereCondition = $dependency["columnid"] . " = %d";
			
			if (strtolower($dependency["cascade"]) == "delete") {
				$db->queryDelete($fromTable, $whereCondition, $deleteId);
			} else {
				$db->queryUpdate(array($dependency["columnid"] => 0), $fromTable, $whereCondition, $deleteId);
			}
		}
		
	}
	echo createSuccessMessage($i18n->getMessage("manage_success_delete"), "");
}

?>


