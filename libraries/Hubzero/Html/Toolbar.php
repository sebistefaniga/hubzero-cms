<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Hubzero\Html;

use Hubzero\Base\Object;

/**
 * ToolBar handler
 */
class Toolbar extends Object
{
	/**
	 * Toolbar name
	 *
	 * @var    string
	 */
	protected $_name = '';

	/**
	 * Toolbar array
	 *
	 * @var    array
	 */
	protected $_bar = array();

	/**
	 * Loaded buttons
	 *
	 * @var    array
	 */
	protected $_buttons = array();

	/**
	 * Directories, where button types can be stored.
	 *
	 * @var    array
	 */
	protected $_buttonPath = array();

	/**
	 * Constructor
	 *
	 * @param   string  $name  The toolbar name.
	 *
	 * @since   11.1
	 */
	public function __construct($name = 'toolbar')
	{
		$this->_name = $name;

		// Set base path to find buttons.
		$this->_buttonPath[] = __DIR__ . DS . 'Toolbar' . DS . 'Button';
	}

	/**
	 * Stores the singleton instances of various toolbar.
	 *
	 * @var JToolbar
	 */
	//protected static $instances = array();

	/**
	 * Returns the global JToolBar object, only creating it if it
	 * doesn't already exist.
	 *
	 * @param   string  $name  The name of the toolbar.
	 * @return  object  The JToolBar object.
	 */
	/*public static function getInstance($name = 'toolbar')
	{
		if (empty(self::$instances[$name]))
		{
			self::$instances[$name] = new static($name);
		}

		return self::$instances[$name];
	}*/

	/**
	 * Push button onto the end of the toolbar array.
	 *
	 * @return  string  The set value.
	 */
	public function appendButton()
	{
		$btn = func_get_args();

		array_push($this->_bar, $btn);

		return true;
	}

	/**
	 * Get the list of toolbar links.
	 *
	 * @return  array
	 */
	public function getItems()
	{
		return $this->_bar;
	}

	/**
	 * Get the name of the toolbar.
	 *
	 * @return  string
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * Insert button into the front of the toolbar array.
	 *
	 * @return  boolean
	 */
	public function prependButton()
	{
		$btn = func_get_args();

		array_unshift($this->_bar, $btn);

		return true;
	}

	/**
	 * Render a tool bar.
	 *
	 * @return  string  HTML for the toolbar.
	 */
	public function render()
	{
		$html = array();

		// Start toolbar div.
		$html[] = '<div class="toolbar-list" id="' . $this->_name . '">';
		$html[] = '<ul>';

		foreach ($this->_bar as $key => $button)
		{
			$this->_bar[$key][9] = array();
			if ($button[0] == 'Separator')
			{
				continue;
			}
			if (!isset($this->_bar[$key - 1]) || $this->_bar[$key - 1][0] == 'Separator')
			{
				$this->_bar[$key][9][] = 'first';
			}
			if (!isset($this->_bar[$key + 1]) || $this->_bar[$key + 1][0] == 'Separator')
			{
				$this->_bar[$key][9][] = 'last';
			}
		}

		// Render each button in the toolbar.
		foreach ($this->_bar as $button)
		{
			$html[] = $this->renderButton($button);
		}

		// End toolbar div.
		$html[] = '</ul>';
		$html[] = '</div>';

		return implode("\n", $html);
	}

	/**
	 * Render a button.
	 *
	 * @param   object  &$node  A toolbar node.
	 * @return  string
	 */
	public function renderButton(&$node)
	{
		// Get the button type.
		$type = $node[0];

		$button = $this->loadButtonType($type);

		// Check for error.
		if ($button === false)
		{
			return \Lang::txt('JLIB_HTML_BUTTON_NOT_DEFINED', $type);
		}
		return $button->render($node);
	}

	/**
	 * Loads a button type.
	 *
	 * @param   string   $type  Button Type
	 * @param   boolean  $new   False by default
	 * @return  object
	 */
	public function loadButtonType($type, $new = false)
	{
		$signature = md5($type);
		if (isset($this->_buttons[$signature]) && $new === false)
		{
			return $this->_buttons[$signature];
		}

		/*if (!class_exists('Button'))
		{
			throw new \RuntimeException(\Lang::txt('JLIB_HTML_BUTTON_BASE_CLASS', $buttonClass, $buttonFile), 500);
		}*/

		$buttonClass = __NAMESPACE__ . '\\Toolbar\\Button\\' . $type;
		if (!class_exists($buttonClass))
		{
			if (isset($this->_buttonPath))
			{
				$dirs = $this->_buttonPath;
			}
			else
			{
				$dirs = array();
			}

			$file = \JFilterInput::getInstance()->clean(str_replace('_', DIRECTORY_SEPARATOR, strtolower($type)) . '.php', 'path');

			jimport('joomla.filesystem.path');
			if ($buttonFile = \JPath::find($dirs, $file))
			{
				include_once $buttonFile;
			}
			else
			{
				throw new \InvalidArgumentException(\Lang::txt('JLIB_HTML_BUTTON_NO_LOAD', $buttonClass, $buttonFile), 500);
			}
		}

		if (!class_exists($buttonClass))
		{
			throw new \Exception("Module file $buttonFile does not contain class $buttonClass.", 500);
			return false;
		}
		$this->_buttons[$signature] = new $buttonClass($this);

		return $this->_buttons[$signature];
	}

	/**
	 * Add a directory where JToolBar should search for button types in LIFO order.
	 *
	 * You may either pass a string or an array of directories.
	 *
	 * JToolbar will be searching for an element type in the same order you
	 * added them. If the parameter type cannot be found in the custom folders,
	 * it will look in libraries/joomla/html/toolbar/button.
	 *
	 * @param   mixed  $path  Directory or directories to search.
	 * @return  void
	 */
	public function addButtonPath($path)
	{
		// Just force path to array.
		settype($path, 'array');

		// Loop through the path directories.
		foreach ($path as $dir)
		{
			// No surrounding spaces allowed!
			$dir = trim($dir);

			// Add trailing separators as needed.
			if (substr($dir, -1) != DIRECTORY_SEPARATOR)
			{
				// Directory
				$dir .= DIRECTORY_SEPARATOR;
			}

			// Add to the top of the search dirs.
			array_unshift($this->_buttonPath, $dir);
		}
	}
}
