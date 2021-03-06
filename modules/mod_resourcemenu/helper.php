<?php
/**
 * HUBzero CMS
 *
 * Copyright 2009-2015 Purdue University. All rights reserved.
 * All rights reserved.
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
 * @copyright Copyright 2009-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Modules\ResourceMenu;

use Hubzero\Module\Module;
use stdClass;
use Event;

/**
 * Module class for displaying a megamenu
 */
class Helper extends Module
{
	/**
	 * Display module content
	 *
	 * @return  void
	 */
	public function display()
	{
		$this->moduleid    = $this->params->get('moduleid');
		$this->moduleclass = $this->params->get('moduleclass');

		// Build the HTML
		$obj = new stdClass;
		$obj->text = $this->params->get('content');

		// Get the search result totals
		$results = Event::trigger(
			'content.onPrepareContent',
			array(
				'',
				$obj,
				$this->params
			)
		);

		$this->html = $obj->text;

		// Push some CSS to the tmeplate
		$this->css()
		     ->js();

		require $this->getLayoutPath();
	}
}
