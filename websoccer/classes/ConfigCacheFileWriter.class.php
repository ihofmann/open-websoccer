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
 * Writes and resets the website configuration cache. The configuration cache consists of system settings, page and event configurations, as well as
 * output messages (i18n).
 * 
 * @author Ingo Hofmann
 */
class ConfigCacheFileWriter {

	private $_frontCacheFileWriter;
	private $_adminCacheFileWriter;
	private $_supportedLanguages;
	private $_messagesFileWriters;
	private $_adminMessagesFileWriters;
	private $_entityMessagesFileWriters;
	private $_settingsCacheFileWriter;
	private $_eventsCacheFileWriter;
	private $_newSettings;
	
	/**
	 * 
	 * @param array $supportedLanguages array of supported languages as ISO-formatted strings (e.g. array('de', 'en')).
	 */
	function __construct($supportedLanguages) {
		$this->_frontCacheFileWriter = new FileWriter(CONFIGCACHE_FILE_FRONTEND);
		$this->_adminCacheFileWriter = new FileWriter(CONFIGCACHE_FILE_ADMIN);
		$this->_settingsCacheFileWriter = new FileWriter(CONFIGCACHE_SETTINGS);
		$this->_eventsCacheFileWriter = new FileWriter(CONFIGCACHE_EVENTS);
		$this->_supportedLanguages = $supportedLanguages;
		
		$this->_messagesFileWriters = array();
		$this->_adminMessagesFileWriters = array();
		$this->_entityMessagesFileWriters = array();
		foreach ($supportedLanguages as $language) {
			$this->_messagesFileWriters[$language] = new FileWriter(sprintf(CONFIGCACHE_MESSAGES, $language));
			$this->_adminMessagesFileWriters[$language] = new FileWriter(sprintf(CONFIGCACHE_ADMINMESSAGES, $language));
			$this->_entityMessagesFileWriters[$language] = new FileWriter(sprintf(CONFIGCACHE_ENTITYMESSAGES, $language));
		}
	}	
	
	/**
	 * creates new cache files and deletes existing ones.
	 */
	public function buildConfigCache() {
		$this->_writeFileStart($this->_frontCacheFileWriter);
		$this->_writeFileStart($this->_adminCacheFileWriter);
		$this->_writeFileStart($this->_settingsCacheFileWriter);
		$this->_writeFileStart($this->_eventsCacheFileWriter);
		foreach ($this->_supportedLanguages as $language) {
			$this->_writeMsgFileStart($this->_messagesFileWriters[$language]);
			$this->_writeMsgFileStart($this->_adminMessagesFileWriters[$language]);
			$this->_writeMsgFileStart($this->_entityMessagesFileWriters[$language]);
		}		
		
		$this->_buildModulesConfig();
		
		$this->_writeFileEnd($this->_frontCacheFileWriter);
		$this->_writeFileEnd($this->_adminCacheFileWriter);
		$this->_writeFileEnd($this->_settingsCacheFileWriter);
		$this->_writeFileEnd($this->_eventsCacheFileWriter);
		foreach ($this->_supportedLanguages as $language) {
			$this->_writeMsgFileEnd($this->_messagesFileWriters[$language]);
			$this->_writeMsgFileEnd($this->_adminMessagesFileWriters[$language]);
			$this->_writeMsgFileEnd($this->_entityMessagesFileWriters[$language]);
		}		
		
		// update global config
		if (is_array($this->_newSettings) && count($this->_newSettings)) {
			global $conf;
			$cf = ConfigFileWriter::getInstance($conf);
			$cf->saveSettings($this->_newSettings);
		}

	}
	
	private function _writeFileStart($fileWriter) {
		$fileWriter->writeLine('<?php');
		$fileWriter->writeLine('// !!!!! GENERATED FILE! DO NOT EDIT !!!!!');
	}
	
	private function _writeMsgFileStart($fileWriter) {
		$this->_writeFileStart($fileWriter);
		$fileWriter->writeLine('if (!isset($msg)) $msg = array();');
		$fileWriter->writeLine('$msg = $msg + array(');
	}
	
	private function _writeFileEnd($fileWriter) {
		$fileWriter->writeLine('?>');
	}	
	
	private function _writeMsgFileEnd($fileWriter) {
		$fileWriter->writeLine(');');
		$this->_writeFileEnd($fileWriter);
		
	}
	
	private function _buildModulesConfig() {
		$modules = scandir(FOLDER_MODULES);
		foreach ($modules as $module) {
			if (is_dir(FOLDER_MODULES .'/'. $module)) {
				$files = scandir(FOLDER_MODULES .'/'. $module);
				
				foreach ($files as $file) {
					$pathToFile = FOLDER_MODULES .'/'. $module .'/' . $file;
					if ($file == MODULE_CONFIG_FILENAME) {
						$this->_processModule($pathToFile, $module);
					} else if (StringUtil::startsWith($file, 'messages_')) {
						$this->_processMessages($pathToFile, $this->_messagesFileWriters);
					} else if (StringUtil::startsWith($file, 'adminmessages_')) {
						$this->_processMessages($pathToFile, $this->_adminMessagesFileWriters);
					} else if (StringUtil::startsWith($file, 'entitymessages_')) {
						$this->_processMessages($pathToFile, $this->_entityMessagesFileWriters);
					}						
				}
			}
			
		}
	}
	
	private function _processModule($file, $module) {
		$doc = new DOMDocument();
		$loaded = @$doc->load($file, LIBXML_DTDLOAD|LIBXML_DTDVALID);
		if (!$loaded) {
			throw new Exception('Could not load XML config file: ' + $file);
		}
		
		// validate (will throw warnings in development mode)
		$isValid = $doc->validate();
		
		$this->_processItem($doc, 'page', $this->_frontCacheFileWriter, $module);
		$this->_processItem($doc, 'block', $this->_frontCacheFileWriter, $module);
		$this->_processItem($doc, 'action', $this->_frontCacheFileWriter, $module);
		$this->_processItem($doc, 'adminpage', $this->_adminCacheFileWriter, $module);
		$this->_processItem($doc, 'setting', $this->_settingsCacheFileWriter, $module);
		$this->_processItem($doc, 'eventlistener', $this->_eventsCacheFileWriter, $module);
	}	
	
	private function _processItem($doc, $itemName, $fileWriter, $module, $keyAttribute = 'id') {
		$items = $doc->getElementsByTagName($itemName);
		foreach ($items as $item) {
			$line = $this->_buildConfigLine($itemName, $keyAttribute, $item, $module);
			$fileWriter->writeLine($line);
		}
	}
	
	private function _buildConfigLine($itemname, $keyAttribute, $xml, $module) {
		
		if ($itemname == 'eventlistener') {
			$line = '$'. $itemname .'[\''. $xml->getAttribute('event') . '\'][]';
		} else {
			$id = $xml->getAttribute($keyAttribute);
			$line = '$'. $itemname .'[\''. $xml->getAttribute($keyAttribute) . '\']';
		}
		
		$itemAttrs = array();
		if ($xml->hasAttributes()) {
			$attrs = $xml->attributes;
			foreach ($attrs as $attr) {
				$itemAttrs[$attr->name] = $attr->value;
			}
		}
		// has parent?
		$parent = $xml->parentNode;
		if ($parent->nodeName == $itemname) {
			$itemAttrs['parentItem'] = $parent->getAttribute($keyAttribute);
		}
		
		// has children?
		if($xml->hasChildNodes()){
			$children = $xml->childNodes;
			$childrenIds = '';
			$first = TRUE;
			foreach ($children as $child) {
				if ($child->nodeName == $itemname) {
					if (!$first) {
						$childrenIds .= ',';
					}
					$childrenIds .= $child->getAttribute($keyAttribute);
					$first = FALSE;
					
					// file references
				} else if ($child->nodeName == 'script' || $child->nodeName == 'css') {
					$childattrs = $child->attributes;
					$resourceRef = array();
					foreach ($childattrs as $attr) {
						$resourceRef[$attr->name] = $attr->value;
					}
					$itemAttrs[$child->nodeName . 's'][] = $resourceRef;
				}
			}
			if (!$first) {
				$itemAttrs['childrenIds'] = $childrenIds;
			}
		}
		$itemAttrs['module'] = $module;
		
		$line .= ' = \'' . json_encode($itemAttrs, JSON_HEX_QUOT) . '\';';
		
		// handle new setting
		if ($itemname == 'setting') {
			global $conf;
			if (!isset($conf[$id])) {
				$defaultValue = '';
				if ($xml->hasAttribute('default')) {
					$defaultValue = $xml->getAttribute('default');
				}
				$this->_newSettings[$id] = $defaultValue;
			}
		}
		
		return $line;
	}
	
	private function _processMessages($file, $fileWriters) {
		$doc = new DOMDocument();
		$loaded = @$doc->load($file);
		if (!$loaded) {
			throw new Exception('Could not load XML messages file: ' + $file);
		}
		
		$lang = substr($file, strrpos($file, '_') + 1, 2);
	
		if (isset($fileWriters[$lang])) {
			$messages = $doc->getElementsByTagName('message');
			$fileWriter = $fileWriters[$lang];
			
			foreach ($messages as $message) {
				$line = '\''. $message->getAttribute('id') . '\' => \''. addslashes($this->_getInnerHtml($message)) . '\',';
				$fileWriter->writeLine($line);
			}
			
		}
	}	
	
	function _getInnerHtml($node) {
		$innerHTML= '';
		$children = $node->childNodes;
		foreach ($children as $child) {
			$innerHTML .= $child->ownerDocument->saveXML($child);
		}
	
		return $innerHTML;  
	}
	
	function __destruct() {
		if ($this->_frontCacheFileWriter) {
			$this->_frontCacheFileWriter->close();
		}
		if ($this->_adminCacheFileWriter) {
			$this->_adminCacheFileWriter->close();
		}
		if ($this->_settingsCacheFileWriter) {
			$this->_settingsCacheFileWriter->close();
		}
		foreach ($this->_supportedLanguages as $language) {
			if ($this->_messagesFileWriters[$language]) {
				$this->_messagesFileWriters[$language]->close();
			}
			if ($this->_adminMessagesFileWriters[$language]) {
				$this->_adminMessagesFileWriters[$language]->close();
			}	
			if ($this->_entityMessagesFileWriters[$language]) {
				$this->_entityMessagesFileWriters[$language]->close();
			}		
		}		
	}	
}
?>