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
 * @author    Alissa Nedossekina <alisa@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

?>
<div<?php echo ($this->params->get('moduleclass')) ? ' class="' . $this->params->get('moduleclass') . '"' : ''; ?>>
	<?php if ($this->params->get('button_show_add', 1)) { ?>
	<ul class="module-nav">
		<li>
			<a class="icon-plus" href="<?php echo Route::url('index.php?option=com_wishlist&task=add&category=general&rid=1'); ?>">
				<?php echo Lang::txt('MOD_MYWISHES_NEW_WISH'); ?>
			</a>
		</li>
	</ul>
	<?php } ?>

	<h4 class="wish-category"><?php echo Lang::txt('MOD_MYWISHES_SUBMITTED'); ?></h4>
	<?php if (count($this->rows1) <= 0) { ?>
		<p><em><?php echo Lang::txt('MOD_MYWISHES_NO_WISHES'); ?></em></p>
	<?php } else { ?>
		<ul class="expandedlist">
			<?php
			foreach ($this->rows1 as $row)
			{
				$when = JHTML::_('date.relative', $row->proposed);
				$title = strip_tags($row->about) ? $this->escape(stripslashes($row->subject)) . ' :: ' . \Hubzero\Utility\String::truncate($this->escape(strip_tags($row->about)), 160) : NULL;
			?>
			<li class="wishlist">
				<a href="<?php echo Route::url('index.php?option=com_wishlist&task=wish&id=' . $row->wishlist . '&wishid=' . $row->id); ?>" class="tooltips" title="<?php echo $title; ?>">
					#<?php echo $row->id; ?>: <?php echo \Hubzero\Utility\String::truncate(stripslashes($row->subject), 35); ?>
				</a>
				<span>
					<span class="<?php
					echo ($row->status==3) ? 'rejected' : '';
					if ($row->status==0) {
						echo ($row->accepted==1) ? 'accepted' : 'pending';
					}
					?>">
						<?php
						echo ($row->status==3) ? Lang::txt('MOD_MYWISHES_REJECTED') : '';
						if ($row->status==0) {
							echo ($row->accepted==1) ? Lang::txt('MOD_MYWISHES_ACCEPTED') : Lang::txt('MOD_MYWISHES_PENDING');
						}
						?>
					</span>
					<span>
						<?php echo Lang::txt('MOD_MYWISHES_WISHLIST') . ': ' . $this->escape(stripslashes($row->listtitle)); ?>
					</span>
				</span>
			</li>
			<?php
			}
			?>
		</ul>
	<?php } ?>

	<h4 class="wish-category"><?php echo Lang::txt('MOD_MYWISHES_ASSIGNED'); ?></h4>
	<?php if (count($this->rows2) <= 0) { ?>
		<p><?php echo Lang::txt('MOD_MYWISHES_NO_WISHES'); ?></p>
	<?php } else { ?>
		<ul class="expandedlist">
			<?php
			foreach ($this->rows2 as $row)
			{
				$when = JHTML::_('date.relative', $row->proposed);
				$title = strip_tags($row->about) ? $this->escape(stripslashes($row->subject)) . ' :: ' . \Hubzero\Utility\String::truncate($this->escape(strip_tags($row->about)), 160) : NULL;
			?>
			<li class="wishlist">
				<a href="<?php echo Route::url('index.php?option=com_wishlist&task=wish&id=' . $row->wishlist . '&wishid=' . $row->id); ?>" class="tooltips" title="<?php echo $title; ?>">
					#<?php echo $row->id; ?>: <?php echo \Hubzero\Utility\String::truncate(stripslashes($row->subject), 35); ?>
				</a>
				<span>
					<span class="<?php
					echo ($row->status==3) ? 'rejected' : '';
					if ($row->status==0) {
						echo ($row->accepted==1) ? 'accepted' : 'pending';
					}
					?>">
						<?php
						echo ($row->status==3) ? Lang::txt('MOD_MYWISHES_REJECTED') : '';
						if ($row->status==0) {
							echo ($row->accepted==1) ? Lang::txt('MOD_MYWISHES_ACCEPTED') : Lang::txt('MOD_MYWISHES_PENDING');
						}
						?>
					</span>
					<span>
						<?php echo Lang::txt('MOD_MYWISHES_WISHLIST') . ': ' . $this->escape(stripslashes($row->listtitle)); ?>
					</span>
				</span>
			</li>
			<?php
			}
			?>
		</ul>
	<?php } ?>
</div>