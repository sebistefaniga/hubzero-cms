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
 * @package   Ilya Shunko <ishunko@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

require_once(JPATH_ROOT . DS . 'components' . DS . 'com_cart' . DS . 'helpers' . DS . 'Helper.php');

/**
 * Core shopping cart
 */
abstract class CartModelCart
{
	// Database instance
	var $db = NULL;

	// Cart ID
	var $crtId = NULL;

	// Debug mode
	var $debug = false;

	// TODO: Move to config
	// Transaction time to live: TTL -- transaction active
	var $transactionTTL = 60;    // 1 hour
	// Transaction kill age -- age at which transaction gets deleted forever
	var $transactionKillAge = 43200; // 30 days

	protected static $securitySalt = 'ERDCVcvk$sad!ccsso====++!w';

	/**
	 * Cart constructor
	 *
	 * @param void
	 * @return void
	 */
	public function __construct()
	{
		// Initialize DB
		$this->_db = JFactory::getDBO();

		// Load language file
		Lang::load('com_cart');
	}

	/**
	 * Add SKU to cart
	 *
	 * @param int   SKU ID
	 * @param int   Quantity
	 * @return void
	 */
	public function add($sId, $qty = 1)
	{
		$this->doItem($sId, 'add', $qty);
	}

	/**
	 * Update/set SKU in cart
	 *
	 * @param int   SKU ID
	 * @param int   Quantity
	 * @param bool  Retain old value
	 * @return void
	 */
	public function update($sId, $qty = 1, $retainOldValue = false)
	{
		$this->doItem($sId, 'set', $qty, $retainOldValue);
	}

	/**
	 * Delete SKU from cart
	 *
	 * @param SKU ID
	 * @return void
	 */
	public function delete($sId)
	{
		// Not sure if it is still in use
		throw new Exception('I thought it was not in use anymore...');
		$this->doItem($sId, 'set', 0);

		// Update session
		$this->syncSessionCart();
	}

	/**
	 * Gets all saved shipping addresses for this user
	 *
	 * @param int       Currently logged in user ID
	 * @return array
	 */
	public function getSavedShippingAddresses($uId)
	{
		if (!Cart_Helper::isNonNegativeInt($uId, false))
		{
			throw new Exception(JGLOBAL_AUTH_USER_NOT_FOUND);
		}

		// Get all user addresses
		$sql = 'SELECT * FROM `#__cart_saved_addresses` WHERE `uidNumber` = ' . $this->_db->quote($uId);
		$this->_db->setQuery($sql);
		$shippingAddresses = $this->_db->loadObjectList();

		if (empty($shippingAddresses))
		{
			return false;
		}

		return $shippingAddresses;
	}

	/**
	 * Get cart items from the database
	 *
	 * @param void
	 * @return Object with two elements: array of SKU info in the cart and array of SKU IDs in the cart
	 */
	protected function getCartItems()
	{
		if ($this->debug)
		{
			echo "<br>Getting items from DB";
		}

		$sql = "SELECT `sId`, `crtiQty`, `crtiPrice`, `crtiOldPrice` FROM `#__cart_cart_items` crti WHERE crti.`crtId` = {$this->crtId}";
		$this->_db->setQuery($sql);
		// Get all info
		$allSkuInfo = $this->_db->loadObjectList('sId');
		// Get just sku IDs
		$skus = $this->_db->loadResultArray();

		$items = new stdClass();
		$items->allSkuInfo = $allSkuInfo;
		$items->skus = $skus;
		return $items;
	}

	/**
	 * Get a single item in the cart
	 *
	 * @param SKU ID
	 * @return object SKU cart info
	 */
	protected function getCartItem($sId)
	{
		$sql = 'SELECT `crtiQty` FROM `#__cart_cart_items`
				WHERE `sId` = ' . $this->_db->quote($sId) . ' AND `crtId` = ' . $this->_db->quote($this->crtId);
		$this->_db->setQuery($sql);
		$skuCartInfo = $this->_db->loadObject();

		if ($skuCartInfo) {
			return $skuCartInfo;
		}
		return false;
	}

	/**
	 * Check if cart is linked to any member's ID
	 *
	 * @param $crtId Cart ID
	 * @return bool
	 */
	protected function cartIsLinked($crtId)
	{
		$sql = "SELECT COUNT(`crtId`) FROM `#__cart_carts` WHERE `crtId` = {$crtId} AND `uidNumber` IS NOT NULL";
		$this->_db->setQuery($sql);
		$isLinked = $this->_db->loadResult();

		if ($isLinked)
		{
			return true;
		}
		return false;
	}

	/**
	 * Check if user's cart exists and return its ID
	 * @param   int $uId User ID
	 * @return  int Cart ID, false if no user cart exists
	 */
	protected function getUserCartId($uId)
	{
		$sql = "SELECT `crtId` FROM `#__cart_carts` WHERE `uidNumber` = '{$uId}'";
		$this->_db->setQuery($sql);
		$crtId = $this->_db->loadResult();

		if (!empty($crtId))
		{
			return $crtId;
		}
		return false;
	}

	/**
	 * Check if the cart exists.
	 *
	 * @param void
	 * @return bool
	 */
	protected function exists()
	{
		$sql = 'SELECT `crtId` FROM `#__cart_carts` WHERE `crtId` = ' . $this->_db->quote($this->crtId);
		$this->_db->setQuery($sql);
		$crtId = $this->_db->loadResult();

		if (!empty($crtId))
		{
			return true;
		}
		return false;
	}

	/**
	 * Update cart SKU, set as unavailable (if SKU or product get deleted or become unavailable)
	 *
	 * @param 	int		SKU ID
	 * @return void
	 */
	protected function markItemUnavailable($sId)
	{
		$sql = "UPDATE `#__cart_cart_items` SET `crtiAvailable` = 0
				WHERE `crtId` = {$this->crtId} AND `sId` = " . $this->_db->quote($sId);
		$this->_db->setQuery($sql);
		$this->_db->query();
	}

	/**
	 * Update/add SKU/quantity to cart, update the price in the cart, save old price and inventory level (if requested)
	 *
	 * @param int       SKU ID
	 * @param string    Update Method:  add - adds to the existing quantity,
	 *                                  set - ignores existing quantity and sets a new value,
	 *                                  sync - simply checks/updates inventory and pricing
	 * @param int       Quantity
	 * @param bool      Flag determining whether the old qty should be saved (only when it goes down);
	 *                  price get saved in either case
	 * @return void
	 */
	protected function doItem($sId, $mode = 'add', $qty = 1, $retainOldValue = false)
	{
		// Check quantity: must be a positive integer or zero
		if (!Cart_Helper::isNonNegativeInt($qty))
		{
			throw new Exception(Lang::txt('COM_CART_INCORRECT_QTY'));
		}
		elseif ($qty == 0 && !$retainOldValue)
		{
			// Delete if quantity is set to zero
			if ($mode == 'set')
			{
				$this->deleteItem($sId);
				return;
			}
			else {
				throw new Exception(Lang::txt('COM_CART_INCORRECT_QTY'));
			}
		}

		// Check if there is enough inventory (if tracking inventory) taking into account current quantity in the cart

		// Get the quantity already in the cart (if appending or simply syncing)
		if ($mode == 'add' || $mode == 'sync')
		{
			$skuCartInfo = $this->getCartItem($sId);
		}
		// If setting, ignore the current cart value
		else
		{
			$skuCartInfo = new stdClass();
			$skuCartInfo->crtiQty = 0;
		}

		// Get SKU pricing and inventory level & policies
		include_once(JPATH_BASE . DS . 'components' . DS . 'com_storefront' . DS . 'models' . DS . 'Warehouse.php');
		$warehouse = new StorefrontModelWarehouse();
		$allSkuInfo = $warehouse->getSkusInfo(array($sId));

		if (empty($allSkuInfo))
		{
			throw new Exception(Lang::txt('COM_STOREFRONT_SKU_NOT_FOUND'));
		}

		$skuInfo = $allSkuInfo[$sId]['info'];
		$skuName = $skuInfo->pName;
		if (!empty($allSkuInfo[$sId]['options']) && count($allSkuInfo[$sId]['options']))
		{
			foreach ($allSkuInfo[$sId]['options'] as $oName)
			{
				$skuName .= ', ' . $oName;
			}
		}

		// Check inventory rules (sync mode doesn't check inventory level, just pricing)
		if ($mode != 'sync')
		{
			// Don't allow purchasing multiple products (same & different SKUs) for those that are not allowed
			if (!$skuInfo->pAllowMultiple) {
				// Check this SKU qty to make sure no multiple SKUs are there
				if ((!empty($skuCartInfo->crtiQty) && $skuCartInfo->crtiQty > 0) || ($qty > 1))
				{
					throw new Exception($skuInfo->pName . Lang::txt('COM_CART_NO_MULTIPLE_ITEMS'));
				}
				// Check if there is this project already in the cart (different SKU)
				$allSkus = $warehouse->getProductSkus($skuInfo->pId);
				foreach ($allSkus as $skuId)
				{
					// Skip the current SKU, look only at other SKUs
					if ($skuId != $sId)
					{
						$otherSkuInfo = $this->getCartItem($skuId);
						// Error if there is already another SKU of the same product in the cart
						if (!empty($otherSkuInfo->crtiQty) && $otherSkuInfo->crtiQty > 0)
						{
							throw new Exception($skuInfo->pName . Lang::txt('COM_CART_NO_MULTIPLE_ITEMS'));
						}
					}

				}
			}
			// Don't allow purchasing multiple SKUs for those that are not allowed
			if (!$skuInfo->sAllowMultiple && ((!empty($skuCartInfo->crtiQty) && $skuCartInfo->crtiQty > 0) || ($qty > 1))) {
				throw new Exception($skuName . Lang::txt('COM_CART_NO_MULTIPLE_ITEMS'));
			}

			// Make sure there is enough inventory
			if ($skuInfo->sTrackInventory)
			{
				// See if qty can be added
				if ($qty > $skuInfo->sInventory)
				{
					throw new Exception(Lang::txt('COM_CART_NOT_ENOUGH_INVENTORY'));
				}
				elseif (!empty($skuCartInfo->crtiQty) && ($qty + $skuCartInfo->crtiQty > $skuInfo->sInventory))
				{
					// This is how much they can add: $skuInfo->sInventory - $skuCartInfo->crtiQty
					throw new Exception(Lang::txt('COM_CART_ADD_TOO_MANY_CART'));
				}
			}
		}

		// Run the auditor
		if ($mode != 'sync')
		{
			require_once(JPATH_BASE . DS . 'components' . DS . 'com_cart' . DS . 'helpers' . DS . 'Audit.php');
			$auditor = Audit::getAuditor($skuInfo, $this->crtId);
			$auditorResponse = $auditor->audit();

			if ($auditorResponse->status == 'error')
			{
				throw new Exception($skuInfo->pName . $auditor->getResponseError());
			}
		}

		// Insert new values, if exists save the previous price (for possible price changes messaging)
		// and old inventory level (if needed)
		$sql = "INSERT INTO `#__cart_cart_items`
				(`crtId`, `sId`, `crtiQty`, `crtiOldQty`, `crtiPrice`, `crtiOldPrice`, `crtiName`)
				VALUES
				({$this->crtId}, '{$sId}', {$qty}, NULL, {$skuInfo->sPrice}, NULL, " . $this->_db->quote($skuName) . ")
				ON DUPLICATE KEY UPDATE `crtiOldPrice` = `crtiPrice`, `crtiPrice` = {$skuInfo->sPrice}, `crtiName` = " . $this->_db->quote($skuName);

		// Check if old value has to be retained
		if ($retainOldValue)
		{
			$sql .= ", `crtiOldQty` = `crtiQty`";
		}
		else
		{
			$sql .= ", `crtiOldQty` = NULL";
		}

		// add to the existing qty value
		if ($mode == 'add')
		{
			$sql .= ", `crtiQty` = `crtiQty` + {$qty}";
		}
		// set new qty value
		elseif ($mode == 'set')
		{
			$sql .= ", `crtiQty` = {$qty}";
		}
		// keep the qty value if syncing

		$this->_db->setQuery($sql);
		$this->_db->query();
	}

	/**
	 * Delete SKU from cart
	 *
	 * @param SKU ID
	 * @return void
	 */
	private function deleteItem($sId)
	{
		// delete cart item
		$sql = "DELETE FROM `#__cart_cart_items` WHERE `sId` = '{$sId}' AND `crtId` = {$this->crtId}";

		$this->_db->setQuery($sql);
		$this->_db->query();
	}

	/********************************* Static functions *********************************/

	/**
	 * Verify security token
	 *
	 * @param 	string	string token
	 * @param   int     Transaction ID
	 * @return	bool
	 */
	public static function verifySecurityToken($token, $tId)
	{
		if (!Cart_Helper::isNonNegativeInt($tId, false))
		{
			throw new Exception(Lang::txt('COM_CART_NO_TRANSACTION_FOUND'));
		}
		return md5(self::$securitySalt . $tId) == $token;
	}

	/**
	 * Get user ID associated with the provided cart ID
	 * @param   int     $crtId cart ID
	 * @return  int     user ID, false if no cart found
	 */
	public static function getCartUser($crtId)
	{
		$db = JFactory::getDBO();

		$sql = 'SELECT `uidNumber` AS uId FROM `#__cart_carts` WHERE `crtId` = ' . $db->quote($crtId);
		$db->setQuery($sql);
		$uId = $db->loadResult();

		if (!empty($uId))
		{
			return $uId;
		}
		return false;
	}

	/**
	 * Remove given quantity of SKU from cart
	 *
	 * @param 	int SKU ID
	 * @param 	int Qty
	 * @return 	void
	 */
	protected static function removeItem($sId, $qty, $crtId)
	{
		$db = JFactory::getDBO();

		$sql = "UPDATE `#__cart_cart_items` SET `crtiQty` = `crtiQty` - {$qty} WHERE `sId` = '{$sId}' AND `crtId` = {$crtId}";
		$db->setQuery($sql);
		$db->query();
	}

	/**
	 * Delete cart and all cart items
	 *
	 * @param int Cart ID
	 * @return void
	 */
	protected static function kill($crtId)
	{
		$db = JFactory::getDBO();

		// delete cart items
		$sql = "DELETE FROM `#__cart_cart_items` WHERE `crtId` = {$crtId}";
		$db->setQuery($sql);
		$db->query();

		// delete cart coupons
		$sql = "DELETE FROM `#__cart_coupons` WHERE `crtId` = {$crtId}";
		$db->setQuery($sql);
		$db->query();

		// delete cart memberships
		$sql = "DELETE FROM `#__cart_memberships` WHERE `crtId` = {$crtId}";
		$db->setQuery($sql);
		$db->query();

		// delete the cart
		$sql = "DELETE FROM `#__cart_carts` WHERE `crtId` = {$crtId}";
		$db->setQuery($sql);
		$db->query();
	}

	/**
	 * Get main transaction facts (total, other verification info)
	 *
	 * @param void
	 * @return array of items in the transaction or false on failed attempt
	 */
	public static function getTransactionFacts($tId)
	{
		if (!is_numeric($tId))
		{
			return false;
		}

		$items = self::getTransactionItems($tId);
		if (!$items)
		{
			return false;
		}

		// Can be purchased -- get transaction items
		$transaction = new stdClass();
		$transaction->items = $items;

		$tInfo = self::getTransactionInfo($tId);

		// Calculate grand total
		$tInfo->tiTotalAmount = $tInfo->tiSubtotal + $tInfo->tiTax + $tInfo->tiShipping;
		$transaction->info = $tInfo;

		return $transaction;
	}

	/**
	 * Get all items in the transaction
	 *
	 * @param int transaction ID
	 * @return array of items in the transaction, false if no items in transaction
	 */
	protected static function getTransactionItems($tId)
	{
		$db = JFactory::getDBO();

		$sql = "SELECT `sId`, `tiQty`, `tiPrice` FROM `#__cart_transaction_items` ti WHERE ti.`tId` = {$tId}";
		$db->setQuery($sql);
		$db->query();

		if (!$db->getNumRows())
		{
			return false;
		}

		$allSkuInfo = $db->loadObjectList('sId');
		$skus = $db->loadResultArray();

		include_once(JPATH_BASE . DS . 'components' . DS . 'com_storefront' . DS . 'models' . DS . 'Warehouse.php');
		$warehouse = new StorefrontModelWarehouse();

		$skuInfo = $warehouse->getSkusInfo($skus);

		// Update skuInfo with transaction info
		foreach ($skuInfo as $sId => $sku)
		{
			$transactionInfo = new stdClass();
			$transactionInfo->qty = $allSkuInfo[$sId]->tiQty;
			$transactionInfo->tiPrice = $allSkuInfo[$sId]->tiPrice;
			$skuInfo[$sId]['transactionInfo'] = $transactionInfo;
			unset($transactionInfo);
		}

		if (empty($skuInfo))
		{
			return false;
		}

		return $skuInfo;
	}

	/**
	 * Gets all transaction related info
	 *
	 * @param   int     Transaction ID
	 * @return  object, false on no results
	 */
	protected static function getTransactionInfo($tId)
	{
		$db = JFactory::getDBO();

		// Get info
		$sql = 'SELECT t.*, TIMESTAMPDIFF(MINUTE, t.`tLastUpdated`, NOW()) AS tAge, ti.*
				FROM `#__cart_transactions` t LEFT JOIN `#__cart_transaction_info` ti ON t.`tId` = ti.`tId`
				WHERE t.`tId` = ' . $db->quote($tId);
		$db->setQuery($sql);
		$db->query();

		if (!$db->getNumRows())
		{
			return false;
		}

		$transactionInfo = $db->loadObject();
		return $transactionInfo;
	}

	/**
	 * Complete the transaction, mark it as completed, done, success...
	 *
	 * @param	object Transaction info
	 * @return 	void
	 */
	public static function completeTransaction($tInfo)
	{
		$tId = $tInfo->info->tId;
		$crtId = $tInfo->info->crtId;

		// Extract transaction items
		$transactionItems = unserialize($tInfo->info->tiItems);

		require_once(JPATH_ROOT . DS . 'components' . DS . 'com_cart' . DS . 'helpers' . DS . 'ProductHandler.php');

		// Handle each item in the transaction
		foreach ($transactionItems as $sId => $item)
		{
			$productHandler = new Cart_ProductHandler($item, $crtId);
			$productHandler->handle();
		}

		// Mark transaction as completed
		self::updateTransactionStatus('completed', $tId);

		// Remove items from cart
		self::removeTransactionItemsFromCart($tInfo);

		// remove coupons from cart
		self::removeTransactionCouponsFromCart($tInfo);

		/* Clean up cart */
		$db = JFactory::getDBO();

		// Delete zero and negative qty items in the cart
		$sql = "DELETE FROM `#__cart_cart_items` WHERE `crtiQty` <= 0 AND `crtId` = {$tInfo->info->crtId}";
		$db->setQuery($sql);
		$db->query();
	}

	/**
	 * Update transaction status
	 *
	 * @param   string  status
	 * @param   int     Transaction ID
	 * @return  bool    Success or failure
	 */
	public static function updateTransactionStatus($status, $tId)
	{
		$db = JFactory::getDBO();

		$sql = "UPDATE `#__cart_transactions` SET `tStatus` = '{$status}' WHERE `tId` = {$tId}";
		$db->setQuery($sql);
		$db->query();

		$affectedRows = $db->getAffectedRows();

		if (!$affectedRows) {
			return false;
		}
		return true;
	}

	/**
	 * Remove transaction items from the cart associated with it
	 *
	 * @param	object transaction info
	 * @return 	void
	 */
	private static function removeTransactionItemsFromCart($tInfo)
	{
		// remove each item from the cart
		foreach ($tInfo->items as $sId => $item)
		{
			self::removeItem($sId, $item['transactionInfo']->qty, $tInfo->info->crtId);
		}
	}

	/**
	 * Remove transaction coupons from the cart associated with it
	 *
	 * @param	object transaction info
	 * @return 	void
	 */
	private static function removeTransactionCouponsFromCart($tInfo)
	{
		$perks = $tInfo->info->tiPerks;

		if (empty($perks))
		{
			return true;
		}

		$perks = unserialize($perks);

		// remove each coupon from the cart
		$couponIds = array();
		foreach ($perks as $k => $val)
		{
			if ($k != 'info')
			{
				if (is_array($val))
				{
					foreach ($val as $coupon)
					{
						$couponIds[] = $coupon->couponId;
					}
				}
				else
				{
					$couponIds[] = $val->couponId;
				}
			}
		}

		$db = JFactory::getDBO();

		$sqlCoupons = '0';
		foreach ($couponIds as $cnId)
		{
			$sqlCoupons .= ' OR `cnId` = ' . $db->quote($cnId);
		}

		$sql = "UPDATE `#__cart_coupons` SET `crtCnStatus` = 'applied'
				WHERE ({$sqlCoupons}) AND `crtId` = {$tInfo->info->crtId}";
		$db->setQuery($sql);
		$db->query();
	}

	/**
	 * Handle the error processing the transaction
	 *
	 * @param	int transaction ID
	 * @param 	object error
	 * @return	void
	 */
	public static function handleTransactionError($tId, $error)
	{
		// Release transaction items back to inventory
		self::releaseTransaction($tId);

		// Update status to 'error processing'
		self::updateTransactionStatus('error processing', $tId);
	}

	/**
	 * Releases locked transaction items back to inventory and marks the transaction status as 'released'
	 *
	 * @param int Transaction ID
	 * @return void
	 */
	public static function releaseTransaction($tId)
	{
		$db = JFactory::getDBO();

		// Check if the transaction can be released (status is pending)
		// Get info
		$sql = "SELECT t.`tStatus` FROM `#__cart_transactions` t WHERE t.`tId` = {$tId}";
		$db->setQuery($sql);
		$db->query();

		if (!$db->getNumRows())
		{
			return false;
		}

		// Get transaction items
		$tItems = self::getTransactionItems($tId);

		/* Go through each item and return the quantity back to inventory if needed */
		require_once(JPATH_BASE . DS . 'components' . DS . 'com_storefront' . DS . 'models' . DS . 'Warehouse.php');
		$warehouse = new StorefrontModelWarehouse();

		if (!empty($tItems))
		{
			foreach ($tItems as $sId => $itemInfo)
			{
				$qty = $itemInfo['transactionInfo']->qty;
				$warehouse->updateInventory($sId, $qty, 'add');
			}
		}
		// update status
		self::updateTransactionStatus('released', $tId);
	}

	/**
	 * Kill transaction
	 *
	 * @param int tId transaction ID to kill
	 * @return void
	 */
	protected static function killTransaction($tId)
	{
		$db = JFactory::getDBO();

		$sql = "DELETE FROM `#__cart_transactions` WHERE `tId` = {$tId}";
		$db->setQuery($sql);
		$db->query();

		$sql = "DELETE FROM `#__cart_transaction_items` WHERE `tId` = {$tId}";
		$db->setQuery($sql);
		$db->query();

		$sql = "DELETE FROM `#__cart_transaction_info` WHERE `tId` = {$tId}";
		$db->setQuery($sql);
		$db->query();

		$sql = "DELETE FROM `#__cart_transaction_steps` WHERE `tId` = {$tId}";
		$db->setQuery($sql);
		$db->query();
	}
}