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
 * Implementation of a simple tag
 *
 */
class SofortTag extends SofortElement {
	
	public $tagname = '';
	
	public $attributes = array();
	
	public $children = array();
	
	
	/**
	 * Constructor for SofortTag
	 * @param string $tagname
	 * @param (optional) array $attributes
	 * @param (optional) array $children
	 * @return void
	 */
	public function __construct($tagname, array $attributes = array(), $children = array()) {
		$this->tagname = $tagname;
		$this->attributes = $attributes;
		$this->children = is_array($children) ? $children : array($children);
	}
	
	
	/**
	 * Renders the element (override)
	 * @see SofortElement::render()
	 * @return string
	 */
	public function render() {
		$output = '';
		$attributes = '';
		
		foreach ($this->children as $child) {
			$output .= is_object($child) ? $child->render() : $child;
		}
		
		foreach ($this->attributes as $key => $value) {
			$attributes .= " $key=\"$value\"";
		}
		
		return $this->_render($output, $attributes);
	}
	
	
	/**
	 * Render the output
	 * @param string $output
	 * @param string $attributes
	 * @return string
	 */
	protected function _render($output, $attributes) {
		return $output !== '' ? "<{$this->tagname}{$attributes}>{$output}</{$this->tagname}>" : "<{$this->tagname}{$attributes} />";
	}
}
?>