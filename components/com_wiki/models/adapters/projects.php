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

namespace Components\Wiki\Models\Adapters;

use Request;
use Route;

require_once(__DIR__ . DS . 'base.php');

/**
 * Adapter class for a project note
 */
class Projects extends Base
{
	/**
	 * URL segments
	 *
	 * @var string
	 */
	protected $_segments = array(
		'option' => 'com_projects',
	);

	/**
	 * Generate and return various links to the entry
	 * Link will vary depending upon action desired, such as edit, delete, etc.
	 *
	 * @param   string  $type    The type of link to return
	 * @param   mixed   $params  Optional string or associative array of params to append
	 * @return  string
	 */
	public function link($type='', $params=null)
	{
		$segments = $this->_segments;
		$project = Request::getVar('project', NULL);
		$segments['scope'] = Request::getVar('scope', NULL);
		if (!$segments['scope'] && is_object($project))
		{
			$segments['scope'] = 'projects/' . $project->alias . '/notes';
		}
		$anchor = '';

		// If it doesn't exist or isn't published
		switch (strtolower($type))
		{
			case 'base':
				unset($segments['pagename']);
				return $this->_base . '?' . (string) $this->_build($segments);
			break;

			case 'pdf':
			case 'new':
			case 'rename':
			case 'edit':
			case 'delete':
			case 'history':
			case 'compare':
			case 'approve':
			case 'comments':
			case 'deleterevision':
			case 'addcomment':
				$segments['task'] = $type;
			break;

			case 'permalink':
			default:

			break;
		}

		if (is_string($params))
		{
			$params = str_replace('&amp;', '&', $params);

			if (substr($params, 0, 1) == '#')
			{
				$anchor = $params;
			}
			else
			{
				if (substr($params, 0, 1) == '?')
				{
					$params = substr($params, 1);
				}
				parse_str($params, $parsed);
				$params = $parsed;
			}
		}

		$segments = array_merge($segments, (array) $params);

		return Route::url($this->_base . '?' . (string) $this->_build($segments) . (string) $anchor) . '?t=1';
	}
}
