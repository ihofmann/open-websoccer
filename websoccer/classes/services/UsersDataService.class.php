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
 * Data service for users data management.
 */
class UsersDataService {
	
	/**
	 * Creates a new local user in the application data base.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB Connection.
	 * @param string $nick User name of new user. Optional if e-mail address is provided. Must be unique in local data base. Case sensitive.
	 * @param string $email E-mail address of new user. Optional if nick is provided. Must be unique in local data base. Case insensitive (will be stored with lower letters).
	 * @throws Exception if both nick and e-mail are blank, or if nick name or e-mail address is already in use. Messages are not internationalized. Method assumes appropriate checks before calling it.
	 * @return int ID of newly created user.
	 */
	public static function createLocalUser(WebSoccer $websoccer, DbConnection $db, $nick = null, $email = null) {
		
		$username = trim($nick);
		$emailAddress = strtolower(trim($email));
		
		// check if either nick or e-mail is provided. If not, it most probably is a wrong API call, 
		// hence message is not required to be translated.
		if (!strlen($username) && !strlen($emailAddress)) {
			throw new Exception("UsersDataService::createBlankUser(): Either user name or e-mail must be provided in order to create a new internal user.");
		}
		
		// verify that there is not already such a user. If so, the calling function is wrongly implemented, hence
		// no translation of message.
		if (strlen($username) && self::getUserIdByNick($websoccer, $db, $username) > 0) {
			throw new Exception("Nick name is already in use.");
		}
		if (strlen($emailAddress) && self::getUserIdByEmail($websoccer, $db, $emailAddress) > 0) {
			throw new Exception("E-Mail address is already in use.");
		}
		
		// creates user.
		$i18n = I18n::getInstance($websoccer->getConfig("supported_languages"));
		$columns = array(
				"nick" => $username,
				"email" => $emailAddress,
				"status" => "1",
				"datum_anmeldung" => $websoccer->getNowAsTimestamp(),
				"lang" => $i18n->getCurrentLanguage()
				);
		if ($websoccer->getConfig("premium_initial_credit")) {
			$columns["premium_balance"] = $websoccer->getConfig("premium_initial_credit");
		}
		$db->queryInsert($columns, $websoccer->getConfig("db_prefix") . "_user");
		
		// provide ID of created user.
		if (strlen($username)) {
			$userId = self::getUserIdByNick($websoccer, $db, $username);
		} else {
			$userId = self::getUserIdByEmail($websoccer, $db, $emailAddress);
		}
		
		// trigger plug-ins
		$event = new UserRegisteredEvent($websoccer, $db, I18n::getInstance($websoccer->getConfig("supported_languages")),
				$userId, $username, $emailAddress);
		PluginMediator::dispatchEvent($event);
		
		return $userId;
	}
	
	/**
	 * Provides number of active users with ahighscore higher than 0.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @return int number of active users who have a highscore of higher than 0.
	 */
	public static function countActiveUsersWithHighscore(WebSoccer $websoccer, DbConnection $db) {
		$columns = "COUNT(id) AS hits";
		
		$fromTable = $websoccer->getConfig("db_prefix") . "_user";
		$whereCondition = "status = 1 AND highscore > 0 GROUP BY id";
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition);
		if (!$result) {
			$users = 0;
		} else {
			$users = $result->num_rows;
		}
		
		$result->free();
		
		return $users;
	}
	
	/**
	 * Provides active users with ahighscore higher than 0. Ordered by highscore and registration date.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $startIndex index of item to start to fetch.
	 * @param int $entries_per_page numger of items to fetch.
	 * @return array array of active users.
	 */
	public static function getActiveUsersWithHighscore(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page) {
		$columns["U.id"] = "id";
		$columns["nick"] = "nick";
		$columns["email"] = "email";
		$columns["U.picture"] = "picture";
		$columns["highscore"] = "highscore";
		$columns["datum_anmeldung"] = "registration_date";
		$columns["C.id"] = "team_id";
		$columns["C.name"] = "team_name";
		$columns["C.bild"] = "team_picture";
		
		$limit = $startIndex .",". $entries_per_page;
		
		$fromTable = $websoccer->getConfig("db_prefix") . "_user AS U";
		$fromTable .= " LEFT JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS C ON C.user_id = U.id";
		$whereCondition = "U.status = 1 AND highscore > 0 GROUP BY id ORDER BY highscore DESC, datum_anmeldung ASC";
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, null, $limit);
		
		$users = array();
		while ($user = $result->fetch_array()) {
			$user["picture"] = self::getUserProfilePicture($websoccer, $user["picture"], $user["email"]);
			
			$users[] = $user;
		}
		$result->free();
		
		return $users;
	}
	
	/**
	 * Provides data about the user with specified ID.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB connection.
	 * @param int $userId ID of user to fetch.
	 * @return array assoc. array with information about requested user. NULL if no active user could be found.
	 */
	public static function getUserById(WebSoccer $websoccer, DbConnection $db, $userId) {
		$columns["id"] = "id";
		$columns["nick"] = "nick";
		$columns["email"] = "email";
		$columns["highscore"] = "highscore";
		$columns["fanbeliebtheit"] = "popularity";
		$columns["datum_anmeldung"] = "registration_date";
		$columns["lastonline"] = "lastonline";
		$columns["picture"] = "picture";
		$columns["history"] = "history";
		
		$columns["name"] = "name";
		$columns["wohnort"] = "place";
		$columns["land"] = "country";
		$columns["geburtstag"] = "birthday";
		$columns["beruf"] = "occupation";
		$columns["interessen"] = "interests";
		$columns["lieblingsverein"] = "favorite_club";
		$columns["homepage"] = "homepage";
		
		$columns["premium_balance"] = "premium_balance";
		
		$fromTable = $websoccer->getConfig("db_prefix") . "_user";
		$whereCondition = "id = %d AND status = 1";
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $userId);
		$user = $result->fetch_array();
		$result->free();
		
		if ($user) {
			$user["picture_uploadfile"] = $user["picture"];
			$user["picture"] = self::getUserProfilePicture($websoccer, $user["picture"], $user["email"], 120);
		}
		
		return $user;
	}
	
	/**
	 * Provides ID of user with specified nick name.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB connection.
	 * @param string $nick nick name of user (exact match, case sensitive).
	 * @return int ID of user with specified nick name or -1 if no user could be found.
	 */
	public static function getUserIdByNick(WebSoccer $websoccer, DbConnection $db, $nick) {
		$columns = "id";
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_user";
		$whereCondition = "nick = '%s' AND status = 1";
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $nick);
		$user = $result->fetch_array();
		$result->free();
		
		if (isset($user["id"])) {
			return $user["id"];
		}
	
		return -1;
	}
	
	/**
	 * Provides ID of user with specified e-mail.
	 *
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB connection.
	 * @param string $email e-mail of user (exact match).
	 * @return int ID of user with specified e-mail or -1 if no user could be found.
	 */
	public static function getUserIdByEmail(WebSoccer $websoccer, DbConnection $db, $email) {
		$columns = "id";
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_user";
		$whereCondition = "email = '%s' AND status = 1";
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $email);
		$user = $result->fetch_array();
		$result->free();
	
		if (isset($user["id"])) {
			return $user["id"];
		}
	
		return -1;
	}
	
	/**
	 * Provides a list of nick names matching a specified search query. Helpful for autocomplete components.
	 * 
	 * @param WebSoccer $websoccer application context.
	 * @param DbConnection $db DB connection.
	 * @param string $nickStart nick name (beginning or complete) / search query. Case insensitive.
	 * @return array list of existing nick names ofactive users matching the search query.
	 */
	public static function findUsernames(WebSoccer $websoccer, DbConnection $db, $nickStart) {
		$columns = "nick";
		$fromTable = $websoccer->getConfig("db_prefix") . "_user";
		$whereCondition = "UPPER(nick) LIKE '%s%%' AND status = 1";
		
		$result = $db->querySelect($columns, $fromTable, $whereCondition, strtoupper($nickStart), 10);
		
		$users = array();
		while($user = $result->fetch_array()) {
			$users[] = $user["nick"];
		}
		$result->free();
		
		return $users;
	}
	
	/**
	 * Provides the user's profile picture. Either self-uploaded photo or from Gravatar.com
	 *
	 * @param WebSoccer $websoccer Application context.
	 * @param string $fileName Name of file in uploads folder. If NULL or empty string, the picture is taken from Gravatar.
	 * @param string $email user's e-mail.
	 * @param int $size desired picture size in pixels.
	 * @return string|NULL either URL to profile picture or NULL if none is configured.
	 */
	public static function getUserProfilePicture(WebSoccer $websoccer, $fileName, $email, $size = 40) {
	
		if (strlen($fileName)) {
			return $websoccer->getConfig("context_root") . "/uploads/users/" . $fileName;
		}
		
		return self::getGravatarUserProfilePicture($websoccer, $email, $size);
	}
	
	/**
	 * Provides the user's profile picture from Gravatar.com.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param string $email user's e-mail.
	 * @param int $size desired picture size in pixels.
	 * @return string|NULL either URL to profile picture or NULL if none is configured.
	 */
	public static function getGravatarUserProfilePicture(WebSoccer $websoccer, $email, $size = 40) {
		
		// use gravatar
		if (strlen($email) && $websoccer->getConfig("gravatar_enable")) {
			
			if (empty($_SERVER['HTTPS'])) {
				$picture = "http://www.";
			} else {
				$picture = "https://secure.";
			}
			
			$picture .= "gravatar.com/avatar/" . md5(strtolower($email));
			$picture .= "?s=" . $size; // size param
			$picture .= "&d=mm"; // use "mystery man" as default icon
			
			return $picture;
		} else {
			return null;
		}
	}
	
	/**
	 * Provide number of users who are currently online.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @return int number of users who are currently online.
	 */
	public static function countOnlineUsers(WebSoccer $websoccer, DbConnection $db) {
		$timeBoundary = $websoccer->getNowAsTimestamp() - 15 * 60;
		
		$result = $db->querySelect("COUNT(*) AS hits", $websoccer->getConfig("db_prefix") . "_user", 
				"lastonline >= %d", $timeBoundary);
		$users = $result->fetch_array();
		$result->free();
		
		if (isset($users["hits"])) {
			return $users["hits"];
		}
		
		return 0;
	}
	
	/**
	 * Provides users who are currently online.
	 * 
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @param int $startIndex Fetch start index.
	 * @param int $entries_per_page Number of rows to fetch.
	 * @return array list of users who are currently online.
	 */
	public static function getOnlineUsers(WebSoccer $websoccer, DbConnection $db, $startIndex, $entries_per_page) {
		$timeBoundary = $websoccer->getNowAsTimestamp() - 15 * 60;
		
		$columns["U.id"] = "id";
		$columns["nick"] = "nick";
		$columns["email"] = "email";
		$columns["U.picture"] = "picture";
		$columns["lastonline"] = "lastonline";
		$columns["lastaction"] = "lastaction";
		$columns["c_hideinonlinelist"] = "hideinonlinelist";
		$columns["C.id"] = "team_id";
		$columns["C.name"] = "team_name";
		$columns["C.bild"] = "team_picture";
	
		$limit = $startIndex .",". $entries_per_page;
	
		$fromTable = $websoccer->getConfig("db_prefix") . "_user AS U";
		$fromTable .= " LEFT JOIN " . $websoccer->getConfig("db_prefix") . "_verein AS C ON C.user_id = U.id";
		$whereCondition = "U.status = 1 AND lastonline >= %d GROUP BY id ORDER BY lastonline DESC";
	
		$result = $db->querySelect($columns, $fromTable, $whereCondition, $timeBoundary, $limit);
	
		$users = array();
		while ($user = $result->fetch_array()) {
			$user["picture"] = self::getUserProfilePicture($websoccer, $user["picture"], $user["email"]);
			$users[] = $user;
		}
		$result->free();
	
		return $users;
	}
	
	/**
	 * Provide total number of enabled users.
	 *
	 * @param WebSoccer $websoccer Application context.
	 * @param DbConnection $db DB connection.
	 * @return int total number of enabled users.
	 */
	public static function countTotalUsers(WebSoccer $websoccer, DbConnection $db) {
		$result = $db->querySelect("COUNT(*) AS hits", $websoccer->getConfig("db_prefix") . "_user",
				"status = 1");
		$users = $result->fetch_array();
		$result->free();
	
		if (isset($users["hits"])) {
			return $users["hits"];
		}
	
		return 0;
	}
	
}
?>