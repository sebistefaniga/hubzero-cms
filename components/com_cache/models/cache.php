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

namespace Components\Cache\Models;

use JApplicationHelper;
use JModelList;
use JPagination;
use JArrayHelper;
use JFactory;
use JCache;

jimport('joomla.application.component.modellist');

/**
 * Cache Model
 */
class Cache extends JModelList
{
	/**
	 * An Array of CacheItems indexed by cache group ID
	 *
	 * @var  Array
	 */
	protected $_data = array();

	/**
	 * Group total
	 *
	 * @var  integer
	 */
	protected $_total = null;

	/**
	 * Pagination object
	 *
	 * @var  object
	 */
	protected $_pagination = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string   $ordering
	 * @param   string   $direction
	 * @return  unknown
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();

		$clientId = $this->getUserStateFromRequest($this->context . '.filter.client_id', 'filter_client_id', 0, 'int');
		$this->setState('clientId', $clientId == 1 ? 1 : 0);

		$client = JApplicationHelper::getClientInfo($clientId);
		$this->setState('client', $client);

		parent::populateState('group', 'asc');
	}


	/**
	 * Method to get cache data
	 *
	 * @return  array
	 */
	public function getData()
	{
		if (empty($this->_data))
		{
			$cache = $this->getCache();
			$data  = $cache->getAll();

			if ($data != false)
			{
				$this->_data = $data;
				$this->_total = count($data);

				if ($this->_total)
				{
					// Apply custom ordering
					$ordering  = $this->getState('list.ordering');
					$direction = ($this->getState('list.direction') == 'asc') ? 1 : -1;

					jimport('joomla.utilities.arrayhelper');
					$this->_data = JArrayHelper::sortObjects($data, $ordering, $direction);

					// Apply custom pagination
					if ($this->_total > $this->getState('list.limit') && $this->getState('list.limit'))
					{
						$this->_data = array_slice($this->_data, $this->getState('list.start'), $this->getState('list.limit'));
					}
				}
			}
			else
			{
				$this->_data = array();
			}
		}
		return $this->_data;
	}



	/**
	 * Method to get cache instance
	 *
	 * @return object
	 */
	public function getCache()
	{
		$options = array(
			'defaultgroup' => '',
			'storage'      => \Config::get('cache_handler', ''),
			'caching'      => true,
			'cachebase'    => ($this->getState('clientId') == 1) ? JPATH_ADMINISTRATOR . '/cache' : \Config::get('cache_path', JPATH_SITE . '/cache')
		);

		$cache = JCache::getInstance('', $options);

		return $cache;
	}

	/**
	 * Method to get client data
	 *
	 * @return  array
	 */
	public function getClient()
	{
		return $this->getState('client');
	}

	/**
	 * Get the number of current Cache Groups
	 *
	 * @return  integer
	 */
	public function getTotal()
	{
		if (empty($this->_total))
		{
			$this->_total = count($this->getData());
		}

		return $this->_total;
	}

	/**
	 * Method to get a pagination object for the cache
	 *
	 * @return  integer
	 */
	public function getPagination()
	{
		if (empty($this->_pagination))
		{
			jimport('joomla.html.pagination');
			$this->_pagination = new JPagination(
				$this->getTotal(),
				$this->getState('list.start'),
				$this->getState('list.limit')
			);
		}

		return $this->_pagination;
	}

	/**
	 * Clean out a cache group as named by param.
	 * If no param is passed clean all cache groups.
	 *
	 * @param   string  $group
	 * @return  void
	 */
	public function clean($group = '')
	{
		$this->getCache()->clean($group);
	}

	/**
	 * Clean an array
	 *
	 * @param   array  $array
	 * @return  void
	 */
	public function cleanlist($array)
	{
		foreach ($array as $group)
		{
			$this->clean($group);
		}
	}

	/**
	 * Purge cache
	 *
	 * @return  boolean
	 */
	public function purge()
	{
		return JFactory::getCache('')->gc();
	}
}
