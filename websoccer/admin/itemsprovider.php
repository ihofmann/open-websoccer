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

// this file provides items of an entity. Can be used for auto-complete feature of Primary Key selection fields.
// GET parameters: 
// 		$dbtable - the DB table name without prefix
//		$labelcolumns - the DB column names from whcih the label shall be constructed
//		$search - an optional search string. All label columns will be searched (case insensitive).
//		$itemid - ID of a single item. Then only this item will be returned.

define('BASE_FOLDER', __DIR__ .'/..');
define('MAX_ITEMS', 20);

include(BASE_FOLDER . '/admin/adminglobal.inc.php');

// validate parameters
$dbTable = $_GET['dbtable'];
if(!strlen($dbTable) || preg_match('/^([a-zA-Z1-9_])+$/', $dbTable) == 0) {
	throw new Exception('Illegal parameter: dbtable');
}

$labelColumns = $_GET['labelcolumns'];
if(!strlen($labelColumns) || preg_match('/^([a-zA-Z1-9_, ])+$/', $labelColumns) == 0) {
	throw new Exception('Illegal parameter: labelcolumns');
}

$search = (isset($_GET['search'])) ? strtolower($_GET['search']) : '';
$itemId = (isset($_GET['itemid']) && is_numeric($_GET['itemid'])) ? $_GET['itemid'] : 0;

$labels = explode(',', $labelColumns);

// query
$whereCondition = '';
if ($itemId > 0) {
	$whereCondition = 'id = %d';
	$queryParameters = $_GET['itemid'];
} elseif (!strlen($search)) {
	$whereCondition = '1=1';
	$queryParameters = '';
} else {
	// check every label column
	$first = TRUE;
	foreach ($labels as $labelColumn) {
		if (!$first) {
			$whereCondition .= ' OR ';
		}
		$first = FALSE;
		
		$whereCondition .= 'LOWER(' . $labelColumn . ') LIKE \'%%%s%%\'';
		$queryParameters[] = $search;
	}
}

		
$whereCondition .= ' ORDER BY '. $labelColumns . ' ASC';
$result = $db->querySelect('id, ' . $labelColumns, $website->getConfig('db_prefix') . '_' . $dbTable, $whereCondition, $queryParameters, MAX_ITEMS);

$items = array();
// collect items;
while($item = $result->fetch_array()) {
	
	// construct label
	$label = '';
	$first = TRUE;
	foreach ($labels as $labelColumn) {
		if (!$first) {
			$label .= ' - ';
		}
		$first = FALSE;
		$label .= $item[trim($labelColumn)];
	}
	
	$items[] = array('id' => $item['id'], 'text' => $label);
}
$result->free();

echo json_encode($items);
?>