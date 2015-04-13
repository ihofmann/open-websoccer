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
define("ALLOWED_PROFPIC_EXTENSIONS", "jpg,jpeg,png");
define("CLUBPICTURE_UPLOAD_DIR", BASE_FOLDER . "/uploads/club");

/**
 * Validates an uploaded club profile pictures and saves it.
 */
class UploadClubPictureController implements IActionController {
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
		if (!$this->_websoccer->getConfig("upload_clublogo_max_size")) {
			throw new Exception("feature is not enabled.");
		}
		
		// check if valid club. User should actually not reach here, hence no i18n
		$clubId = $this->_websoccer->getUser()->getClubId();
		if (!$clubId) {
			throw new Exception("requires team");
		}
		
		// check if picture is provided
		if (!isset($_FILES["picture"])) {
			throw new Exception($this->_->getMessage("change-profile-picture_err_notprovied"));
		}
		
		$errorcode = $_FILES["picture"]["error"];
		
		// check upload status. If too big, PHP skips uploading
		if ($errorcode == UPLOAD_ERR_FORM_SIZE) {
			throw new Exception($this->_i18n->getMessage("change-profile-picture_err_illegalfilesize"));
		}
		
		// check file type
		$filename = $_FILES["picture"]["name"];
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$allowedExtensions = explode(",", ALLOWED_PROFPIC_EXTENSIONS);
		if (!in_array($ext, $allowedExtensions)) {
			throw new Exception($this->_i18n->getMessage("change-profile-picture_err_illegalfiletype"));
		}
		
		$imagesize = getimagesize($_FILES["picture"]["tmp_name"]);
		if ($imagesize === FALSE) {
			throw new Exception($this->_i18n->getMessage("change-profile-picture_err_illegalfiletype"));
		}
		
		$type = substr($imagesize["mime"], strrpos($imagesize["mime"], "/") + 1);
		if (!in_array($type, $allowedExtensions)) {
			throw new Exception($this->_i18n->getMessage("change-profile-picture_err_illegalfiletype"));
		}
		
		// check file size (just for security issues. Usually, PHP will not accept too big files)
		$maxFilesize = $this->_websoccer->getConfig("upload_clublogo_max_size") * 1024;
		if ($_POST["MAX_FILE_SIZE"] != $maxFilesize || $_FILES["picture"]["size"] > $maxFilesize) {
			throw new Exception($this->_i18n->getMessage("change-profile-picture_err_illegalfilesize"));
		}
		
		// save new picture
		if ($errorcode == UPLOAD_ERR_OK) {
			$tmp_name = $_FILES["picture"]["tmp_name"];
			$name = md5($clubId . time()) . "." . $ext;
			$uploaded = @move_uploaded_file($tmp_name, CLUBPICTURE_UPLOAD_DIR . "/" . $name);
			if (!$uploaded) {
				throw new Exception($this->_i18n->getMessage("change-profile-picture_err_failed"));
			}
		} else {
			throw new Exception($this->_i18n->getMessage("change-profile-picture_err_failed"));
		}
		
		// check image size. If not 120px, adjust it.
		if ($imagesize[0] != 120 || $imagesize[1] != 120) {
			$this->resizeImage(CLUBPICTURE_UPLOAD_DIR . "/". $name, 120, $imagesize[0], $imagesize[1], $ext == "png");
		}
		
		// delete old picture
		$fromTable = $this->_websoccer->getConfig("db_prefix") . "_verein";
		$whereCondition = "id = %d";
		$result = $this->_db->querySelect("bild", $fromTable, $whereCondition, $clubId);
		$clubinfo = $result->fetch_array();
		$result->free();
		
		if (strlen($clubinfo["bild"]) && file_exists(CLUBPICTURE_UPLOAD_DIR . "/" . $clubinfo["bild"])) {
			unlink(CLUBPICTURE_UPLOAD_DIR . "/" . $clubinfo["bild"]);
		}
		
		// update user
		$this->_db->queryUpdate(array("bild" => $name), $fromTable, $whereCondition, $clubId);
		
		// show success message
		$this->_websoccer->addFrontMessage(new FrontMessage(MESSAGE_TYPE_SUCCESS, 
				$this->_i18n->getMessage("upload-clublogo_success"),
				""));
		
		return null;
	}
	
	private function resizeImage($file, $width, $oldWidth, $oldHeight, $isPng) {
		if (!$isPng) {
			$src = imagecreatefromjpeg($file);
		} else {
			$src = imagecreatefrompng($file);
		}
		
		$target = imagecreatetruecolor($width, $width);
		imagecopyresampled($target, $src, 0, 0, 0, 0, $width, $width, $oldWidth, $oldHeight);
		
		if (!$isPng) {
			imagejpeg($target, $file);
		} else {
			imagepng($target, $file);
		}
	}
	
}

?>