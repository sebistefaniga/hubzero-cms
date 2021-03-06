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

namespace Components\Wishlist\Helpers;

use Components\Wishlist\Tables;
use Hubzero\Base\Object;
use Hubzero\Bank\Teller;
use Hubzero\Bank\Transaction;
use Component;
use User;
use Lang;

/**
 * Wishlist Economy class:
 * Stores economy funtions for wishlists
 */
class Economy extends Object
{
	/**
	 * JDatabase
	 *
	 * @var object
	 */
	private $_db = NULL;

	/**
	 * Constructor
	 *
	 * @param   object  &$db  JDatabase
	 * @return  void
	 */
	public function __construct(&$db)
	{
		$this->_db = $db;
	}

	/**
	 * Get a list of payees for a wish
	 *
	 * @param   integer  $wishid  Wish ID
	 * @return  array
	 */
	public function getPayees($wishid)
	{
		if (!$wishid)
		{
			return null;
		}
		$sql = "SELECT DISTINCT uid FROM `#__users_transactions` WHERE category='wish' AND referenceid=$wishid AND type='hold'";
		$this->_db->setQuery($sql);
		return $this->_db->loadObjectList();
	}

	/**
	 * Get total payment for a wish and user
	 *
	 * @param   integer  $wishid  Wish ID
	 * @param   integer  $uid     User ID
	 * @return  integer
	 */
	public function getTotalPayment($wishid, $uid)
	{
		if (!$wishid or !$uid)
		{
			return null;
		}
		$sql = "SELECT SUM(amount) FROM `#__users_transactions` WHERE category='wish' AND referenceid='$wishid' AND type='hold' AND uid='$uid'";
		$this->_db->setQuery($sql);
		return $this->_db->loadResult();
	}

	/**
	 * Adjust credits for a wish with a bonus assigned
	 *
	 * @param   integer  $wishid  Wish ID
	 * @return  void
	 */
	public function cleanupBonus($wishid)
	{
		if (!$wishid)
		{
			return null;
		}
		require_once(dirname(__DIR__) . DS . 'models' . DS . 'wishlist.php');
		$objWish = new Tables\Wish($this->_db);
		$wish = $objWish->get_wish($wishid, '', 1);

		if ($wish->bonus > 0)
		{
			// Adjust credits
			$payees = $this->getPayees($wishid);
			if ($payees)
			{
				foreach ($payees as $p)
				{
					$BTL = new Teller($this->_db , $p->uid);
					$hold = $this->getTotalPayment($wishid, $p->uid);
					if ($hold)
					{
						$credit = $BTL->credit_summary();
						$adjusted = $credit - $hold;
						$BTL->credit_adjustment($adjusted);
					}
				}
			}
			 // Delete holds
			$BT = new Transaction($this->_db);
			$BT->deleteRecords('wish', 'hold', $wishid);
		}
	}

	/**
	 * Distribute points
	 *
	 * @param   integer  $wishid  Wish ID
	 * @param   string   $type    Transaction type
	 * @param   number   $points  Points to distribute
	 * @return  void
	 */
	public function distribute_points($wishid, $type='grant', $points=0)
	{
		if (!$wishid)
		{
			return null;
		}

		require_once(dirname(__DIR__) . DS . 'models' . DS . 'wishlist.php');
		$objWish = new Tables\Wish($this->_db);
		$wish = $objWish->get_wish ($wishid);

		$points = !$points ? $wish->bonus : $points;

		// Points for list owners
		if ($points > 0 && $type!='royalty')
		{
			// Get the component parameters
			$wconfig = Component::params('com_wishlist');
			$admingroup = $wconfig->get('group', 'hubadmin');

			// get list owners
			$objOwner = new Tables\Owner( $this->_db);
			$owners   = $objOwner->get_owners($wish->wishlist, $admingroup, '', 0, $wishid);
			$owners   = $owners['individuals'];

			$mainshare = $wish->assigned ? $points*0.8 : 0; //80%
			$commonshare = $mainshare ? ($points - $mainshare)/count($owners) : $points/count($owners);

			// give the remaining 20%
			if ($owners && $commonshare)
			{
				foreach ($owners as $owner)
				{
					$o = User::getInstance($owner);
					if (!is_object($o) || !$o->get('id'))
					{
						continue;
					}
					$BTLO = new Teller($this->_db , $owner);
					if ($wish->assigned && $wish->assigned == $owner)
					{
						//$BTLO->deposit($mainshare, Lang::txt('Bonus for fulfilling assigned wish').' #'.$wishid.' '.Lang::txt('on list').' #'.$wish->wishlist, 'wish', $wishid);
						$mainshare += $commonshare;
					}
					else
					{
						$BTLO->deposit($commonshare, Lang::txt('Bonus for fulfilling wish #%s on list #%s', $wishid, $wish->wishlist), 'wish', $wishid);
					}
				}
			}
			else
			{
				$mainshare += $commonshare;
			}

			// give main share
			if ($wish->assigned && $mainshare)
			{
				$o = User::getInstance($wish->assigned);
				if (is_object($o) && $o->get('id'))
				{
					$BTLM = new Teller($this->_db , $wish->assigned);
					$BTLM->deposit($mainshare, Lang::txt('Bonus for fulfilling assigned wish #%s on list #%s', $wishid, $wish->wishlist), 'wish', $wishid);
				}
			}

			// Adjust credits
			$payees = $this->getPayees($wishid);
			if ($payees)
			{
				foreach ($payees as $p)
				{
					$o = User::getInstance($p->uid);
					if (!is_object($o) || !$o->get('id'))
					{
						continue;
					}

					$BTL = new Teller($this->_db, $p->uid);
					$hold = $this->getTotalPayment($wishid, $p->uid);
					if ($hold)
					{
						$credit = $BTL->credit_summary();
						$adjusted = $credit - $hold;
						$BTL->credit_adjustment($adjusted);

						// withdraw bonus amount
						$BTL->withdraw($hold, Lang::txt('Bonus payment for granted wish #%s on list #%s', $wishid, $wish->wishlist), 'wish', $wishid);
					}
				}
			}

			// Remove holds if exist
			if ($wish->bonus)
			{
				$BT = new Transaction($this->_db);
				$BT->deleteRecords('wish', 'hold', $wishid);
			}
		}

		// Points for wish author (needs to be granted by another person)
		if ($wish->ranking > 0 && $wish->proposed_by != User::get('id') && $wish->proposed_by)
		{
			$o = User::getInstance($wish->proposed_by);
			if (is_object($o) && $o->get('id'))
			{
				$BTLA = new Teller($this->_db , $wish->proposed_by);
				$BTLA->deposit($wish->ranking, Lang::txt('Your wish #%s on list #%s was granted', $wishid, $wish->wishlist), 'wish', $wishid);
			}
		}
	}
}

