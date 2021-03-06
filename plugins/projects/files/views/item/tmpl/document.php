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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$file 	= $this->item;
$me 	= ($file['email'] == User::get('email') || $file['author'] == User::get('name'))  ? 1 : 0;
$c 		= $this->c;

$when 	= $file['date'] ? \Components\Projects\Helpers\Html::formatTime($file['date']) : 'N/A';

// LaTeX?
$tex = Components\Projects\Helpers\Compiler::isTexFile(basename($file['name']));

?>
	<tr class="mini faded mline">
		<?php if ($this->model->access('content')) { ?>
		<td>
			<?php if ($file['untracked'] == 0) { ?>
			<input type="checkbox" value="<?php echo urlencode($file['name']); ?>" name="asset[]" class="checkasset js <?php if ($this->publishing && $file['pid']) { echo 'publ'; } ?>" />
			<?php } ?>
		</td>
		<?php } ?>
		<td class="top_valign nobsp">
			<img src="<?php echo \Components\Projects\Helpers\Html::getFileIcon($file['ext']); ?>" alt="<?php echo $file['ext']; ?>" />
			<a href="<?php echo $this->url
			. '/?action=download&amp;subdir='.urlencode($this->subdir)
			. '&amp;file='.urlencode($file['name']); ?>"
			<?php if ($file['untracked'] == 0) { ?>
			class="preview file:<?php echo urlencode($file['name']); ?>" <?php } ?> id="edit-c-<?php echo $c; ?>">
			<?php echo \Components\Projects\Helpers\Html::shortenFileName($file['name'], 50); ?></a>

			<?php if ($file['untracked'] == 0) { ?>
				<?php if ($this->model->access('content')) { ?>
			<span id="rename-c-<?php echo $c; ?>" class="rename js" title="<?php echo Lang::txt('PLG_PROJECTS_FILES_RENAME_FILE_TOOLTIP'); ?>">&nbsp;</span><?php } ?>
			<?php } else { ?>
				<span class="fileoptions"><?php echo Lang::txt('PLG_PROJECTS_FILES_UNTRACKED'); ?></span>
			<?php } ?>
		</td>
		<td class="shrinked"></td>
		<td class="shrinked"><?php echo $file['size']; ?></td>
		<td class="shrinked"><?php if ($file['untracked'] == 0) { ?>
		<a href="<?php echo $this->url . '/?action=history&amp;subdir=' . urlencode($this->subdir) . '&amp;asset=' . urlencode($file['name']); ?>" title="<?php echo Lang::txt('PLG_PROJECTS_FILES_HISTORY_TOOLTIP'); ?>"><?php echo $when; ?></a>
		<?php } ?></td>
		<td class="shrinked pale"><?php if ($me) { echo Lang::txt('PLG_PROJECTS_FILES_ME'); } else { echo $file['author']; } ?>
		</td>
		<td class="shrinked nojs">
			<?php if ($this->model->access('content')) { ?>
			<a href="<?php echo $this->url . '/?action=delete' . '&amp;subdir=' . urlencode($this->subdir)
		. '&amp;asset=' . urlencode($file['name']); ?>"
		 title="<?php echo Lang::txt('PLG_PROJECTS_FILES_DELETE_TOOLTIP'); ?>" class="i-delete">&nbsp;</a>
		<a href="<?php echo $this->url . '/?action=move&amp;subdir=' . urlencode($this->subdir)
		. '&amp;asset=' . urlencode($file['name']); ?>"
		 title="<?php echo Lang::txt('PLG_PROJECTS_FILES_MOVE_TOOLTIP'); ?>" class="i-move">&nbsp;</a>
		<?php } ?>
		</td>
		<?php if ($this->publishing) { ?>
		<td class="shrinked"><?php if ($file['pid'] && $file['pub_title']) { ?><a href="<?php echo Route::url('index.php?option=' . $this->option . '&active=publications&alias=' . $this->model->get('alias') . '&pid=' . $file['pid']) . '?section=content'; ?>" title="<?php echo $file['pub_title'] . ' (v.' . $file['pub_version_label'] . ')' ; ?>" class="asset_resource"><?php echo \Hubzero\Utility\String::truncate($file['pub_title'], 20); ?></a><?php } ?></td>
		<?php } ?>
	</tr>