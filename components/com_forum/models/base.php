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

namespace Components\Forum\Models;

use Hubzero\Base\Model;
use Hubzero\User\Profile;
use InvalidArgumentException;
use Component;
use Lang;
use Date;

/**
 * Abstract model for forums
 */
class Base extends Model
{
	/**
	 * \Hubzero\User\Profile
	 *
	 * @var object
	 */
	protected $_creator = NULL;

	/**
	 * JRegistry
	 *
	 * @var object
	 */
	protected $_config = NULL;

	/**
	 * Scope adapter
	 *
	 * @var object
	 */
	protected $_adapter = null;

	/**
	 * Return a formatted timestamp
	 *
	 * @param   string  $as  What data to return
	 * @return  string
	 */
	public function created($as='')
	{
		switch (strtolower($as))
		{
			case 'date':
				return Date::of($this->get('created'))->toLocal(Lang::txt('DATE_FORMAT_HZ1'));
			break;

			case 'time':
				return Date::of($this->get('created'))->toLocal(Lang::txt('TIME_FORMAT_HZ1'));
			break;

			default:
				return $this->get('created');
			break;
		}
	}

	/**
	 * Get the creator of this entry
	 *
	 * Accepts an optional property name. If provided
	 * it will return that property value. Otherwise,
	 * it returns the entire JUser object
	 *
	 * @param   string  $property  What data to return
	 * @param   mixed   $default   Default value
	 * @return  mixed
	 */
	public function creator($property=null, $default=null)
	{
		if (!($this->_creator instanceof Profile))
		{
			$this->_creator = Profile::getInstance($this->get('created_by'));
			if (!$this->_creator)
			{
				$this->_creator = new Profile();
			}
		}
		if ($property)
		{
			$property = ($property == 'id' ? 'uidNumber' : $property);
			if ($property == 'picture')
			{
				return $this->_creator->getPicture($this->get('anonymous'));
			}
			return $this->_creator->get($property, $default);
		}
		return $this->_creator;
	}

	/**
	 * Get a configuration value
	 * If no key is passed, it returns the configuration object
	 *
	 * @param   string  $key      Config property to retrieve
	 * @param   mixed   $default  Default value to return
	 * @return  mixed
	 */
	public function config($key=null, $default=null)
	{
		if (!($this->_config instanceof \JRegistry))
		{
			$this->_config = Component::params('com_forum');
		}
		if ($key)
		{
			return $this->_config->get($key, $default);
		}
		return $this->_config;
	}

	/**
	 * Create an adapter object based on scope
	 *
	 * @return  object
	 */
	public function _adapter()
	{
		if (!$this->get('scope'))
		{
			$this->set('scope', 'site');
		}

		$scope = strtolower($this->get('scope'));
		$cls = __NAMESPACE__ . '\\Adapters\\' . ucfirst($scope);

		if (!class_exists($cls))
		{
			$path = __DIR__ . DS . 'adapters' . DS . $scope . '.php';
			if (!is_file($path))
			{
				throw new InvalidArgumentException(Lang::txt('Invalid scope of "%s"', $scope));
			}
			include_once($path);
		}

		return new $cls($this->get('scope_id'));
	}
}

