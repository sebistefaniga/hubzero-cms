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

namespace Hubzero\Document\Asset;

/**
 * Image asset class
 */
class Image extends File
{
	/**
	 * Asset type
	 *
	 * @var  string
	 */
	protected $type = 'img';

	/**
	 * Allowed file extensions
	 *
	 * @var  string
	 */
	private $handles = array('png', 'gif', 'jpg', 'jpeg', 'jpe');

	/**
	 * File extension
	 *
	 * @var  string
	 */
	private $ext = '';

	/**
	 * Constructor
	 *
	 * @param   string  $extension  CMS Extension to load asset from
	 * @param   string  $name       Asset name (optional)
	 * @return  void
	 */
	public function __construct($extension, $name=null)
	{
		parent::__construct($extension, $name);

		// Preserve the original file extension
		jimport('joomla.filesystem.file');
		$this->ext = strtolower(\JFile::getExt($name));
	}

	/**
	 * Get the file name
	 *
	 * @return  string
	 */
	public function file()
	{
		return $this->name . '.' . $this->ext;
	}
}
