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

// execute transfer
DirectTransfersDataService::executeTransferFromOffer($website, $db, $website->getRequestParameter('id'));

// remove pending state
$db->queryUpdate(array('admin_approval_pending' => '0'), $website->getConfig('db_prefix') . '_transfer_offer',
		'id = %d', $website->getRequestParameter('id'));

// create success message
echo createSuccessMessage($i18n->getMessage('transferoffer_approval_success'), '');

?>