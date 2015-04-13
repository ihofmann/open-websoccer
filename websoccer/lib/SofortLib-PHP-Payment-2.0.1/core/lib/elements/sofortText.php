<?php
/**
 * @author SOFORT AG (integration@sofort.com)
 * @link http://www.sofort.com/
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 */

/**
 *
 * Implementation of simple text
 *
 */
class SofortText extends SofortElement {
	
	public $text;
	
	public $escape = false;
	
	
	/**
	 * Constructor for SofortText
	 * @param string $text
	 * @param boolean $escape (default false)
	 * @param boolean $trim (default true)
	 * @return void
	 */
	public function __construct($text, $escape = false, $trim = true) {
		$this->text = $trim ? trim($text) : $text;
		$this->escape = $escape;
	}
	
	
	/**
	 * Renders the element (override)
	 * @see SofortElement::render()
	 * @return string
	 */
	public function render() {
		return $this->escape ? htmlspecialchars($this->text) : $this->text;
	}
}
?>