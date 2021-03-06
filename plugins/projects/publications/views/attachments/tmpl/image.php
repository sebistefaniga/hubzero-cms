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

$data  		 = $this->data;
$allowRename = $this->data->allowRename;

// Get settings
$suffix = isset($this->config->params->thumbSuffix) && $this->config->params->thumbSuffix
		? $this->config->params->thumbSuffix : '-tn';

$format = isset($this->config->params->thumbFormat) && $this->config->params->thumbFormat
		? $this->config->params->thumbFormat : 'png';

$dirHierarchy = isset($this->params->dirHierarchy) ? $this->params->dirHierarchy : 1;

if ($dirHierarchy == 1)
{
	$file = $this->data->path;
}
elseif ($dirHierarchy == 2)
{
	// Get file attachment params
	$file 	= isset($this->data->suffix) && $this->data->suffix  ? \Components\Projects\Helpers\Html::fixFileName(basename($data->path), ' (' . $this->data->suffix . ')') : basename($data->path);
}
else
{
	$file 	= \Components\Projects\Helpers\Html::fixFileName(basename($data->path), '-' . $data->id);
}

$thumbName = \Components\Projects\Helpers\Html::createThumbName($file, $suffix, $format);

$filePath = Route::url('index.php?option=com_publications&id=' . $data->pid . '&v=' . $data->vid) . '/Image:' . $file;
$thumbSrc = Route::url('index.php?option=com_publications&id=' . $data->pid . '&v=' . $data->vid) . '/Image:' . $thumbName;

// Is this image used for publication thumbail?
$class = $data->pubThumb == 1 ? ' starred' : '';
$over  = $data->pubThumb == 1 ? ' title="' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_IMAGE_DEFAULT') . '"' : '';

$viewer = $this->data->viewer;

if ($viewer == 'freeze')
{
	$title 	 = $data->title;
	$details = $data->path;
	$details.= $data->size ? ' | ' . \Hubzero\Utility\Number::formatBytes($data->size) : '';
}
else
{
	$title 	 = $data->title;
	$details = $data->path;
	$details.= $data->size ? ' | ' . \Hubzero\Utility\Number::formatBytes($data->size) : '';
	$details.= $data->gitStatus ? ' | ' . $data->gitStatus : '';
}

?>
	<li class="image-container">
		<span class="item-options">
			<?php if ($viewer == 'edit') { ?>
			<span>
				<?php if (!$data->pubThumb) { ?>
				<a href="<?php echo $data->editUrl . '/?action=saveitem&aid=' . $data->id . '&p=' . $data->props . '&makedefault=1&version=' . $data->version; ?>" class="item-default" title="<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_IMAGE_MAKE_DEFAULT'); ?>">&nbsp;</a>
				<?php } ?>
				<a href="<?php echo $data->editUrl . '/?action=edititem&aid=' . $data->id . '&p=' . $data->props; ?>" class="showinbox item-edit" title="<?php echo ($data->gone || $allowRename == false) ? Lang::txt('PLG_PROJECTS_PUBLICATIONS_RELABEL') : Lang::txt('PLG_PROJECTS_PUBLICATIONS_RENAME'); ?>">&nbsp;</a>
				<a href="<?php echo $data->editUrl . '/?action=deleteitem&version=' . $data->version . '&aid=' . $data->id . '&p=' . $data->props; ?>" class="item-remove" title="<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_REMOVE'); ?>">&nbsp;</a>
			</span>
			<?php } ?>
		</span>
		<span class="item-image<?php echo $class; ?>" <?php echo $over; ?>><a class="more-content" href="<?php echo $filePath; ?>"><img alt="" src="<?php echo $thumbSrc; ?>" /></a></span>
		<span class="item-title">
			<?php echo $title; ?></span>
		<span class="item-details"><?php echo $details; ?></span>
	</li>