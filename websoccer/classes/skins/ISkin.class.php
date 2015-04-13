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
 * Any skin must implement this interface.
 * 
 * A skin controls which resources (CSS, JavaScript, Template Files) have to be taken for the website presentation.
 * 
 * <strong>Note:</strong> Skin Class Names must end with 'Skin', e.g. 'MyFooSkin'.
 * 
 * @author Ingo Hofmann
 */
interface ISkin {
	
	/**
	 * @param WebSoccer $websoccer WebSoccer context instance.
	 */
	public function __construct($websoccer);
	
	/**
	 * @return string Name of sub directory where templates are located.
	 */
	public function getTemplatesSubDirectory();
	
	/**
	 * @return array of absolute paths to CSS files which shall be loaded.
	 */
	public function getCssSources();
	
	/**
	 * @return array of absolute paths to JavaScript files which shall be loaded.
	 */
	public function getJavaScriptSources();
	
	/**
	 * Provides the file name of specified template name. Usually, it is just the template name plus file extension, but the
	 * implementation could map particular templates to another file.
	 * 
	 * @param string $templateName name of template to load, without file extension.
	 * @return sring Name of template file to load.
	 */
	public function getTemplate($templateName);
	
	/**
	 * 
	 * @param string $fileName Name of image file to load.
	 * @return string absolute path to the specified image for usage in output file.
	 */
	public function getImage($fileName);
}

?>