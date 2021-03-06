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

namespace Components\Resources\Tables;

/**
 * Table class for resource detailed media tracking
 */
class MediaTrackingDetailed extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  JDatabase
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__media_tracking_detailed', 'id', $db);
	}

	/**
	 * Check method used to verify data on save
	 * 
	 * @return  bool  Validation check result
	 */
	public function check()
	{
		// session id check
		if (trim($this->session_id) == '')
		{
			$this->setError(\Lang::txt('Missing required session identifier.'));
		}

		// IP check
		if (trim($this->ip_address) == '')
		{
			$this->setError(\Lang::txt('Missing required session identifier.'));
		}

		// object id/type check
		if (trim($this->object_id) == '' || trim($this->object_type) == '')
		{
			$this->setError(\Lang::txt('Missing required object id or object type.'));
		}

		if ($this->getError())
		{
			return false;
		}

		return true;
	}

	/**
	 * Load a record by ID
	 *
	 * @param   integer  $id  Record ID
	 * @return  object
	 */
	public function loadByDetailId($id)
	{
		$this->_db->setQuery("SELECT m.* FROM $this->_tbl AS m WHERE id=" . $this->_db->quote($id));
		return $this->_db->loadObject();
	}
}