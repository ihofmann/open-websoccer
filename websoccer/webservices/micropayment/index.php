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

//
// Processes a micropayment.de payment notification.
// Process according to script samples from micropayment.de.
// Script call is protected by .htaccess.
//

define('BASE_FOLDER', __DIR__ .'/../..');
include(BASE_FOLDER . '/admin/config/global.inc.php');

// check if enabled
if (!$website->getConfig('micropayment_enabled')) {
	die('micropayments.de is not enabled');
}

// 1. validate parameters --------------------------------------------------

// amount is passed in eurocents
$amount	= $_GET['amount'] / 100;

// user id as free parameter
$userId	= (int) $_GET['free'];
if (!$userId) {
	die('status=error');
}

// function must be 'billing' for crediting money
if ($_GET['function'] != 'billing') {
	die('invalid function');
}

// credit amount
PremiumDataService::createPaymentAndCreditPremium($website, $db, $userId, $amount, 'micropayment-notify');

// 2. Prepare response ------------------------------------------------------------------
$trenner 	= "\n";

$status		= 'ok';
$url		= $website->getInternalUrl('premiumaccount', null, TRUE);
$target		= '_top';
$forward	= 1;

$response = 'status=' . $status;
$response.= $trenner;
$response.= 'url=' . $url;
$response.= $trenner;
$response.= 'target=' . $target;
$response.= $trenner;
$response.= 'forward=' . $forward;

// send response
echo $response;
?>