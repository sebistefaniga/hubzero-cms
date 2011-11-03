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
 * Short description for 'Hubzero_Message_Seen'
 * 
 * Long description (if any) ...
 */
class Hubzero_Message_Seen extends JTable
{

	/**
	 * Description for 'mid'
	 * 
	 * @var unknown
	 */
	var $mid      = NULL;  // @var int(11)

	/**
	 * Description for 'uid'
	 * 
	 * @var unknown
	 */
	var $uid      = NULL;  // @var int(11)

	/**
	 * Description for 'whenseen'
	 * 
	 * @var unknown
	 */
	var $whenseen = NULL;  // @var datetime(0000-00-00 00:00:00)

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
		parent::__construct( '#__xmessage_seen', 'uid', $db );
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
		if (trim( $this->mid ) == '') {
			$this->setError( JText::_('Please provide a message ID.') );
			return false;
		}
		if (trim( $this->uid ) == '') {
			$this->setError( JText::_('Please provide a user ID.') );
			return false;
		}
		return true;
	}

	/**
	 * Short description for 'loadRecord'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $mid Parameter description (if any) ...
	 * @param      unknown $uid Parameter description (if any) ...
	 * @return     boolean Return description (if any) ...
	 */
	public function loadRecord( $mid=NULL, $uid=NULL )
	{
		if (!$mid) {
			$mid = $this->mid;
		}
		if (!$uid) {
			$uid = $this->uid;
		}
		if (!$mid || !$uid) {
			return false;
		}

		$this->_db->setQuery( "SELECT * FROM $this->_tbl WHERE mid='$mid' AND uid='$uid'" );
		if ($result = $this->_db->loadAssoc()) {
			return $this->bind( $result );
		} else {
			$this->setError( $this->_db->getErrorMsg() );
			return false;
		}
	}

	/**
	 * Short description for 'store'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      boolean $new Parameter description (if any) ...
	 * @return     boolean Return description (if any) ...
	 */
	public function store( $new=false )
	{
		if (!$new) {
			$this->_db->setQuery( "UPDATE $this->_tbl SET whenseen='$this->whenseen' WHERE mid='$this->mid' AND uid='$this->uid'");
			if ($this->_db->query()) {
				$ret = true;
			} else {
				$ret = false;
			}
		} else {
			//$ret = $this->_db->insertObject( $this->_tbl, $this, $this->_tbl_key );
			$this->_db->setQuery( "INSERT INTO $this->_tbl (mid, uid, whenseen) VALUES ('$this->mid', '$this->uid', '$this->whenseen')");
			if ($this->_db->query()) {
				$ret = true;
			} else {
				$ret = false;
			}
		}
		if (!$ret) {
			$this->setError( strtolower(get_class( $this )).'::store failed <br />' . $this->_db->getErrorMsg() );
			return false;
		} else {
			return true;
		}
	}
}

