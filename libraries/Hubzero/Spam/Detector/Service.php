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

namespace Hubzero\Spam\Detector;

use Hubzero\Base\Object;

/**
 * Abstract spam detector service
 */
abstract class Service extends Object implements DetectorInterface
{
	/**
	 * The value to be validated
	 *
	 * @var mixed
	 */
	protected $_value;

	/**
	 * Returns the validation value
	 *
	 * @return mixed Value to be validated
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * Sets the value to be validated and clears the errors arrays
	 *
	 * @param  mixed $value
	 * @return void
	 */
	public function setValue($value)
	{
		$this->_value  = $value;
		$this->_errors = array();
	}

	/**
	 * Tests for spam.
	 *
	 * @param   string  $data  Content to test
	 * @return  bool    True if the comment is spam, false if not
	 */
	public function detect($data = null) 
	{
		$data = $data ?: $this->getValue();

		return false;
	}

	/**
	 * Train the service
	 *
	 * @param   string   $data
	 * @param   boolean  $isSpam
	 * @return  boolean
	 */
	public function learn($data, $isSpam)
	{
		$data = $data ?: $this->getValue();

		if (!$data)
		{
			return false;
		}

		return true;
	}

	/**
	 * Forget a trained value
	 *
	 * @param   string   $data
	 * @param   boolean  $isSpam
	 * @return  boolean
	 */
	public function forget($data, $isSpam)
	{
		$data = $data ?: $this->getValue();

		return true;
	}
}
