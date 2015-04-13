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

/**
 * Send a private message to specified user.
 */
class SendMessageController implements IActionController {
	private $_i18n;
	private $_websoccer;
	private $_db;
	
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db) {
		$this->_i18n = $i18n;
		$this->_websoccer = $websoccer;
		$this->_db = $db;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IActionController::executeAction()
	 */
	public function executeAction($parameters) {
		
		$senderId = $this->_websoccer->getUser()->id;
		
		// check if messages is enabled
		if (!$this->_websoccer->getConfig("messages_enabled")) {
			throw new Exception($this->_i18n->getMessage("messages_err_messagesdisabled"));
		}
		
		// find user
		$recipientId = UsersDataService::getUserIdByNick($this->_websoccer, $this->_db, $parameters["nick"]);
		if ($recipientId < 1) {
			throw new Exception($this->_i18n->getMessage("messages_send_err_invalidrecipient"));
		}
		
		// cannot send to yourself
		if ($senderId == $recipientId) {
			throw new Exception($this->_i18n->getMessage("messages_send_err_sendtoyourself"));
		}
		
		$now = $this->_websoccer->getNowAsTimestamp();
		
		// check when sent last message (needs x minutes break in order to prevent spam)
		$lastMessage = MessagesDataService::getLastMessageOfUserId($this->_websoccer, $this->_db, $senderId);
		$timebreakBoundary = $now - $this->_websoccer->getConfig("messages_break_minutes") * 60;
		if ($lastMessage != null && $lastMessage["date"] >= $timebreakBoundary) {
			throw new Exception($this->_i18n->getMessage("messages_send_err_timebreak", $this->_websoccer->getConfig("messages_break_minutes")));
		}
		
		// create message
		$columns["empfaenger_id"] = $recipientId;
		$columns["absender_id"] = $senderId;
		$columns["datum"] = $now;
		$columns["betreff"] = $parameters["subject"];
		$columns["nachricht"] = $parameters["msgcontent"];
		
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_briefe";
		
		// create message in inbox of recipient
		$columns["typ"] = "eingang";
		$this->_db->queryInsert($columns, $fromTable);
		
		// create message in outbox of sender
		$columns["typ"] = "ausgang";
		$this->_db->queryInsert($columns, $fromTable);
		
		// success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("messages_send_success"),
				""));
		
		// clear fields
		$_REQUEST["subject"] = "";
		$_REQUEST["msgcontent"] = "";
		$_REQUEST["nick"] = "";
		
		return null;
	}
	
}

?>