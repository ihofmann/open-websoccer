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
define('DOUBLE_SUBMIT_CHECK_SECONDS', 3);
define('DOUBLE_SUBMIT_CHECK_SESSIONKEY_TIME', 'laction_time');
define('DOUBLE_SUBMIT_CHECK_SESSIONKEY_ACTIONID', 'laction_id');

/**
 * Handles action processing. An action triggers any kind of business logic and might control where the user shall be redirected after the action has been
 * processed. It also validates request parameters according to module settings.
 * 
 * @author Ingo Hofmann
 */
class ActionHandler {
	
	/**
	 * Securely calls an action. Before the actual call, this handler will validate all parameters defined at the module.xml
	 * and check the user permissions.
	 * 
	 * @param WebSoccer $website Current WebSoccer context.
	 * @param DbConnection $db Data base connection.
	 * @param I18n $i18n Messages context.
	 * @param string $actionId ID of action to validate and execute.
	 * @return string|NULL ID of page to display after the execution of this action or NULL if the current page shall be displayed.
	 * @throws Exception if action could not be found, a double-submit occured, access is denied, controller could not be found 
	 * or if the executed controller has thrown an Exception.
	 */
	public static function handleAction(WebSoccer $website, DbConnection $db, I18n $i18n, $actionId) {
		if ($actionId == NULL) {
			return;
		}
		
		// check double-submit
		if (isset($_SESSION[DOUBLE_SUBMIT_CHECK_SESSIONKEY_ACTIONID]) 
				&& $_SESSION[DOUBLE_SUBMIT_CHECK_SESSIONKEY_ACTIONID] == $actionId 
				&& isset($_SESSION[DOUBLE_SUBMIT_CHECK_SESSIONKEY_TIME]) 
				&& ($_SESSION[DOUBLE_SUBMIT_CHECK_SESSIONKEY_TIME] + DOUBLE_SUBMIT_CHECK_SECONDS) > $website->getNowAsTimestamp()) {
			throw new Exception($i18n->getMessage('error_double_submit'));
		}
		
		$actionConfig = json_decode($website->getAction($actionId), true);
		$actionXml = ModuleConfigHelper::findModuleConfigAsXmlObject($actionConfig['module']);
		
		// check permissions
		$user = $website->getUser();
		// is admin action
		if (strpos($actionConfig['role'], 'admin') !== false) {
			if (!$user->isAdmin()) {
				throw new AccessDeniedException($i18n->getMessage('error_access_denied'));
			}
		} else {
			// all other actions
			$requiredRoles = explode(',', $actionConfig['role']);
			if (!in_array($user->getRole(), $requiredRoles)) {
				throw new AccessDeniedException($i18n->getMessage('error_access_denied'));
			}
		}

		// validate parameters
		$params = $actionXml->xpath('//action[@id = "'. $actionId . '"]/param');
		$validatedParams = array();
		if ($params) {
			$validatedParams = self::_validateParameters($params, $website, $i18n);
		}
		
		$controllerName = $actionConfig['controller'];
		
		// handle premium actions
		if (isset($actionConfig['premiumBalanceMin']) && $actionConfig['premiumBalanceMin']) {
			return self::_handlePremiumAction($website, $db, $i18n, $actionId, $actionConfig['premiumBalanceMin'], $validatedParams, $controllerName);
		}
		
		$actionReturn = self::_executeAction($website, $db, $i18n, $actionId, $controllerName, $validatedParams);
		
		// create log entry
		if (isset($actionConfig['log']) && $actionConfig['log'] && $website->getUser()->id) {
			ActionLogDataService::createOrUpdateActionLog($website, $db, $website->getUser()->id, $actionId);
		}
		
		return $actionReturn;
	}	
	
	private static function _validateParameters($params, $website, $i18n) {
		$errorMessages = array();
		$validatedParams = array();
		
		foreach ($params as $param) {
			$paramId = (string) $param->attributes()->id;
			$type = (string) $param->attributes()->type;
			$required = ($param->attributes()->required == 'true');
			$min = (int) $param->attributes()->min;
			$max = (int) $param->attributes()->max;
			$validatorName = (string) $param->attributes()->validator;
			
			$paramValue = $website->getRequestParameter($paramId);
			
			if ($type == 'boolean') {
				$paramValue = ($paramValue) ? '1' : '0';
			}
			
			// validate 'required'
			if ($required && $paramValue == null) {
				$errorMessages[$paramId] = $i18n->getMessage('validation_error_required');
			} else if ($paramValue != null) {
				
				// minimum / maximum length
				if ($type == 'text' && $min > 0 && strlen($paramValue) < $min) {
					$errorMessages[$paramId] = sprintf($i18n->getMessage('validation_error_min_length'), $min);
				} else if ($type == 'text' && $max > 0 && strlen($paramValue) > $max) {
					$errorMessages[$paramId] = sprintf($i18n->getMessage('validation_error_max_length'), $max);
					
				// check number
				} else if ($type == 'number' && !is_numeric($paramValue)) {
					$errorMessages[$paramId] = $i18n->getMessage('validation_error_not_a_number');
				} else if ($type == 'number' && $paramValue < $min) {
					$errorMessages[$paramId] = $i18n->getMessage('validation_error_min_number', $min);
				} else if ($type == 'number' && $max > 0 && $paramValue > $max) {
					$errorMessages[$paramId] = $i18n->getMessage('validation_error_max_number', $max);
				} else if ($type == 'url' && !filter_var($paramValue, FILTER_VALIDATE_URL)) {
					$errorMessages[$paramId] = $i18n->getMessage('validation_error_not_a_url');
				} else if ($type == 'date') {
					$format = $website->getConfig('date_format');
					if (!DateTime::createFromFormat($format, $paramValue)) {
						$errorMessages[$paramId] = $i18n->getMessage('validation_error_invaliddate', $format);
					}
				}
				
				if (strlen($validatorName)) {
					if (!class_exists($validatorName)) {
						throw new Exception('Validator not found: ' . $validatorName);
					}
					
					$validator = new $validatorName($i18n, $website, $paramValue);
					if (!$validator->isValid()) {
						$errorMessages[$paramId] = $validator->getMessage();
					}
				}
			}
			
			if (!isset($errorMessages[$paramId])) {
				$validatedParams[$paramId] = $paramValue;
			}
			
		}
		
		if (count($errorMessages)) {
			throw new ValidationException($errorMessages);
		}
		
		return $validatedParams;
	}
	
	private static function _executeAction($website, $db, $i18n, $actionId, $controllerName, $validatedParams) {
		if (!class_exists($controllerName)) {
			throw new Exception('Controller not found: ' . $controllerName);
		}
			
		// prevent double-submit
		$_SESSION[DOUBLE_SUBMIT_CHECK_SESSIONKEY_ACTIONID] = $actionId;
		$_SESSION[DOUBLE_SUBMIT_CHECK_SESSIONKEY_TIME] = $website->getNowAsTimestamp();
		
		$controller = new $controllerName($i18n, $website, $db);
		return $controller->executeAction($validatedParams);
	}
	
	private static function _handlePremiumAction(WebSoccer $website, DbConnection $db, I18n $i18n, 
			$actionId, $creditsRequired, $validatedParams, $controllerName) {
		
		// check if user has enough credit
		if ($creditsRequired > $website->getUser()->premiumBalance) {
		
			$targetPage = $website->getConfig('premium_infopage');
		
			// redirect to external info page
			if (filter_var($targetPage, FILTER_VALIDATE_URL)) {
				header('location: ' . $targetPage);
				exit;
					
				// render info page
			} else {
				$website->addContextParameter('premium_balance_required', $creditsRequired);
				return $targetPage;
			}
		}
		
		// debit amount and execute action
		if ($website->getRequestParameter('premiumconfirmed')) {
			PremiumDataService::debitAmount($website, $db, $website->getUser()->id, $creditsRequired, $actionId);
			
			return self::_executeAction($website, $db, $i18n, $actionId, $controllerName, $validatedParams);
		}
		
		// redirect to confirmation page
		$website->addContextParameter('premium_balance_required', $creditsRequired);
		$website->addContextParameter('actionparameters', $validatedParams);
		$website->addContextParameter('actionid', $actionId);
		$website->addContextParameter('srcpage', $website->getPageId());
		return 'premium-confirm-action';
	}
}

?>
