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

namespace Components\Citations\Tables;

/**
 * Table class for citation authors
 */
class Author extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  JDatabase
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__citations_authors', 'id', $db);
	}

	/**
	 * Validate data
	 *
	 * @return  boolean  True if data is valid
	 */
	public function check()
	{
		if (trim($this->cid) == '')
		{
			$this->setError(Lang::txt('AUTHOR_MUST_HAVE_CITATION_ID'));
		}
		if (trim($this->author) == '')
		{
			$this->setError(Lang::txt('AUTHOR_MUST_HAVE_TEXT'));
		}

		if ($this->getError())
		{
			return false;
		}

		if (!$this->id)
		{
			$sql = "SELECT ordering from $this->_tbl WHERE `cid`=" . $this->_db->Quote(intval($this->cid)) . " ORDER BY ordering DESC LIMIT 1";
			$this->_db->setQuery($sql);
			$this->ordering = intval($this->_db->loadResult());
			$this->ordering++;
		}

		return true;
	}

	/**
	 * Build a query from filters
	 *
	 * @param   array   $filters  Filters to build query from
	 * @return  string  SQL
	 */
	public function buildQuery($filters)
	{
		$query = "";
		$ands = array();
		if (isset($filters['cid']) && $filters['cid'] != 0)
		{
			$ands[] = "r.cid=" . $this->_db->Quote($filters['cid']);
		}
		if (isset($filters['author_uid']) && $filters['author_uid'] != 0)
		{
			$ands[] = "r.author_uid=" . $this->_db->Quote($filters['author_uid']);
		}
		if (isset($filters['author']) && trim($filters['author']) != '')
		{
			$ands[] = "LOWER(r.author)=" . $this->_db->Quote(strtolower($filters['author']));
		}
		if (count($ands) > 0)
		{
			$query .= " WHERE ";
			$query .= implode(" AND ", $ands);
		}
		if (isset($filters['sort']) && $filters['sort'] != '')
		{
			$query .= " ORDER BY " . $filters['sort'];
		}
		if (isset($filters['limit']) && $filters['limit'] != 0)
		{
			$query .= " LIMIT " . intval($filters['start']) . "," . intval($filters['limit']);
		}

		return $query;
	}

	/**
	 * Get a record count
	 *
	 * @param   array    $filters  Filters to build query from
	 * @return  integer
	 */
	public function getCount($filters=array())
	{
		$query  = "SELECT COUNT(*) FROM $this->_tbl AS r" . $this->buildQuery($filters);

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Get records
	 *
	 * @param   array  $filters  Filters to build query from
	 * @return  array
	 */
	public function getRecords($filters=array())
	{
		if (!isset($filters['sort']) || $filters['sort'] == '')
		{
			$filters['sort'] = 'ordering ASC';
		}

		$query  = "SELECT * FROM $this->_tbl AS r" . $this->buildQuery($filters);

		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}

	/**
	 * Delete entries for a specific citation
	 *
	 * @param   integer  $cid  Citation ID
	 * @return  boolean  True on success, False on error
	 */
	public function deleteForCitation($cid=null)
	{
		if ($cid === null)
		{
			$cid = $this->cid;
		}

		if (!$cid)
		{
			$this->setError(Lang::txt('Missing argument'));
			return false;
		}

		// Remove any types in the remove list
		$this->_db->setQuery("DELETE FROM `$this->_tbl` WHERE `cid`=" . $this->_db->Quote(intval($cid)));
		if (!$this->_db->query())
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
		return true;
	}
}

