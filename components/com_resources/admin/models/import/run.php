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
 * @author    Christopher Smoak <csmoak@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Resources\Models\Import;

use Hubzero\Base\Model;

require_once PATH_CORE . DS . 'components' . DS . 'com_resources' . DS . 'tables' . DS . 'import' . DS . 'run.php';

/**
 * Import Runs Model
 */
class Run extends Model
{
	/**
	 * JTable
	 *
	 * @var  string
	 */
	protected $_tbl = null;

	/**
	 * Table name
	 *
	 * @var  string
	 */
	protected $_tbl_name = '\\Components\\Resources\\Tables\\Import\\Run';

	/**
	 * Constructor
	 *
	 * @param   mixed  $oid Object Id
	 * @return  void
	 */
	public function __construct($oid = null)
	{
		// create needed objects
		$this->_db = \JFactory::getDBO();

		// load page jtable
		$this->_tbl = new $this->_tbl_name($this->_db);

		// load object
		if (is_numeric($oid))
		{
			$this->_tbl->load($oid);
		}
		else if (is_object($oid) || is_array($oid))
		{
			$this->bind($oid);
		}
	}

	/**
	 * Add to the processed number on this run
	 * @param   integer  $number  Number to increpemnt by
	 * @return  void
	 */
	public function processed($number = 1)
	{
		$this->set('processed', $this->get('processed') + $number);
		$this->store();
	}
}