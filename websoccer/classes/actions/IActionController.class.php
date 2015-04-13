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
 * <p>Any controller must implement this interface.</p>
 * <p><strong>Note:</strong> Controller Names must end with 'Controller', e.g. 'MyFooController'.</p>
 */
interface IActionController {
	
	/**
	 * @param I18n $i18n i18n instance.
	 * @param WebSoccer $websoccer Websoccer instance.
	 * @param DbConnnection $db DB connection.
	 */
	public function __construct(I18n $i18n, WebSoccer $websoccer, DbConnection $db);
	
	/**
	 * Execute action.
	 * 
	 * @param array $parameters validated request parameters.
	 * @return string target page-ID or <code>null</code> if user shall remain on same page.
	 * @throws Exception when action failed. Basic parameter validation is not required in case parameters are properly configured at module.xml.
	 */
	public function executeAction($parameters);
	
}

?>