<?php
/**
 * @package		HUBzero CMS
 * @author		Shawn Rice <zooley@purdue.edu>
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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );
?>
<div class="sidebox">
		<h4 class="notes"><a href="<?php echo Route::url('index.php?option=' . $this->option . '&alias=' . $this->project->alias . '&active=notes'); ?>" class="hlink" title="<?php echo Lang::txt('COM_PROJECTS_VIEW') . ' ' . strtolower(Lang::txt('COM_PROJECTS_PROJECT')) . ' ' . strtolower(Lang::txt('COM_PROJECTS_TAB_NOTES')); ?>"><?php echo ucfirst(Lang::txt('COM_PROJECTS_TAB_NOTES')); ?></a>
<?php if (count($this->items) > 0) { ?>
	<span><a href="<?php echo Route::url('index.php?option=' . $this->option . '&alias=' . $this->project->alias . '&active=notes'); ?>"><?php echo ucfirst(Lang::txt('COM_PROJECTS_SEE_ALL')); ?> </a></span>
<?php } ?>
</h4>
<?php if (count($this->items) == 0) { ?>
	<div class="noitems">
		<p><?php echo Lang::txt('COM_PROJECTS_NO_NOTES'); ?></p>
		<p class="addnew"><a href="<?php echo Route::url('index.php?option=' . $this->option . '&alias=' . $this->project->alias . '&active=notes') . '?action=new'; ?>"><?php echo Lang::txt('COM_PROJECTS_ADD_NOTE'); ?></a></p>
	</div>
<?php } else { ?>
	<ul>
		<?php foreach ($this->items as $note) {
		?>
	<li>
		 <a href="<?php echo Route::url('index.php?option=' . $this->option . '&alias=' . $this->project->alias . '&active=notes&scope='.$note->scope . '&pagename=' . $note->pagename); ?>" title="<?php echo htmlentities($note->title); ?>">
		<?php echo \Hubzero\Utility\String::truncate($note->title, 35); ?></a>
	   </li><?php } ?>
	</ul><?php } ?>
</div>