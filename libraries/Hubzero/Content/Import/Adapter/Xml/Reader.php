<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2014 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2014 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Hubzero\Content\Import\Adapter\Xml;

use Iterator;
use XMLReader;
use DOMDocument;

/**
 *  XML Reader Iterator Class implemeting interator
 */
class Reader implements Iterator
{
	private $file;
	private $key;
	private $reader;
	private $position;

	/**
	 * XML Reader Iterator Constructor
	 *
	 * @param string $file XML file we want to use
	 * @param string $key  XML node we are looking to iterate over
	 */
	public function __construct($file, $key)
	{
		$this->reader   = new XMLReader();
		$this->position = 0;
		$this->file     = $file;
		$this->key      = $key;
	}

	/**
	 * Get the current XML node
	 *
	 * @return object XML node as a stdClass
	 */
	public function current()
	{
		$doc = new DOMDocument();
		$object = simplexml_import_dom($doc->importNode($this->reader->expand(), true));
		return json_decode(json_encode($object));
	}

	/**
	 * Get our current position while iterating
	 *
	 * @return int Current position
	 */
	public function key()
	{
		return $this->position;
	}

	/**
	 * Go to the next Node that matches our key
	 *
	 * @return void
	 */
	public function next()
	{
		if ($this->reader->next($this->key))
		{
			++$this->position;
		}
	}

	/**
	 * Move to the first node that matches our key
	 * @return void
	 */
	public function rewind()
	{
		// open file with reader
		// force UTF-8, validate XML, & substitute entities while reading
		$this->reader->open($this->file, 'UTF-8', XMLReader::VALIDATE | XMLReader::SUBST_ENTITIES);

		// fast forward to first record
		while ($this->reader->read() && $this->reader->name !== $this->key);
	}

	/**
	 * Is our current node valid
	 *
	 * @return bool Is valid?
	 */
	public function valid()
	{
		return $this->reader->name === $this->key;
	}
}