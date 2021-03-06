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
use Lang;
use Date;
use User;

/**
 * Table class for question votes
 */
class QuestionsLog extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  JDatabase
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__answers_questions_log', 'id', $db);
	}

	/**
	 * Validate data
	 *
	 * @return  boolean  True if data is valid
	 */
	public function check()
	{
		$this->question_id = intval($this->question_id);
		if (!$this->question_id)
		{
			$this->setError(Lang::txt('Missing question ID'));
		}

		$this->voter = intval($this->voter);
		if (!$this->voter)
		{
			$this->voter = User::get('id');
		}

		if (!$this->expires)
		{
			$this->expires = Date::of(time() + (7 * 24 * 60 * 60))->toSql(); // in a week
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
	 * Check if a user has voted
	 *
	 * @param   integer  $qid    Question ID
	 * @param   string   $ip     IP address
	 * @param   integer  $voter  Voter user ID
	 * @return  mixed    False if error, integer on success
	 */
	public function checkVote($qid=null, $ip=null, $voter=null)
	{
		if ($qid == null)
		{
			$qid = $this->question_id;
		}
		if ($qid == null)
		{
			return false;
		}

		$query = "SELECT COUNT(*) FROM `$this->_tbl` WHERE question_id=" . $this->_db->Quote($qid);
		if ($voter !== null)
		{
			$query .= " AND voter=" . $this->_db->Quote($voter);
		}
		else
		{
			$query .= " AND ip=" . $this->_db->Quote($ip);
		}


		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}
}

