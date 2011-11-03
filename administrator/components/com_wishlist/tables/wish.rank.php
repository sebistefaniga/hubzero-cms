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
 * @author    Alissa Nedossekina <alisa@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

/**
 * Short description for 'WishRank'
 * 
 * Long description (if any) ...
 */
class WishRank extends JTable
{

	/**
	 * Description for 'id'
	 * 
	 * @var unknown
	 */
	var $id         	= NULL;  // @var int(11) Primary key

	/**
	 * Description for 'wishid'
	 * 
	 * @var unknown
	 */
	var $wishid      	= NULL;  // @var int

	/**
	 * Description for 'userid'
	 * 
	 * @var unknown
	 */
	var $userid 		= NULL;  // @var int

	/**
	 * Description for 'voted'
	 * 
	 * @var unknown
	 */
	var $voted    	    = NULL;  // @var datetime (0000-00-00 00:00:00)

	/**
	 * Description for 'importance'
	 * 
	 * @var unknown
	 */
	var $importance     = NULL;  // @var int(3)

	/**
	 * Description for 'effort'
	 * 
	 * @var unknown
	 */
	var $effort		    = NULL;  // @var int(3)

	/**
	 * Description for 'due'
	 * 
	 * @var unknown
	 */
	var $due    	    = NULL;  // @var datetime (0000-00-00 00:00:00)

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
		parent::__construct( '#__wishlist_vote', 'id', $db );
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
		if (trim( $this->wishid ) == '') {
			$this->setError( JText::_('WISHLIST_ERROR_NO_WISHID') );
			return false;
		}

		return true;
	}

	/**
	 * Short description for 'load_vote'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $oid Parameter description (if any) ...
	 * @param      unknown $wishid Parameter description (if any) ...
	 * @return     boolean Return description (if any) ...
	 */
	public function load_vote( $oid=NULL, $wishid=NULL )
	{
		if ($oid === NULL) {
			$oid = $this->userid;
		}
		if ($wishid === NULL) {
			$wishid = $this->wishid;
		}

		if ($oid === NULL or $wishid === NULL) {
			return false;
		}
		$this->_db->setQuery( "SELECT * FROM #__wishlist_vote WHERE userid='$oid' AND wishid='$wishid'");
		if ($result = $this->_db->loadAssoc()) {
			return $this->bind( $result );
		} else {
			$this->setError( $this->_db->getErrorMsg() );
			return false;
		}
	}

	/**
	 * Short description for 'get_votes'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $wishid Parameter description (if any) ...
	 * @return     mixed Return description (if any) ...
	 */
	public function get_votes( $wishid=NULL )
	{
		if ($wishid === NULL) {
			$wishid = $this->wishid;
		}

		if ($wishid === NULL) {
			return false;
		}
		$this->_db->setQuery( "SELECT * FROM #__wishlist_vote WHERE wishid='$wishid'");
		return $this->_db->loadObjectList();
	}

	/**
	 * Short description for 'remove_vote'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $wishid Parameter description (if any) ...
	 * @param      string $oid Parameter description (if any) ...
	 * @return     boolean Return description (if any) ...
	 */
	public function remove_vote( $wishid=NULL, $oid=NULL )
	{
		if ($oid === NULL) {
			$oid = $this->userid;
		}
		if ($wishid === NULL) {
			$wishid = $this->wishid;
		}

		if ($wishid === NULL) {
			return false;
		}

		$query = "DELETE FROM #__wishlist_vote WHERE wishid='$wishid'";
		if ($oid) {
			$query .= " AND userid=".$oid;
		}
		$this->_db->setQuery($query);
		if (!$this->_db->query()) {
			$this->setError( $this->_db->getErrorMsg() );
			return false;
		}

		return true;
	}
}

