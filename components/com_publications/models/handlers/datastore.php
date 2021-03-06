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

namespace Components\Publications\Models\Handlers;

use Components\Publications\Models\Handler as Base;
use stdClass;

/**
 * DataStore Lite Handler
 */
class DataStore extends Base
{
	/**
	* Handler type name
	*
	* @var		string
	*/
	protected	$_name 		= 'datastore';

	/**
	* Configs
	*
	* @var
	*/
	protected	$_config 	= NULL;

	/**
	 * Get default params for the handler
	 *
	 * @return  void
	 */
	public function getConfig($savedConfig = array())
	{
		// Defaults
		$configs = array(
			'name' 			=> 'datastore',
			'label' 		=> 'Data Viewer',
			'title' 		=> 'Interactive data explorer',
			'about'			=> 'Selected CSV file will be viewed as a database',
			'params'	=> array(
				'allowed_ext' 		=> array('csv'),
				'required_ext' 		=> array('csv'),
				'min_allowed' 		=> 1,
				'max_allowed' 		=> 1,
				'enforced'			=> 0
			)
		);

		$this->_config = json_decode(json_encode($this->_parent->parseConfig($this->_name, $configs, $savedConfig)), FALSE);
		return $this->_config;
	}

	/**
	 * Clean-up related files
	 *
	 * @return  void
	 */
	public function cleanup( $path )
	{
		// Make sure we got config
		if (!$this->_config)
		{
			$this->getConfig();
		}

		return true;
	}

	/**
	 * Draw list of included items
	 *
	 * @return  void
	 */
	public function drawList($attachments, $attConfigs, $pub, $authorized )
	{
		// No special treatment for this handler
		return;
	}

	/**
	 * Draw attachment
	 *
	 * @return  void
	 */
	public function drawAttachment($data, $params)
	{
		// No special treatment for this handler
		return;
	}

	/**
	 * Check for changed selections etc
	 *
	 * @return  object
	 */
	public function getStatus( $attachments )
	{
		// Start status
		$status = new \Components\Publications\Models\Status();
		return $status;
	}

	/**
	 * Draw handler status in editor
	 *
	 * @return  object
	 */
	public function drawStatus($editor)
	{
		return;
	}

	/**
	 * Draw handler editor content
	 *
	 * @return  object
	 */
	public function drawEditor($editor)
	{
		return;
	}

	/**
	 * Check against handler-specific requirements
	 *
	 * @return  object
	 */
	public function checkRequired( $attachments )
	{
		return true;
	}
}