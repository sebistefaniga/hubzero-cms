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

namespace Components\Forum\Tables;

use User;
use Lang;
use Date;

/**
 * Table class for a forum category
 */
class Section extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  JDatabase
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__forum_sections', 'id', $db);
	}

	/**
	 * Method to compute the default name of the asset.
	 * The default name is in the form table_name.id
	 * where id is the value of the primary key of the table.
	 *
	 * @return  string
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;
		return 'com_forum.section.' . (int) $this->$k;
	}

	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return  string
	 */
	protected function _getAssetTitle()
	{
		return $this->title;
	}

	/**
	 * Get the parent asset id for the record
	 *
	 * @param   object   $table  A JTable object for the asset parent.
	 * @param   integer  $id     The id for the asset
	 * @return  integer  The id of the asset's parent
	 */
	protected function _getAssetParentId($table = null, $id = null)
	{
		// Initialise variables.
		$assetId = null;
		$db = $this->getDbo();

		if ($assetId === null)
		{
			// Build the query to get the asset id for the parent category.
			$query = $db->getQuery(true);
			$query->select('id');
			$query->from('#__assets');
			$query->where('name = ' . $db->quote('com_forum'));

			// Get the asset id from the database.
			$db->setQuery($query);
			if ($result = $db->loadResult())
			{
				$assetId = (int) $result;
			}
		}

		// Return the asset id.
		if ($assetId)
		{
			return $assetId;
		}
		else
		{
			return parent::_getAssetParentId($table, $id);
		}
	}

	/**
	 * Load a record by its alias and bind data to $this
	 *
	 * @param   string   $oid  Record alias
	 * @return  boolean  True upon success, False if errors
	 */
	public function loadByAlias($oid=NULL, $scope_id=null, $scope='site')
	{
		$fields = array(
			'alias' => trim((string) $oid),
			'state' => 1
		);

		if ($scope_id !== null)
		{
			$fields['scope_id'] = (int) $scope_id;
			$fields['scope']    = (string) $scope;
		}

		return parent::load($fields);
	}

	/**
	 * Load a record by its alias and bind data to $this
	 *
	 * @param   string   $oid  Record alias
	 * @return  boolean  True upon success, False if errors
	 */
	public function loadByObject($oid=NULL, $scope_id=null, $scope='site')
	{
		$fields = array(
			'object_id' => intval($oid),
			'state'     => 1
		);

		if ($scope_id !== null)
		{
			$fields['scope_id'] = (int) $scope_id;
			$fields['scope']    = (string) $scope;
		}

		return parent::load($fields);
	}

	/**
	 * Populate the object with default data
	 *
	 * @param      integer $group ID of group the data belongs to
	 * @return     boolean True if data is bound to $this object
	 */
	public function loadDefault($scope='site', $scope_id=0)
	{
		$result = array(
			'id'         => 0,
			'title'      => Lang::txt('Categories'),
			'created_by' => 0,
			'scope'      => $scope,
			'scope_id'   => $scope_id,
			'state'      => 1,
			'access'     => 1
		);
		$result['alias'] = str_replace(' ', '-', $result['title']);
		$result['alias'] = preg_replace("/[^a-zA-Z0-9\-]/", '', strtolower($result['alias']));

		return $this->bind($result);
	}

	/**
	 * Validate data
	 *
	 * @return     boolean True if data is valid
	 */
	public function check()
	{
		if (trim($this->title) == '')
		{
			$this->setError(Lang::txt('Please provide a title.'));
		}

		if (!$this->alias)
		{
			$this->alias = str_replace(' ', '-', strtolower($this->title));
		}
		$this->alias = preg_replace("/[^a-zA-Z0-9\-]/", '', $this->alias);
		if (!$this->alias)
		{
			$this->setError(Lang::txt('Alias cannot be all punctuation or blank.'));
		}

		if ($this->getError())
		{
			return false;
		}

		$this->scope = preg_replace("/[^a-zA-Z0-9]/", '', strtolower($this->scope));
		$this->scope_id = intval($this->scope_id);

		if (!$this->id)
		{
			$this->created    = Date::toSql();
			$this->created_by = User::get('id');
			$this->state = 1;
			if (!$this->ordering)
			{
				$this->ordering = $this->getHighestOrdering($this->scope, $this->scope_id);
			}
		}

		return true;
	}


	/**
	 * Get the last page in the ordering
	 *
	 * @param      string  $offering_id
	 * @return     integer
	 */
	public function getHighestOrdering($scope, $scope_id)
	{
		$sql = "SELECT MAX(ordering)+1 FROM $this->_tbl WHERE scope_id=" . $this->_db->Quote(intval($scope_id)) . " AND scope=" . $this->_db->Quote($scope);
		$this->_db->setQuery($sql);
		return $this->_db->loadResult();
	}

	/**
	 * Build a query based off of filters passed
	 *
	 * @param      array $filters Filters to construct query from
	 * @return     string SQL
	 */
	public function buildQuery($filters=array())
	{
		$query  = "FROM $this->_tbl AS c";
		if (isset($filters['group']) && (int) $filters['group'] >= 0)
		{
			$query .= " LEFT JOIN #__xgroups AS g ON g.gidNumber=c.scope_id";
		}
		$query .= " LEFT JOIN #__viewlevels AS a ON c.access=a.id";

		$where = array();

		if (isset($filters['state']) && (int) $filters['state'] >= 0)
		{
			$where[] = "c.state=" . $this->_db->Quote(intval($filters['state']));
		}
		if (isset($filters['access']))
		{
			if (is_array($filters['access']))
			{
				$filters['access'] = array_map('intval', $filters['access']);
				$where[] = "c.access IN (" . implode(',', $filters['access']) . ")";
			}
			else if ($filters['access'] >= 0)
			{
				$where[] = "c.access=" . $this->_db->Quote(intval($filters['access']));
			}
		}

		if (isset($filters['group']) && (int) $filters['group'] >= 0)
		{
			$where[] = "(c.scope_id=" . $this->_db->Quote(intval($filters['group'])) . " AND c.scope=" . $this->_db->Quote('group') . ")";
		}

		if (isset($filters['scope']) && (string) $filters['scope'])
		{
			$where[] = "c.scope=" . $this->_db->Quote(strtolower($filters['scope']));
		}
		if (isset($filters['scope_id']) && (int) $filters['scope_id'] >= 0)
		{
			$where[] = "c.scope_id=" . $this->_db->Quote(intval($filters['scope_id']));
		}
		if (isset($filters['object_id']) && (int) $filters['object_id'] >= 0)
		{
			$where[] = "c.object_id=" . $this->_db->Quote(intval($filters['object_id']));
		}

		if (isset($filters['search']) && $filters['search'] != '')
		{
			$where[] = "LOWER(c.title) LIKE " . $this->_db->quote('%' . strtolower($filters['search']) . '%');
		}

		if (count($where) > 0)
		{
			$query .= " WHERE ";
			$query .= implode(" AND ", $where);
		}

		return $query;
	}

	/**
	 * Get a record count
	 *
	 * @param      array $filters Filters to construct query from
	 * @return     integer
	 */
	public function getCount($filters=array())
	{
		$filters['limit'] = 0;

		$query = "SELECT COUNT(*) " . $this->buildQuery($filters);

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Get records
	 *
	 * @param      array $filters Filters to construct query from
	 * @return     array
	 */
	public function getRecords($filters=array())
	{
		$query  = "SELECT c.*";
		if (isset($filters['group']) && (int) $filters['group'] >= 0)
		{
			$query .= ", g.cn AS group_alias";
		}
		$query .= ", a.title AS access_level";
		$query .= " " . $this->buildQuery($filters);

		if (!isset($filters['sort']) || !$filters['sort'])
		{
			$filters['sort'] = 'title';
		}
		if (!isset($filters['sort_Dir']) || !$filters['sort_Dir'])
		{
			$filters['sort_Dir'] = 'ASC';
		}
		$query .= " ORDER BY " . $filters['sort'] . " " . $filters['sort_Dir'];

		if (isset($filters['limit']) && $filters['limit'] != 0)
		{
			$query .= ' LIMIT ' . $filters['start'] . ',' . $filters['limit'];
		}

		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}
}
