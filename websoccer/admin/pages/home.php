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

if (!$show) {

  ?>

  <h1><?php echo sprintf($i18n->getMessage('home_title'), escapeOutput($admin['name'])); ?></h1>

  <p><?php echo $i18n->getMessage('home_intro'); ?></p>

  <h3><?php echo $i18n->getMessage('home_softwareinfo_title'); ?></h3>
  
 <table class='table table-bordered' style='width: 500px;'>
  <tr>
	<td><b><?php echo $i18n->getMessage('home_softwareinfo_name'); ?></b></td>
	<td>OpenWebSoccer-Sim</td>
  </tr>
  <tr>
	<td><b><?php echo $i18n->getMessage('home_softwareinfo_version'); ?></b></td>
	<td><?php readfile('config/version.txt'); ?></td>
  </tr>
</table> 

  <h3><?php echo $i18n->getMessage('home_projectinfo_title'); ?></h3>

        <table class='table table-bordered' style='width: 500px;'>
          <tr>
            <td><b><?php echo $i18n->getMessage('home_projectinfo_name'); ?></b></td>
            <td><?php echo escapeOutput($website->getConfig('projectname')) ?></td>
          </tr>
          <tr>
            <td><b><?php echo $i18n->getMessage('home_projectinfo_adminemail'); ?></b></td>
            <td><a href='mailto:<?php echo $website->getConfig('systememail'); ?>'><?php echo $website->getConfig('systememail'); ?></a></td>
          </tr>
        </table>

  <?php

}

?>
