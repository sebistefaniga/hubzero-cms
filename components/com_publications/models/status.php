<?php
/**
 * @package		HUBzero CMS
 * @author		Alissa Nedossekina <alisa@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace Components\Publications\Models;

use Hubzero\Base\Object;

/**
 * Publication status model class
 */
class Status extends Object
{
	/**
	 *  Int(1) 
	 * 1 = requirement satisfied
	 * 0 = requirement not satisfied
	 * 2 = requirement partially satisfied (incomplete)
	 * 3 = not available
	 */
	var $status						= NULL;
		
	/**
	 * Status message
	 */
	var $message					= NULL;
		
	/**
	 * For nested blocks
	 */
	var $elements					= NULL;
	
	/**
	 * Time of last status update
	 */
	var $lastupdate					= NULL;
	
}