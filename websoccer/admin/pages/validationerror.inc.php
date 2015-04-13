<?
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
echo "<h1>". $mainTitle ." &raquo; ". $i18n->getMessage("subpage_save_title") . " &raquo; ". $i18n->getMessage("subpage_error_title") . "</h1>";

$message = "<ul>";
foreach ($err as $e => $error) {
  $message .= "<li>". $error ."</li>";
}
$message .= "</ul>";

echo createErrorMessage($i18n->getMessage("subpage_error_alertbox_title") , $message);

echo "<p>&raquo; <a href=\"?site=". $site . "\">". $i18n->getMessage("back_label") . "</a></p>\n";
?>
