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
 * @author    Sam Wilson <samwilson@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Courses\Tables;

/**
 * Course section table class
 */
class SectionDate extends \JTable
{
	/**
	 * Contructor method for JTable class
	 *
	 * @param   object  &$db  Database object
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__courses_offering_section_dates', 'id', $db);
	}

	/**
	 * Load a record and bind to $this
	 *
	 * @param   mixed    $oid
	 * @param   string   $scope
	 * @param   integer  $section_id
	 * @return  boolean  True on success
	 */
	public function load($oid=null, $scope=null, $section_id=null)
	{
		if ($oid === null)
		{
			return false;
		}
		if (is_numeric($oid) && $scope === null)
		{
			return parent::load($oid);
		}

		$fields = array(
			'scope'    => trim($scope),
			'scope_id' => intval($oid)
		);
		if ($section_id !== null)
		{
			$fields['section_id'] = intval($section_id);
		}

		return parent::load($fields);
	}

	/**
	 * Override the check function to do a little input cleanup
	 *
	 * @return  boolean
	 */
	public function check()
	{
		$this->section_id = intval($this->section_id);
		if (!$this->section_id)
		{
			$this->setError(Lang::txt('Please provide a section ID.'));
			return false;
		}

		$this->scope = trim($this->scope);
		if (!$this->scope)
		{
			$this->setError(Lang::txt('Please provide a scope.'));
			return false;
		}

		$this->scope_id = intval($this->scope_id);
		if (!$this->scope_id)
		{
			$this->setError(Lang::txt('Please provide a scope ID.'));
			return false;
		}

		if (!$this->id)
		{
			$this->created = \Date::toSql();
			$this->created_by = User::get('id');

			// Make sure the record doesn't already exist
			$query  = "SELECT id FROM $this->_tbl WHERE scope=" . $this->_db->Quote($this->scope) . " AND scope_id=" . $this->_db->Quote($this->scope_id);
			$query .= " AND section_id=" . $this->_db->Quote($this->section_id);
			$query .= " LIMIT 1";

			$this->_db->setQuery($query);
			if ($id = $this->_db->loadResult())
			{
				$this->id = $id;
			}
		}

		return true;
	}

	/**
	 * Build query method
	 *
	 * @param   array   $filters
	 * @return  string  SQL
	 */
	private function _buildQuery($filters=array())
	{
		$query  = " FROM $this->_tbl AS sd";

		$where = array();

		if (isset($filters['section_id']) && $filters['section_id'] >= 0)
		{
			$where[] = "sd.section_id=" . $this->_db->Quote(intval($filters['section_id']));
		}

		if (isset($filters['scope']) && $filters['scope'])
		{
			$where[] = "sd.scope=" . $this->_db->Quote($filters['scope']);
		}

		if (isset($filters['scope_id']) && $filters['scope_id'] > 0)
		{
			$where[] = "sd.scope_id=" . $this->_db->Quote(intval($filters['scope_id']));
		}

		if (count($where) > 0)
		{
			$query .= " WHERE " . implode(" AND ", $where);
		}

		return $query;
	}

	/**
	 * Get a count of entries
	 *
	 * @param   array    $filters
	 * @return  integer
	 */
	public function count($filters=array())
	{
		$query  = "SELECT COUNT(sd.id)";
		$query .= $this->_buildquery($filters);

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Get a list of entries
	 *
	 * @param   array  $filters
	 * @return  array
	 */
	public function find($filters=array())
	{
		$query  = "SELECT sd.*";
		$query .= $this->_buildquery($filters);

		if (!isset($filters['sort']) || $filters['sort'] == '')
		{
			$filters['sort'] = 'sd.publish_up';
		}
		if (!isset($filters['sort_Dir']) || !in_array(strtoupper($filters['sort_Dir']), 'ASC', 'DESC'))
		{
			$filters['sort_Dir'] = 'ASC';
		}

		$query .= " ORDER BY " . $filters['sort'] . " " . $filters['sort_Dir'];

		if (isset($filters['limit']) && $filters['limit'] != 0)
		{
			if (!isset($filters['start']))
			{
				$filters['start'] = 0;
			}
			$query .= " LIMIT " . (int) $filters['start'] . "," . (int) $filters['limit'];
		}

		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}

	/**
	 * Delete all records by section ID
	 *
	 * @param   integer  $section_id
	 * @return  boolean
	 */
	public function deleteBySection($section_id)
	{
		$query  = "DELETE FROM $this->_tbl WHERE `section_id`=" . $this->_db->Quote($section_id);

		$this->_db->setQuery($query);
		if (!$this->_db->query())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		return true;
	}
}