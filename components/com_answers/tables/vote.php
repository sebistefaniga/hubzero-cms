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
 * @author    Alissa Nedossekina <alisa@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Answers\Tables;

use Hubzero\Utility\Validate;
use User;
use Date;
use Lang;

/**
 * Table class for votes
 */
class Vote extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  JDatabase
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__vote_log', 'id', $db);
	}

	/**
	 * Validate data
	 *
	 * @return  boolean  True if data is valid
	 */
	public function check()
	{
		$this->referenceid = intval($this->referenceid);
		if (!$this->referenceid)
		{
			$this->setError(Lang::txt('Missing reference ID'));
		}

		$this->category = trim($this->category);
		if (!$this->category)
		{
			$this->setError(Lang::txt('Missing category'));
		}

		if (!$this->id)
		{
			$this->voted = ($this->voted) ? $this->voted : Date::toSql();
			$this->voter = ($this->voter) ? $this->voter : User::get('id');
		}

		if (!Validate::ip($this->ip))
		{
			$this->setError(Lang::txt('Invalid IP address'));
		}

		if ($this->getError())
		{
			return false;
		}

		return true;
	}

	/**
	 * Check if a user has voted on an item
	 *
	 * @param   integer  $refid     Reference ID
	 * @param   string   $category  Reference type
	 * @param   integer  $voter     User ID
	 * @return  mixed    False on error, integer on success
	 */
	public function checkVote($refid=null, $category=null, $voter=null)
	{
		if ($refid == null)
		{
			$refid = $this->referenceid;
		}
		if ($refid == null)
		{
			return false;
		}
		if ($category == null)
		{
			$category = $this->category;
		}
		if ($category == null)
		{
			return false;
		}

		$query = "SELECT count(*) FROM $this->_tbl WHERE referenceid=" . $this->_db->Quote($refid) . " AND category = " . $this->_db->Quote($category) . " AND voter=" . $this->_db->Quote($voter);

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Load a vote record
	 *
	 * @param   integer  $refid     Reference ID
	 * @param   string   $category  Reference type
	 * @param   integer  $voter     User ID
	 * @return  mixed    False on error, integer on success
	 */
	public function loadVote($refid=null, $category=null, $voter=null)
	{
		$fields = array(
			'referenceid' => $refid,
			'category'    => $category,
			'voter'       => $voter
		);

		return parent::load($fields);
	}

	/**
	 * Get records
	 *
	 * @param   array  $filters  Filters to build query from
	 * @return  array
	 */
	public function getResults($filters=array())
	{
		$query = "SELECT c.*
				FROM $this->_tbl AS c
				WHERE c.referenceid=" . $this->_db->Quote($filters['id']) . " AND category=" . $this->_db->Quote($filters['category']) . " ORDER BY c.voted DESC";

		$this->_db->setQuery($query);
		return $this->_db->loadObjectList();
	}

	/**
	 * Delete vote record(s)
	 *
	 * @param   integer  $refid     Reference ID
	 * @param   string   $category  Reference type
	 * @param   integer  $voter     User ID
	 * @return  mixed    False on error, integer on success
	 */
	public function deleteVotes($refid=null, $category=null)
	{
		$query = "DELETE FROM $this->_tbl WHERE referenceid=" . $this->_db->Quote($refid) . " AND category = " . $this->_db->Quote($category);

		$this->_db->setQuery($query);
		if (!$this->_db->query())
		{
			$this->setError( $this->_db->getErrorMsg() );
			return false;
		}
		return true;
	}
}

