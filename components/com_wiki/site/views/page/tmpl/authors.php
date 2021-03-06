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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

if ($this->page->param('mode', 'wiki') == 'knol' && !$this->page->param('hide_authors', 0))
{
	$author = ($this->page->creator('name') ? $this->escape(stripslashes($this->page->creator('name'))) : Lang::txt('COM_WIKI_UNKNOWN'));

	$auths = array();
	$auths[] = ($this->page->creator('public') ? '<a href="' . Route::url($this->page->creator()->getLink()) . '">' . $author . '</a>' : $author);
	foreach ($this->page->authors() as $auth)
	{
		if ($auth->get('user_id') == $this->page->get('created_by'))
		{
			continue;
		}

		$name = $this->escape(stripslashes($auth->get('name')));

		$xprofile = \Hubzero\User\Profile::getInstance($auth->get('user_id'));
		if (is_object($xprofile))
		{
			$name = ($xprofile->get('public') ? '<a href="' . Route::url($xprofile->getLink()) . '">' . $name . '</a>' : $name);
		}

		$auths[] = $name;
	}
	?>
	<p class="topic-authors"><?php echo Lang::txt('COM_WIKI_BY_AUTHORS', implode(', ', $auths)); ?></p>
	<?php
}
