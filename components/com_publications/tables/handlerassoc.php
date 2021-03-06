<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Publications\Tables;

/**
 * Table class for publication handler associations
 */
class HandlerAssoc extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param      object &$db JDatabase
	 * @return     void
	 */
	public function __construct( &$db )
	{
		parent::__construct( '#__publication_handler_assoc', 'id', $db );
	}

	/**
	 * Get associated handler(s)
	 *
	 * @param      integer 	$vid 		Publication Version ID
	 * @param      integer 	$elementid  Element ID
	 *
	 * @return     mixed False if error, Object on success
	 */
	public function getAssoc( $vid = NULL, $elementid = NULL )
	{
		if (!intval($vid) || !intval($elementid))
		{
			return false;
		}

		$query  = "SELECT H.*, A.params as configs, A.status, A.ordering FROM $this->_tbl as A ";
		$query .= " JOIN #__publication_handlers as H ON H.id=A.handler_id";
		$query .= " WHERE A.publication_version_id=" . $this->_db->Quote($vid);
		$query .= " AND A.element_id=" . $this->_db->Quote($elementid);
		$query .= " ORDER BY A.ordering ASC";

		$this->_db->setQuery( $query );
		return $this->_db->loadObjectList();
	}

	/**
	 * Load associated handler
	 *
	 * @param      integer 	$vid 		Publication Version ID
	 * @param      integer 	$elementid  Element ID
	 * @param      string 	$handler    Handler name
	 *
	 * @return     mixed False if error, Object on success
	 */
	public function getAssociation( $vid = NULL, $elementid = NULL, $handler = NULL )
	{
		if (!intval($vid) || !intval($elementid) || !$handler)
		{
			return false;
		}

		$query  = "SELECT H.*, A.params as configs, A.status, A.ordering FROM $this->_tbl as A ";
		$query .= " JOIN #__publication_handlers as H ON H.id=A.handler_id";
		$query .= " WHERE A.publication_version_id=" . $this->_db->Quote($vid);
		$query .= " AND A.element_id=" . $this->_db->Quote($elementid);
		$query .= " AND H.name=" . $this->_db->quote($handler);
		$query .= " LIMIT 1";

		$this->_db->setQuery( $query );
		$result = $this->_db->loadObjectList();
		return $result ? $result[0] : NULL;
	}
}
