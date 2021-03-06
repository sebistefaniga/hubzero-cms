<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2013 Purdue University. All rights reserved.
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
 * @author    Alissa Nedossekina <alisa@purdue.edu>
 * @copyright Copyright 2005-2013 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Projects\Models;

require_once(dirname(__DIR__) . DS . 'tables' . DS . 'tool.php');
require_once(dirname(__DIR__) . DS . 'tables' . DS . 'tool.instance.php');
require_once(dirname(__DIR__) . DS . 'tables' . DS . 'tool.status.php');
require_once(dirname(__DIR__) . DS . 'tables' . DS . 'tool.log.php');
require_once(dirname(__DIR__) . DS . 'tables' . DS . 'tool.view.php');
require_once(__DIR__ . DS . 'tool' . DS . 'instance.php');
require_once(__DIR__ . DS . 'tool' . DS . 'log.php');
require_once(__DIR__ . DS . 'tool' . DS . 'status.php');
require_once(__DIR__ . DS . 'tool' . DS . 'view.php');

use Hubzero\Base\Model;
use Components\Projects\Tables;
use Hubzero\Base\ItemList;

/**
 * Project Tool model
 */
class Tool extends Model
{
	/**
	 * Table class name
	 *
	 * @var string
	 */
	protected $_tbl_name = '\\Components\\Projects\\Tables\\Tool';

	/**
	 * Entry
	 *
	 * @var object
	 */
	private $_entry = null;

	/**
	 * \Hubzero\Base\ItemList
	 *
	 * @var object
	 */
	private $_entries = null;

	/**
	 * JParameter
	 *
	 * @var object
	 */
	public $config = NULL;

	/**
	 * Constructor
	 *
	 * @return     void
	 */
	public function __construct($oid = NULL, $projectid = NULL, $instance = NULL)
	{
		$this->_db = \JFactory::getDBO();

		$this->_tbl = new Tables\Tool($this->_db);

		if ($oid)
		{
			if (is_numeric($oid) || is_string($oid))
			{
				$this->_tbl->loadTool($oid, $projectid);
			}

			// Load instance
			if ($this->exists())
			{
				$this->version($instance, $oid);
			}
		}
	}

	/**
	 * Returns a reference to a tool model
	 *
	 * @param      mixed $oid TODO ID
	 * @return     object Todo
	 */
	static function &getInstance($oid=null, $projectid = NULL, $instance = NULL)
	{
		static $instances;

		if (!isset($instances))
		{
			$instances = array();
		}

		if (!isset($instances[$oid]))
		{
			$instances[$oid] = new static($oid, $projectid, $instance);
		}

		return $instances[$oid];
	}

	/**
	 * Returns a reference to a tool model
	 *
	 * @param      mixed $oid TODO ID
	 * @return     object Todo
	 */
	static function mapInstance($result)
	{
		if (!is_object($result))
		{
			return false;
		}

		$oid = $result->id;
		static $instances;

		if (!isset($instances))
		{
			$instances = array();
		}

		if (!isset($instances[$oid]))
		{
			$instances[$oid] = new static();
			foreach ($result as $key => $value)
			{
				$instances[$oid]->set($key, $value);
			}
		}

		return $instances[$oid];
	}

	/**
	 * Set and get a specific instance
	 *
	 * @return     void
	 */
	public function version($id=null, $parent = NULL)
	{
		if (!isset($this->_version)
		 || ($id !== null && (int) $this->_version->get('id') != $id))
		{
			$this->_version = Tool\Instance::getInstance($id, $parent);
		}

		return $this->_version;
	}

	/**
	 * Get a log model
	 *
	 * @return     void
	 */
	public function log($parent_id = NULL, $parent_name = NULL)
	{
		if (!isset($this->_log))
		{
			$this->_log = new Tool\Log();
			if ($parent_id)
			{
				$this->_log->set('parent_id', $parent_id);
			}
			if ($parent_name)
			{
				$this->_log->set('parent_name', $parent_name);
			}
		}
		return $this->_log;
	}

	/**
	 * Get a status model
	 *
	 * @return     void
	 */
	public function status($id = NULL, $property = NULL)
	{
		if (!isset($this->_status) || ($id !== null && (int) $this->_status->get('id') != $id))
		{
			$this->_status = new Tool\Status($id);

			if ($this->_status->exists() && $property)
			{
				return $this->_status->get($property);
			}
		}
		return $this->_status;
	}

	/**
	 * Return a formatted timestamp
	 *
	 * @param	   string $as What format to return
	 * @return	   boolean
	 */
	public function statusChanged($as='')
	{
		switch (strtolower($as))
		{
			case 'date':
				return \JHTML::_('date', $this->get('status_changed'), Lang::txt('DATE_FORMAT_HZ1'));
			break;

			case 'time':
				return \JHTML::_('date', $this->get('status_changed'), Lang::txt('TIME_FORMAT_HZ1'));
			break;

			case 'datetime':
				return $this->statusChanged('date') . ' &#64; ' . $this->statusChanged('time');
			break;

			case 'timeago':
				return \Components\Projects\Helpers\Html::showTime($this->get('status_changed'), true);
			break;

			default:
				return $this->get('status_changed');
			break;
		}
	}

	/**
	 * Get a list of tools
	 *   Accepts either a numeric array index or a string [id, name]
	 *   If index, it'll return the entry matching that index in the list
	 *   If string, it'll return either a list of IDs or names
	 *
	 * @param      mixed $idx Index value
	 * @return     array
	 */
	public function entries($rtrn='list', $filters=array(), $admin = false)
	{
		$results = array();

		switch (strtolower($rtrn))
		{
			case 'count':
				$filters['count'] = 1;
				return (int) $this->_tbl->getRecordCount($filters, $admin);
			break;

			case 'results':
				return $this->_tbl->getRecords($filters, $admin);
			break;

			case 'list':
			default:
				if ($results = $this->_tbl->getRecords($filters, $admin))
				{
					foreach ($results as $key => $result)
					{
						/*
						$results[$key] = self::getInstance(
							$result->name,
							$result->project_id,
							$result->instance
						);
						*/
						$results[$key] = self::mapInstance($result);
					}
				}
				else
				{
					$results = array();
				}

				return new ItemList($results);
			break;
		}
		return null;
	}

	/**
	 * Get a configuration value
	 * If no key is passed, it returns the configuration object
	 *
	 * @param      string $key Config property to retrieve
	 * @return     mixed
	 */
	public function config($key=null)
	{
		if (!isset($this->_config))
		{
			$this->_config = Component::params('com_tools');
		}
		if ($key)
		{
			return $this->_config->get($key);
		}
		return $this->_config;
	}

	/**
	 * Verify data before saving
	 *
	 * @return    boolean False if error, True on success
	 */
	public function verify()
	{
		if (!$this->check())
		{
			// Name check
			return false;
		}
		if (trim($this->get('title')) == '')
		{
			// Title check
			$this->setError( Lang::txt('PLG_PROJECTS_TOOLS_ERROR_MISSING_TITLE') );
			return false;
		}

		// Clean title
	}

	/**
	 * Check tool name
	 *
	 * @param     string $name Alias name
	 * @return    boolean False if error, True on success
	 */
	public function check($name = '', $ajax = 0)
	{
		$name = $name ? $name : $this->get('name');

		// Load config
		$this->config();

		// Set name length
		$minLength = $this->_config->get('min_name_length', 3);
		$maxLength = $this->_config->get('max_name_length', 30);

		// Array of reserved names (task names and default dirs)
		$reserved = explode(',', $this->_config->get('reserved_names'));
		$tasks    = array('temp', 'toolname', 'register');

		if ($name)
		{
			$name = preg_replace('/ /', '', $name);
			$name = strtolower($name);
			$this->set('name', $name);
		}

		// Perform checks
		if (!$name)
		{
			// Cannot be empty
			$this->setError(Lang::txt('COM_PROJECTS_ERROR_NAME_EMPTY'));
		}
		elseif (strlen($name) < intval($minLength))
		{
			// Check for length
			$this->setError(Lang::txt('COM_PROJECTS_ERROR_NAME_TOO_SHORT'));
		}
		elseif (strlen($name) > intval($maxLength))
		{
			$this->setError(Lang::txt('COM_PROJECTS_ERROR_NAME_TOO_LONG'));
		}
		elseif (preg_match('/[^a-z0-9]/', $name))
		{
			// Check for illegal characters
			$this->setError(Lang::txt('COM_PROJECTS_ERROR_NAME_INVALID'));
		}
		elseif (is_numeric($name))
		{
			// Check for all numeric (not allowed)
			$this->setError(Lang::txt('COM_PROJECTS_ERROR_NAME_INVALID_NUMERIC'));
		}
		else
		{
			// Verify name uniqueness
			if ($this->_tbl->checkUniqueName( $name, $this->get('id') )
				|| ($reserved && in_array( $name, $reserved)) ||
				in_array( $name, $tasks ))
			{
				$this->setError(Lang::txt('COM_PROJECTS_ERROR_NAME_NOT_UNIQUE'));
			}
		}
		if ($this->getError())
		{
			return false;
		}

		return true;
	}

	/**
	 * Get the creator of this entry
	 *
	 * Accepts an optional property name. If provided
	 * it will return that property value. Otherwise,
	 * it returns the entire User object
	 *
	 * @return     mixed
	 */
	public function creator($property=null)
	{
		if (!isset($this->_creator) || !($this->_creator instanceof \Hubzero\User\Profile))
		{
			$this->_creator = \Hubzero\User\Profile::getInstance($this->get('created_by'));
		}
		if ($property)
		{
			$property = ($property == 'id' ? 'uidNumber' : $property);
			return $this->_creator->get($property);
		}
		return $this->_creator;
	}

	/**
	 * Get the use who changed status of this entry
	 *
	 * Accepts an optional property name. If provided
	 * it will return that property value. Otherwise,
	 * it returns the entire User object
	 *
	 * @return     mixed
	 */
	public function statusChanger($property=null)
	{
		if (!isset($this->_statusChanger) || !($this->_statusChanger instanceof \Hubzero\User\Profile))
		{
			$this->_statusChanger = \Hubzero\User\Profile::getInstance($this->get('status_changed_by'));
		}
		if ($property)
		{
			$property = ($property == 'id' ? 'uidNumber' : $property);
			return is_object($this->_statusChanger) ? $this->_statusChanger->get($property) : NULL;
		}
		return $this->_statusChanger;
	}
}

