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
class Category extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  JDatabase
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__forum_categories', 'id', $db);
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
		return 'com_forum.category.' . (int) $this->$k;
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
	 * Load a record and bind to $this
	 *
	 * @param   string   $oid  Record alias
	 * @return  boolean  True on success
	 */
	public function loadByAlias($oid=NULL, $section_id=null, $scope_id=null, $scope='site')
	{
		$fields = array(
			'alias' => trim((string) $oid),
			'state' => 1
		);

		if ($section_id !== null)
		{
			$fields['section_id'] = (int) $section_id;
		}
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
	public function loadByObject($oid=NULL, $section_id=null, $scope_id=null, $scope='site')
	{
		$fields = array(
			'object_id' => intval($oid),
			'state'     => 1
		);

		if ($section_id !== null)
		{
			$fields['section_id'] = (int) $section_id;
		}
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
	 * @param   integer  $group  ID of group the data belongs to
	 * @return  boolean  True if data is bound to $this object
	 */
	public function loadDefault($scope_id=0, $scope='site')
	{
		$result = array(
			'id'          => 0,
			'title'       => Lang::txt('Discussions'),
			'description' => Lang::txt('Default category for all discussions in this forum.'),
			'section_id'  => 0,
			'created_by'  => 0,
			'scope'       => $scope,
			'scope_id'    => $scope_id,
			'state'       => 1,
			'access'      => 1
		);
		$result['alias'] = str_replace(' ', '-', $result['title']);
		$result['alias'] = preg_replace("/[^a-zA-Z0-9\-]/", '', strtolower($result['alias']));

		return $this->bind($result);
	}

	/**
	 * Validate data
	 *
	 * @return  boolean  True if data is valid
	 */
	public function check()
	{
		$this->title = trim($this->title);

		if (!$this->title)
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
			if (!$this->ordering)
			{
				$this->ordering = $this->getHighestOrdering($this->scope, $this->scope_id);
			}
		}
		else
		{
			$this->modified    = Date::toSql();
			$this->modified_by = User::get('id');
		}

		return true;
	}

	/**
	 * Get the last page in the ordering
	 *
	 * @param   string   $offering_id
	 * @return  integer
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
	 * @param   array   $filters  Filters to construct query from
	 * @return  string  SQL
	 */
	protected function _buildQuery($filters=array())
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
		if (isset($filters['closed']))
		{
			$where[] = "c.closed=" . $this->_db->Quote(intval($filters['closed']));
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
		/*if (isset($filters['scope_sub_id']) && (int) $filters['scope_sub_id'] >= 0)
		{
			$where[] = "c.scope_sub_id=" . $this->_db->Quote(intval($filters['scope_sub_id']));
		}*/
		if (isset($filters['section_id']) && (int) $filters['section_id'] >= 0)
		{
			$where[] = "c.section_id=" . $this->_db->Quote(intval($filters['section_id']));
		}
		if (isset($filters['object_id']) && (int) $filters['object_id'] >= 0)
		{
			$where[] = "c.object_id=" . $this->_db->Quote(intval($filters['object_id']));
		}
		if (isset($filters['search']) && $filters['search'] != '')
		{
			$where[] = "(LOWER(c.title) LIKE " . $this->_db->quote('%' . strtolower($filters['search']) . '%') . "
				OR LOWER(c.description) LIKE " . $this->_db->quote('%' . strtolower($filters['search']) . '%') . ")";
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
	 * @param   array    $filters  Filters to construct query from
	 * @return  integer
	 */
	public function getCount($filters=array())
	{
		$filters['limit'] = 0;

		$query = "SELECT COUNT(*) " . $this->_buildQuery($filters);

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Get records
	 *
	 * @param   array  $filters  Filters to construct query from
	 * @return  array
	 */
	public function getRecords($filters=array())
	{
		$flt = "";
		if (isset($filters['scope_sub_id']) && (int) $filters['scope_sub_id'] >= 0)
		{
			$flt = " AND (r.scope_sub_id=" . $this->_db->Quote(intval($filters['scope_sub_id'])) . " OR r.sticky=1)";
		}
		if (isset($filters['access']))
		{
			if (is_array($filters['access']))
			{
				$filters['access'] = array_map('intval', $filters['access']);
				$flt .= " AND r.access IN (" . implode(',', $filters['access']) . ")";
			}
			else if ($filters['access'] >= 0)
			{
				$flt .= " AND r.access=" . $this->_db->Quote(intval($filters['access']));
			}
		}

		if (isset($filters['admin']))
		{
			$query  = "SELECT c.*";
			if (isset($filters['group']) && (int) $filters['group'] >= 0)
			{
				$query .= ", g.cn AS group_alias";
			}
			$query .= ", (SELECT COUNT(*) FROM #__forum_posts AS r WHERE r.category_id=c.id AND r.parent=0 $flt) AS threads,
						(SELECT COUNT(*) FROM #__forum_posts AS r WHERE r.category_id=c.id $flt) AS posts";
		}
		else
		{
			$query  = "SELECT c.*";
			if (isset($filters['group']) && (int) $filters['group'] >= 0)
			{
				$query .= ", g.cn AS group_alias";
			}
			$query .= ", (SELECT COUNT(*) FROM #__forum_posts AS r WHERE r.category_id=c.id AND r.parent=0 AND r.state=1 $flt) AS threads,
						(SELECT COUNT(*) FROM #__forum_posts AS r WHERE r.category_id=c.id AND r.state=1 $flt) AS posts";
		}
		$query .= ", a.title AS access_level";
		$query .= " " . $this->_buildQuery($filters);

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

	/**
	 * Get a count of all threads for a category
	 *
	 * @param   integer  $oid       Category ID
	 * @param   integer  $group_id  Group ID
	 * @return  array
	 */
	public function getThreadCount($oid=null, $scope_id=0, $scope='site')
	{
		$k = $this->_tbl_key;
		if ($oid !== null)
		{
			$this->$k = intval($oid);
		}

		$query = "SELECT COUNT(*) FROM `#__forum_posts` WHERE `category_id`=" . $this->_db->Quote($this->$k) . " AND `scope_id`=" . $this->_db->Quote($scope_id) . " AND `scope`=" . $this->_db->Quote($scope) . " AND parent=0 AND state < 2";

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Get a count of all posts for a category
	 *
	 * @param   integer  $oid       Category ID
	 * @param   integer  $group_id  Group ID
	 * @return  array
	 */
	public function getPostCount($oid=null, $scope_id=0, $scope='site')
	{
		$k = $this->_tbl_key;
		if ($oid !== null)
		{
			$this->$k = intval($oid);
		}

		//$query = "SELECT COUNT(*) FROM `#__forum_posts` WHERE parent IN (SELECT r.id FROM `#__forum_posts` AS r WHERE r.category_id=" . $this->$k . " AND group_id=$group_id AND parent=0 AND state < 2)";
		$query = "SELECT COUNT(*) FROM `#__forum_posts` AS r WHERE r.category_id=" . $this->_db->Quote($this->$k) . " AND scope_id=" . $this->_db->Quote($scope_id) . " AND scope=" . $this->_db->Quote($scope) . " AND parent=0 AND state < 2";
		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Delete a category and all associated content
	 *
	 * @param   integer  $oid  Object ID (primary key)
	 * @return  boolean  True if successful otherwise returns and error message
	 */
	public function delete($oid=null)
	{
		$k = $this->_tbl_key;
		if ($oid)
		{
			$this->$k = intval($oid);
		}

		include_once(__DIR__ . DS . 'post.php');

		$post = new Post($this->_db);
		if (!$post->deleteByCategory($this->$k))
		{
			$this->setError($post->getErrorMsg());
			return false;
		}

		return parent::delete();
	}

	/**
	 * Set the state of records for a section
	 *
	 * @param   integer  $section  Section ID
	 * @param   integer  $state    State (0, 1, 2)
	 * @return  array
	 */
	public function setStateBySection($section=null, $state=null)
	{
		if ($section=== null)
		{
			$section = $this->section_id;
		}
		if ($state === null || $section === null)
		{
			return false;
		}

		if (is_array($section))
		{
			$section = array_map('intval', $section);
			$section = implode(',', $section);
		}
		else
		{
			$section = intval($section);
		}

		$this->_db->setQuery("UPDATE $this->_tbl SET state=" . $this->_db->Quote($state) . " WHERE section_id IN ($section)");
		if (!$this->_db->query())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}

		return true;
	}
}
