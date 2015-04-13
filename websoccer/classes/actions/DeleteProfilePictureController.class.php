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
 * Deletes an updates user profile picture.
 */
class DeleteProfilePictureController implements IActionController {
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
		
		// is feature enabled? User reaches here only when cheating, hence no i18n needed.
		if (!$this->_websoccer->getConfig("user_picture_upload_enabled")) {
			throw new Exception("feature is not enabled.");
		}
		
		$userId = $this->_websoccer->getUser()->id;
		
		// delete old picture
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_user";
		$whereCondition = "id = %d";
		$result = $this->_db->querySelect("picture", $fromTable, $whereCondition, $userId);
		$userinfo = $result->fetch_array();
		$result->free();
		
		if (strlen($userinfo["picture"]) && file_exists(PROFPIC_UPLOADFOLDER . "/" . $userinfo["picture"])) {
			unlink(PROFPIC_UPLOADFOLDER . "/" . $userinfo["picture"]);
		}
		
		// update user
		$this->_db->queryUpdate(array("picture" => ""), $fromTable, $whereCondition, $userId);
		
		// update profile picture for current request
		$this->_websoccer->getUser()->setProfilePicture($this->_websoccer, null);
		
		// show success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("delete-profile-picture_success"),
				""));
		
		return "user";
	}
	
}

?>