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
 * Short description for 'Cart'
 * 
 * Long description (if any) ...
 */
class Cart extends JTable
{

	/**
	 * Description for 'id'
	 * 
	 * @var unknown
	 */
	var $id         = NULL;  // @var int(11) Primary key

	/**
	 * Description for 'uid'
	 * 
	 * @var unknown
	 */
	var $uid    	= NULL;  // @var int(11)

	/**
	 * Description for 'itemid'
	 * 
	 * @var unknown
	 */
	var $itemid     = NULL;  // @var int(11)

	/**
	 * Description for 'type'
	 * 
	 * @var unknown
	 */
	var $type    	= NULL;  // @var varchar(20)

	/**
	 * Description for 'quantity'
	 * 
	 * @var unknown
	 */
	var $quantity   = NULL;  // @var int(11)

	/**
	 * Description for 'added'
	 * 
	 * @var unknown
	 */
	var $added  	= NULL;  // @var datetime

	/**
	 * Description for 'selections'
	 * 
	 * @var unknown
	 */
	var $selections = NULL;  // @var text

	//------------

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
		parent::__construct( '#__cart', 'id', $db );
	}

	/**
	 * Short description for 'checkCartItem'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      string $id Parameter description (if any) ...
	 * @param      string $uid Parameter description (if any) ...
	 * @return     mixed Return description (if any) ...
	 */
	public function checkCartItem( $id=null, $uid)
	{
		if ($id == null or $uid == null) {
			return false;
		}

		$sql = "SELECT id, quantity FROM $this->_tbl WHERE itemid='".$id."' AND uid=".$uid;
		$this->_db->setQuery( $sql );
		return $this->_db->loadObjectList();
	}

	/**
	 * Short description for 'getCartItems'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      string $uid Parameter description (if any) ...
	 * @param      string $rtrn Parameter description (if any) ...
	 * @return     mixed Return description (if any) ...
	 */
	public function getCartItems($uid, $rtrn='')
	{
		$total = 0;
		if ($uid == null) {
			return false;
		}

		// clean-up items with zero quantity
		$sql = "DELETE FROM $this->_tbl WHERE quantity=0";
		$this->_db->setQuery($sql);
		$this->_db->query();

		$query  = "SELECT B.quantity, B.itemid, B.uid, B.added, B.selections, a.title, a.price, a.available, a.params, a.type, a.category ";
		$query .= " FROM $this->_tbl AS B, #__store AS a";
		$query .= " WHERE a.id = B.itemid AND B.uid=".$uid;
		$query .= " ORDER BY B.id DESC";
		$this->_db->setQuery( $query);
		$result = $this->_db->loadObjectList();

		if ($result) {
			foreach ($result as $r)
			{
				$price = $r->price * $r->quantity;
				if ($r->available) {
					$total = $total + $price;
				}

				$params 	 		= new JParameter( $r->params );
				$selections  		= new JParameter( $r->selections );

				// get size selection
				$r->sizes    		= $params->get( 'size', '' );
				$r->sizes 			= str_replace(" ","",$r->sizes);
				$r->selectedsize    = trim($selections->get( 'size', '' ));
				$r->sizes    		= split(',',$r->sizes);

				// get color selection
				$r->colors    		= $params->get( 'color', '' );
				$r->colors 			= str_replace(" ","",$r->colors);
				$r->selectedcolor   = trim($selections->get( 'color', '' ));
				$r->colors    		= split(',',$r->colors);
			}
		}

		if ($rtrn) {
			$result = $total; // total cost of items in cart
		}

		return $result;
	}

	/**
	 * Short description for 'saveCart'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      array $posteditems Parameter description (if any) ...
	 * @param      string $uid Parameter description (if any) ...
	 * @return     boolean Return description (if any) ...
	 */
	public function saveCart( $posteditems, $uid)
	{
		if ($uid == null) {
			return false;
		}

		// get current cart items
		$items = $this->getCartItems($uid);
		if ($items) {
			foreach ($items as $item)
			{
				if ($item->type != 2) { // not service	
					$size 			= (isset($item->selectedsize)) ? $item->selectedsize : '';
					$color 			= (isset($item->color)) ? $item->color : '';
					$sizechoice 	= (isset($posteditems['size'.$item->itemid])) ? $posteditems['size'.$item->itemid] : $size;
					$colorchoice 	= (isset($posteditems['color'.$item->itemid])) ? $posteditems['color'.$item->itemid] : $color;
					$newquantity 	= (isset($posteditems['num'.$item->itemid])) ? $posteditems['num'.$item->itemid] : $item->quantity;

					$selection	    = '';
					$selection	   .= 'size=';
					$selection 	   .= $sizechoice;
					$selection	   .= '\n';
					$selection	   .= 'color=';
					$selection 	   .= $colorchoice;

					$query  = "UPDATE $this->_tbl SET quantity='".$newquantity."',";
					$query .= " selections='".$selection."'";
					$query .= " WHERE itemid=".$item->itemid;
					$query .= " AND uid=".$uid;
					$this->_db->setQuery( $query);
					$this->_db->query();
				}
			}
		}
	}

	/**
	 * Short description for 'deleteCartItem'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      string $id Parameter description (if any) ...
	 * @param      string $uid Parameter description (if any) ...
	 * @param      integer $all Parameter description (if any) ...
	 * @return     void
	 */
	public function deleteCartItem($id, $uid, $all=0)
	{
		$sql = "DELETE FROM $this->_tbl WHERE uid='".$uid."'  ";
		if (!$all && $id) {
			$sql.= "AND itemid='".$id."' ";
		}

		$this->_db->setQuery( $sql);
		$this->_db->query();
	}

	/**
	 * Short description for 'deleteUnavail'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      string $uid Parameter description (if any) ...
	 * @param      array $items Parameter description (if any) ...
	 * @return     boolean Return description (if any) ...
	 */
	public function deleteUnavail( $uid, $items)
	{
		if ($uid == null) {
			return false;
		}
		if (count($items) > 0) {
			foreach ($items as $i)
			{
				if ($i->available == 0) {
					$sql = "DELETE FROM $this->_tbl WHERE itemid=".$i->itemid." AND uid=".$uid;
					$this->_db->setQuery( $sql);
					$this->_db->query();
				}
			}
		}
	}

	/**
	 * Short description for 'deleteItem'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $itemid Parameter description (if any) ...
	 * @param      unknown $uid Parameter description (if any) ...
	 * @param      string $type Parameter description (if any) ...
	 * @return     boolean Return description (if any) ...
	 */
	public function deleteItem($itemid=null, $uid=null, $type='merchandise')
	{
		if ($itemid == null) {
			return false;
		}
		if ($uid == null) {
			return false;
		}

		$sql = "DELETE FROM $this->_tbl WHERE itemid='$itemid' AND type='$type' AND uid=$uid";
		$this->_db->setQuery($sql);
		if (!$this->_db->query()) {
			$this->setError( $this->_db->getErrorMsg() );
			return false;
		}
		return true;
	}
}

