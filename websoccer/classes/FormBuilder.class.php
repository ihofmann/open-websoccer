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
 * Creates form elements and validations for forms in the AdminCenter.
 * Should not be used at the front page, because it gerates HTML code! Use templates and actions instead.
 * 
 * @author Ingo Hofmann
 */
class FormBuilder {
	
	/**
	 * Renders a new form element.
	 * 
	 * @param I18n $i18n Messages context.
	 * @param string $fieldId ID of field.
	 * @param array $fieldInfo Field configuration asan array.
	 * @param string $fieldValue existing field value.
	 * @param string $labelKeyPrefix prefix of i18n message key to use. Message key = labelKeyPrefix + field ID.
	 */
	public static function createFormGroup($i18n, $fieldId, $fieldInfo, $fieldValue, $labelKeyPrefix) {
		$type = $fieldInfo['type'];
		
		// convert date
		if ($type == 'timestamp' && isset($fieldInfo['readonly']) && $fieldInfo['readonly']) {
			$website = WebSoccer::getInstance();
			$dateFormat = $website->getConfig('datetime_format');
			
			// generate date
			if (!strlen($fieldValue)) {
				$fieldValue = date($dateFormat);
			} else if (is_numeric($fieldValue)) {
				$fieldValue = date($dateFormat, $fieldValue);
			}
			$type = 'text';
		} else if ($type == 'date' && strlen($fieldValue)) {
			if (StringUtil::startsWith($fieldValue, '0000')) {
				$fieldValue = '';
			} else {
				$dateObj = DateTime::createFromFormat('Y-m-d', $fieldValue);
				if ($dateObj !== FALSE) {
					$website = WebSoccer::getInstance();
					$dateFormat = $website->getConfig('date_format');
					$fieldValue = $dateObj->format($dateFormat);
				}
			}
		}
		
		echo '<div class=\'control-group\'>';
		
		$helpText = '';
		$inlineHelpKey = $labelKeyPrefix . $fieldId .'_help';
		if ($i18n->hasMessage($inlineHelpKey)) {
			$helpText = '<span class=\'help-inline\'>'. $i18n->getMessage($inlineHelpKey) . '</span>';
		}
		
		if ($type == 'boolean') {
			echo '<label class=\'checkbox\'>';
			echo '<input type=\'checkbox\' value=\'1\' name=\''. $fieldId . '\'';
			if ($fieldValue == '1') {
				echo ' checked';
			}
			echo '>';
			echo $i18n->getMessage($labelKeyPrefix . $fieldId);
			echo '</label>';
			echo $helpText;
		} else {
			$labelOutput = $i18n->getMessage($labelKeyPrefix . $fieldId);
			if (isset($fieldInfo['required']) && $fieldInfo['required'] == 'true') {
				$labelOutput = '<strong>'. $labelOutput . '</strong>';
			}
			echo '<label class=\'control-label\' for=\''. $fieldId . '\'>'. $labelOutput . '</label>';
			echo '<div class=\'controls\'>';
		
			switch ($type) {
				// select from foreign DB table
				case 'foreign_key':
					self::createForeignKeyField($i18n, $fieldId, $fieldInfo, $fieldValue);
					break;
					
				// textarea
				case 'html':
				case 'textarea':
					$class = 'input-xxlarge';
					if ($type == 'html') {
						$class = 'htmleditor';
					}
					echo '<textarea id=\''. $fieldId . '\' name=\''. $fieldId . '\' wrap=\'virtual\' class=\''. $class .'\' rows=\'10\'>'. $fieldValue .'</textarea>';
					break;
					
				// date and time picker
				case 'timestamp':
					$website = WebSoccer::getInstance();
					$dateFormat = $website->getConfig('date_format');
					if (!$fieldValue) {
						$fieldValue = $website->getNowAsTimestamp();
					}
					
					// time picker
					echo '<div class=\'input-append date datepicker\'>';
					echo '<input type=\'text\' name=\''. $fieldId . '_date\' value=\''. date($dateFormat, $fieldValue) . '\' class=\'input-small\'>';
					echo '<span class=\'add-on\'><i class=\'icon-calendar\'></i></span>';
					echo '</div>';
					echo '<div class=\'input-append bootstrap-timepicker\'>';
					echo '<input type=\'text\' name=\''. $fieldId . '_time\' value=\''. date('H:i', $fieldValue) . '\' class=\'timepicker input-small\'>';
					echo '<span class=\'add-on\'><i class=\'icon-time\'></i></span>';
        			echo '</div>';
					break;
		
				// single selection from dropdown
				case 'select':
					echo '<select id=\''. $fieldId . '\' name=\''. $fieldId . '\'>';
					$selection = explode(',', $fieldInfo['selection']);
					$selectValue = $fieldValue;
					echo '<option></option>';
					foreach ($selection as $selectItem) {
						$selectItem = trim($selectItem);
						echo '<option value=\''. $selectItem .'\'';
						if ($selectItem == $selectValue) {
							echo ' selected';
						}
						echo '>';
						$label = $selectItem;
						if ($i18n->hasMessage('option_' . $selectItem)) {
							$label = $i18n->getMessage('option_' . $selectItem);
						}
						echo $label . '</option>';
					}
					echo '</select>';
					break;
		
				// all kind of text fields
				default:
					if (isset($fieldInfo['readonly']) && $fieldInfo['readonly']) {
						echo '<span class=\'uneditable-input\'>'. escapeOutput($fieldValue) .'</span>';
					} else {
						$additionalAttrs = '';
						$htmlType = $type;
						if($type == 'file' && strlen($fieldValue)) {
							global $entity;
							echo '[<a href=\'../uploads/' . $entity .'/'. escapeOutput($fieldValue) . '\' target=\'_blank\'>View</a>] ';
						} else if($type == 'percent') {
							$htmlType = 'number';
							$additionalAttrs = 'class=\'input-mini\' min=\'0\' ';
						} else if ($type == 'number') {
							$additionalAttrs = 'class=\'input-small\' ';
						} else if ($type == 'date') {
							
							if ($type == 'date') {
								echo '<div class=\'input-append date datepicker\'>';
							}
							
							$htmlType ='text';
							$additionalAttrs = ' class=\'input-small\' ';
						} else if ($type == 'tags') {
							$additionalAttrs = ' class=\'input-tag\' data-provide=\'tag\' ';
						} else {
							$additionalAttrs = 'placeholder=\''. $i18n->getMessage($labelKeyPrefix . $fieldId) . '\' ';
						}
						echo '<input type=\''. $htmlType . '\' id=\''. $fieldId . '\' '. $additionalAttrs . 'name=\''. $fieldId . '\' value=\'';
						
						if ($type != 'password') {
							echo escapeOutput($fieldValue);
						}
						
						echo '\'';
						if (isset($fieldInfo['required']) && $fieldInfo['required']) {
							echo ' required';
						}
						echo '>';
						
						if ($type == 'date') {
							echo '<span class=\'add-on\'><i class=\'icon-calendar\'></i></span></div>';
						}
					}
		
			}
		
			if ($type == 'percent') {
				echo ' % ';
			}
			echo $helpText;
			echo '</div>';
		}
		
		echo '</div>';
	}
	
	/**
	 * Validates specified form field.
	 * 
	 * @param I18n $i18n messages context.
	 * @param string $fieldId ID of field.
	 * @param array $fieldInfo field configuration.
	 * @param string $fieldValue value to validate.
	 * @param string $labelKeyPrefix label messages key prefix.
	 * @throws Exception if validation failed.
	 */
	public static function validateField($i18n, $fieldId, $fieldInfo, $fieldValue, $labelKeyPrefix) {
		$textLength = strlen(trim($fieldValue));
		$isEmpty = !$textLength;
		if ($fieldInfo['type'] != 'boolean' && $fieldInfo['required'] && $isEmpty) {
			throw new Exception(sprintf($i18n->getMessage('validationerror_required'), $i18n->getMessage($labelKeyPrefix . $fieldId)));
		}
		
		if (!$isEmpty) {
			
			if ($fieldInfo['type'] == 'text' && $textLength > 255) {
				throw new Exception(sprintf($i18n->getMessage('validationerror_text_too_long'), $i18n->getMessage($labelKeyPrefix . $fieldId)));
			}
			
			if ($fieldInfo['type'] == 'email' && !filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
				throw new Exception($i18n->getMessage('validationerror_email'));
			}
			
			if ($fieldInfo['type'] == 'url' && !filter_var($fieldValue, FILTER_VALIDATE_URL)) {
				throw new Exception(sprintf($i18n->getMessage('validationerror_url'), $i18n->getMessage($labelKeyPrefix . $fieldId)));
			}
			
			if ($fieldInfo['type'] == 'number' && !is_numeric($fieldValue)) {
				throw new Exception(sprintf($i18n->getMessage('validationerror_number'), $i18n->getMessage($labelKeyPrefix . $fieldId)));
			}
			
			if ($fieldInfo['type'] == 'percent' && filter_var($fieldValue, FILTER_VALIDATE_INT) === FALSE) {
				throw new Exception(sprintf($i18n->getMessage('validationerror_percent'), $i18n->getMessage($labelKeyPrefix . $fieldId)));
			}
			
			if ($fieldInfo['type'] == 'date') {
				$website = WebSoccer::getInstance();
				$format = $website->getConfig('date_format');
				if (!DateTime::createFromFormat($format, $fieldValue)) {
					throw new Exception(sprintf($i18n->getMessage('validationerror_date'), $i18n->getMessage($labelKeyPrefix . $fieldId), $format));
				}
			}
			
		}
		
		// check with validator
		if (isset($fieldInfo['validator']) && strlen($fieldInfo['validator'])) {
			$website = WebSoccer::getInstance();
			$validator = new $fieldInfo['validator']($i18n, $website, $fieldValue);
			if (!$validator->isValid()) {
				throw new Exception($i18n->getMessage($labelKeyPrefix . $fieldId) . ': ' . $validator->getMessage());
			}
		}

	}
	
	/**
	 * Renders a selection field for a data table entry.
	 * Up to 20 items will be displayed as usual selection box. Above as Autocomplete field.
	 * 
	 * @param I18n $i18n Messages context.
	 * @param string $fieldId ID of field.
	 * @param array $fieldInfo assoc. array with at least keys 'entity', 'jointable' and 'labelcolumns'.
	 * @param int $fieldValue pre-selected ID.
	 */
	public static function createForeignKeyField($i18n, $fieldId, $fieldInfo, $fieldValue) {
		$website = WebSoccer::getInstance();
		$db = DbConnection::getInstance();
		$fromTable = $website->getConfig('db_prefix') .'_'. $fieldInfo['jointable'];
		
		// count total items
		$result = $db->querySelect('COUNT(*) AS hits', $fromTable, '1=1', '');
		$items = $result->fetch_array();
		$result->free();
		
		// render usual selection box
		if ($items['hits'] <= 20) {
			echo '<select id=\''. $fieldId . '\' name=\''. $fieldId . '\'>';
			echo '<option value=\'\'>' . $i18n->getMessage('manage_select_placeholder') . '</option>';
			
			
			$whereCondition = '1=1 ORDER BY '. $fieldInfo['labelcolumns'] . ' ASC';
			$result = $db->querySelect('id, ' . $fieldInfo['labelcolumns'], $fromTable, $whereCondition, '', 2000);
			while ($row = $result->fetch_array()) {
				$labels = explode(',', $fieldInfo['labelcolumns']);
				$label = '';
				$first = TRUE;
				foreach ($labels as $labelColumn) {
					if (!$first) {
						$label .= ' - ';
					}
					$first = FALSE;
					$label .= $row[trim($labelColumn)];
				}
				echo '<option value=\''. $row['id'] . '\'';
				if ($fieldValue == $row['id']) {
					echo ' selected';
				}
				echo '>'. escapeOutput($label) . '</option>';
			}
			$result->free();
			
			echo '</select>';
			
			// render AJAXified item picker
		} else {
			
			echo '<input type=\'hidden\' class=\'pkpicker\' id=\''. $fieldId . '\' name=\''. $fieldId . '\' 
					value=\'' . $fieldValue . '\' data-dbtable=\''. $fieldInfo['jointable'] . '\' data-labelcolumns=\''. $fieldInfo['labelcolumns'] . '\' data-placeholder=\'' . $i18n->getMessage('manage_select_placeholder') . '\'>';
		
		}
		
		echo ' <a href=\'?site=manage&entity='. $fieldInfo['entity'] . '&show=add\' title=\''. $i18n->getMessage('manage_add') . '\'><i class=\'icon-plus-sign\'></i></a>';
	}
	
}
?>