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
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$base = $this->member->getLink() . '&active=' . $this->name;
?>
	<nav>
		<ul class="sub-menu">
			<?php if ($this->params->get('access-manage-collection')) { ?>
				<li<?php if ($this->active == 'livefeed') { echo ' class="active"'; } ?>>
					<a class="livefeed tooltips" href="<?php echo Route::url($base); ?>" title="<?php echo Lang::txt('PLG_MEMBERS_COLLECTIONS_FEED_TITLE'); ?>">
						<span><?php echo Lang::txt('PLG_MEMBERS_COLLECTIONS_FEED'); ?></span>
					</a>
				</li>
			<?php } ?>
			<li<?php if ($this->active == 'collections') { echo ' class="active"'; } ?>>
				<a class="collections count" href="<?php echo Route::url($base . '&task=all'); ?>">
					<span><?php echo Lang::txt('PLG_MEMBERS_COLLECTIONS_HEADER_NUM_COLLECTIONS', $this->collections); ?></span>
				</a>
			</li>
			<li<?php if ($this->active == 'posts') { echo ' class="active"'; } ?>>
				<a class="posts count" href="<?php echo Route::url($base . '&task=posts'); ?>">
					<span><?php echo Lang::txt('PLG_MEMBERS_COLLECTIONS_HEADER_NUM_POSTS', $this->posts); ?></span>
				</a>
			</li>
			<li<?php if ($this->active == 'followers') { echo ' class="active"'; } ?>>
				<a class="followers count" href="<?php echo Route::url($base . '&task=followers'); ?>">
					<span><?php echo Lang::txt('PLG_MEMBERS_COLLECTIONS_HEADER_NUM_FOLLOWERS', $this->followers); ?></span>
				</a>
			</li>
			<li<?php if ($this->active == 'following') { echo ' class="active"'; } ?>>
				<a class="following count" href="<?php echo Route::url($base . '&task=following'); ?>">
					<span><?php echo Lang::txt('PLG_MEMBERS_COLLECTIONS_HEADER_NUM_FOLLOWNG', $this->following); ?></span>
				</a>
			</li>
		</ul>
	</nav>