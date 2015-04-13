<?php
/**
 * XML To Array Node
 *
 * Copyright (c) 2013 SOFORT AG
 *
 * Released under the GNU LESSER GENERAL PUBLIC LICENSE (Version 3)
 * [http://www.gnu.org/licenses/lgpl.html]
 *
 * $Date: 2013-08-23 09:57:31 +0200 (Fri, 23 Aug 2013) $
 * @version SofortLib 2.0.1  $Id: xmlToArrayNode.php 266 2013-08-23 07:57:31Z mattzick $
 * @author SOFORT AG http://www.sofort.com (integration@sofort.com)
 * @link http://www.sofort.com/
 *
 */
class XmlToArrayNode {
	
	private $_attributes = array();
	
	private $_children = array();
	
	private $_data = '';
	
	private $_name = '';
	
	private $_open = true;
	
	private $_ParentXmlToArrayNode = null;
	
	
	/**
	 * Constructor for XmlToArrayNode
	 * @param string $name
	 * @param array $attributes
	 * @return void
	 */
	public function __construct($name, $attributes) {
		$this->_name = $name;
		$this->_attributes = $attributes;
	}
	
	
	/**
	 * Add a child to collection
	 * @param XmlToArrayNode $XmlToArrayNode
	 * @return void
	 */
	public function addChild(XmlToArrayNode $XmlToArrayNode) {
		$this->_children[] = $XmlToArrayNode;
	}
	
	
	/**
	 * Getter for data, returns an array
	 * @return array
	 */
	public function getData() {
		return $this->_data;
	}
	
	
	/**
	 * Getter for name, returns the name
	 * @return string
	 */
	public function getName() {
		return $this->_name;
	}
	
	
	/**
	 * Getter for parent node
	 * @return XmlToArrayNode
	 */
	public function getParentXmlToArrayNode() {
		return $this->_ParentXmlToArrayNode;
	}
	
	
	/**
	 * Does it have any children
	 * @return int
	 */
	public function hasChildren() {
		return count($this->_children);
	}
	
	
	/**
	 * Does it have a node
	 * return boolean
	 */
	public function hasParentXmlToArrayNode() {
		return $this->_ParentXmlToArrayNode instanceof XmlToArrayNode;
	}
	
	
	/**
	 * Is it open, returns _open
	 * @return boolean
	 */
	public function isOpen() {
		return $this->_open;
	}
	
	
	/**
	 * Renders nodes as array
	 * @param bool $simpleStructure pass true to get an array without @data and @attributes fields
	 * @throws XmlToArrayException
	 * @return void
	 */
	public function render($simpleStructure) {
		$array = array();
		$multiples = array();
		
		$multiples = $this->_countChildren($this->_children);
		
		foreach ($this->_children as $Child) {
			$simpleStructureChildHasNoChildren = $simpleStructure && !$Child->hasChildren();
			if ($multiples[$Child->getName()]) {
				$array[$Child->getName()][] = $this->_renderNode($Child, $simpleStructureChildHasNoChildren, $simpleStructure);
			} else {
				$array[$Child->getName()] = $this->_renderNode($Child, $simpleStructureChildHasNoChildren, $simpleStructure);
			}
		}
		
		if (!$simpleStructure) {
			$array['@data'] = $this->_data;
			$array['@attributes'] = $this->_attributes;
		}
		
		return $this->_ParentXmlToArrayNode instanceof XmlToArrayNode
			? $array
			: array($this->_name => $simpleStructure && !$this->hasChildren() ? $this->getData() : $array);
	}
	
	
	/**
	 * Renders a single node
	 *
	 * @param string $Child
	 * @param bool $simpleStructureChildHasNoChildren
	 * @param bool $simpleStructure
	 */
	private function _renderNode($Child, $simpleStructureChildHasNoChildren, $simpleStructure) {
		return ($simpleStructureChildHasNoChildren) ? $Child->getData() : $Child->render($simpleStructure);
	}
	
	
	/**
	 * Counts the Children of an array and returns them in an associative array
	 *
	 * @param array $Children
	 * @return array
	 */
	private function _countChildren ($Children) {
		$multiples = array();
		
		foreach ($Children as $Child) {
			$multiples[$Child->getName()] = isset($multiples[$Child->getName()]) ? $multiples[$Child->getName()] + 1 : 0;
		}
		
		return $multiples;
	}
	
	
	/**
	 * Set it to closed
	 * @return void
	 */
	public function setClosed() {
		$this->_open = false;
	}
	
	
	/**
	 * Setter for variable data
	 * @param string $data
	 * @return void
	 */
	public function setData($data) {
		$this->_data .= $data;
	}
	
	
	/**
	 * Setter for parent node
	 * @param XmlToArrayNode $XmlToArrayNode
	 * @return void
	 */
	public function setParentXmlToArrayNode(XmlToArrayNode $XmlToArrayNode) {
		$this->_ParentXmlToArrayNode = $XmlToArrayNode;
	}
}
?>