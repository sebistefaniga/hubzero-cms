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

namespace Components\Tags\Models;

use Hubzero\User\Profile;
use Hubzero\Base\Model;
use Date;
use Lang;

require_once(dirname(__DIR__) . DS . 'tables' . DS . 'object.php');

/**
 * Model class for a tag/object association
 */
class Object extends Model
{
	/**
	 * Table class name
	 *
	 * @var string
	 */
	protected $_tbl_name = '\\Components\\Tags\\Tables\\Object';

	/**
	 * \Hubzero\User\Profile
	 *
	 * @var object
	 */
	protected $_creator = NULL;

	/**
	 * Constructor
	 *
	 * @param   mixed    $oid        Record ID or object or array
	 * @param   integer  $scope_id   ID of tagged object
	 * @param   integer  $tag_id     Tag ID
	 * @param   integer  $tagger_id  User ID of tagger
	 * @return  void
	 */
	public function __construct($oid, $scope_id=null, $tag_id=null, $tagger_id=null)
	{
		// Set the database object
		$this->_db = \JFactory::getDBO();

		// Set the table object
		$tbl = $this->_tbl_name;
		$this->_tbl = new $tbl($this->_db);

		// Load record
		if (is_numeric($oid))
		{
			$this->_tbl->load($oid);
		}
		if (is_string($oid))
		{
			$this->_tbl->loadByObjectTag($oid, $scope_id, $tag_id, $tagger_id);
		}
		else if (is_object($oid) || is_array($oid))
		{
			$this->bind($oid);
		}
	}

	/**
	 * Returns a reference to a tags object model
	 *
	 * @param   mixed    $oid        Record ID or object or array
	 * @param   integer  $scope_id   ID of tagged object
	 * @param   integer  $tag_id     Tag ID
	 * @param   integer  $tagger_id  User ID of tagger
	 * @return  object
	 */
	static function &getInstance($oid=0, $scope_id=null, $tag_id=null, $tagger_id=null)
	{
		static $instances;

		if (!isset($instances))
		{
			$instances = array();
		}

		if (is_numeric($oid) || is_string($oid))
		{
			$key = $oid . $scope_id . $tag_id . $tagger_id;
		}
		else if (is_object($oid))
		{
			$key = $oid->id . $scope_id . $tag_id . $tagger_id;
		}
		else if (is_array($oid))
		{
			$key = $oid['id'] . $scope_id . $tag_id . $tagger_id;
		}

		if (!isset($instances[$oid]))
		{
			$instances[$oid] = new static($oid, $scope_id, $tag_id, $tagger_id);
		}

		return $instances[$oid];
	}

	/**
	 * Get the creator of this entry
	 *
	 * Accepts an optional property name. If provided
	 * it will return that property value. Otherwise,
	 * it returns the entire object
	 *
	 * @param   string  $property  Property to retrieve
	 * @param   mixed   $default   Default value if property not set
	 * @return  mixed
	 */
	public function creator($property=null, $default=null)
	{
		if (!($this->_creator instanceof Profile))
		{
			$this->_creator = Profile::getInstance($this->get('taggerid'));
			if (!$this->_creator)
			{
				$this->_creator = new Profile();
			}
		}
		if ($property)
		{
			$property = ($property == 'id' ? 'uidNumber' : $property);
			return $this->_creator->get($property, $default);
		}
		return $this->_creator;
	}

	/**
	 * Return a formatted timestamp
	 *
	 * @param   string  $as  What data to return
	 * @return  string
	 */
	public function created($rtrn='')
	{
		switch (strtolower($rtrn))
		{
			case 'date':
				return Date::of($this->get('taggedon'))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
			break;

			case 'time':
				return Date::of($this->get('taggedon'))->toLocal(Lang::txt('TIME_FORMAT_HZ1'));
			break;

			default:
				return $this->get('taggedon');
			break;
		}
	}
}

