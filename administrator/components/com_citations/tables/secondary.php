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
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Short description for 'CitationsSecondary'
 * 
 * Long description (if any) ...
 */
class CitationsSecondary extends JTable
{

	/**
	 * Description for 'id'
	 * 
	 * @var unknown
	 */
	var $id            = NULL;  // @var int(11) Primary key

	/**
	 * Description for 'cid'
	 * 
	 * @var unknown
	 */
	var $cid           = NULL;  // @var int(11)

	/**
	 * Description for 'sec_cits_cnt'
	 * 
	 * @var unknown
	 */
	var $sec_cits_cnt  = NULL;  // @var int(11)

	/**
	 * Description for 'search_string'
	 * 
	 * @var unknown
	 */
	var $search_string = NULL;  // @var tinytext()

	//-----------

	/**
	 * Short description for '__construct'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown &$db Parameter description (if any) ...
	 * @return     void
	 */
	public function __construct( &$db )
	{
		parent::__construct( '#__citations_secondary', 'id', $db );
	}

	/**
	 * Short description for 'check'
	 * 
	 * Long description (if any) ...
	 * 
	 * @return     boolean Return description (if any) ...
	 */
	public function check()
	{
		if (trim( $this->cid ) == '') {
			$this->setError( JText::_('SECONDARY_MUST_HAVE_CITATION_ID') );
			return false;
		}
		if (trim( $this->sec_cits_cnt ) == '') {
			$this->setError( JText::_('SECONDARY_MUST_HAVE_COUNT') );
			return false;
		}
		return true;
	}

	/**
	 * Short description for 'buildQuery'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      array $filters Parameter description (if any) ...
	 * @return     string Return description (if any) ...
	 */
	public function buildQuery( $filters )
	{
		$query = "";
		$ands = array();
		if (isset($filters['cid']) && $filters['cid'] != 0) {
			$ands[] = "r.cid='".$filters['cid']."'";
		}
		if (isset($filters['search_string']) && $filters['search_string'] != '') {
			$ands[] = "LOWER(r.search_string)='".strtolower($filters['search_string'])."'";
		}
		if (count($ands) > 0) {
			$query .= " WHERE ";
			$query .= implode(" AND ", $ands);
		}
		if (isset($filters['sort']) && $filters['sort'] != '') {
			$query .= " ORDER BY ".$filters['sort'];
		}
		if (isset($filters['limit']) && $filters['limit'] != 0) {
			$query .= " LIMIT ".$filters['start'].",".$filters['limit'];
		}

		return $query;
	}

	/**
	 * Short description for 'getCount'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      array $filters Parameter description (if any) ...
	 * @return     object Return description (if any) ...
	 */
	public function getCount( $filters=array() )
	{
		$query  = "SELECT COUNT(*) FROM $this->_tbl AS r" . $this->buildQuery( $filters );

		$this->_db->setQuery( $query );
		return $this->_db->loadResult();
	}

	/**
	 * Short description for 'getRecords'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      array $filters Parameter description (if any) ...
	 * @return     object Return description (if any) ...
	 */
	public function getRecords( $filters=array() )
	{
		$query  = "SELECT * FROM $this->_tbl AS r" . $this->buildQuery( $filters );

		$this->_db->setQuery( $query );
		return $this->_db->loadObjectList();
	}
}

