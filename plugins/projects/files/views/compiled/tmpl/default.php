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

$subdirlink = $this->subdir ? '&amp;subdir=' . urlencode($this->subdir) : '';

?>
<div id="abox-content">
<?php
// Display error or success message
if ($this->getError()) { ?>
	<h3><?php echo Lang::txt('PLG_PROJECTS_FILES_COMPILED_PREVIEW'); ?></h3>
	<?php
	echo ('<p class="witherror">'.$this->getError().'</p>');

	echo '<div class="witherror"><pre>';
	if ($this->log)
	{
		echo $this->log;
	}
	echo '</pre></div>';
}
?>
<?php
if (!$this->getError()) {
?>
<ul class="sample">
	<?php
		// Display list item with file data
		$view = $this->view('default', 'selected');
		$view->skip 		= false;
		$view->item 		= $this->item;
		$view->remote		= $this->remote;
		$view->type			= 'file';
		$view->action		= 'compile';
		$view->multi		= NULL;

		if ($this->ext == 'tex' && is_file(PATH_APP . $this->outputDir . DS . $this->embed))
		{
			$view->extras  = '<span class="rightfloat">';
			$view->extras .= '<a href="' . $this->url . '/?action=compile' . $subdirlink . '&amp;download=1&amp;file=' . $this->item . '" class="i-download">' . Lang::txt('PLG_PROJECTS_FILES_DOWNLOAD') . ' PDF</a> ';
			$view->extras .= '<a href="' . $this->url . '/?action=compile' . $subdirlink . '&amp;commit=1&amp;file=' . $this->item . '" class="i-commit">' . Lang::txt('PLG_PROJECTS_FILES_COMMIT_INTO_REPO') . '</a>';
			$view->extras .= '</span>';
		}
		echo $view->loadTemplate();
	?>
</ul>
<?php } ?>
<?php if ($this->data && !$this->binary && $this->cType != 'application/pdf') {

	// Clean up data from Windows characters - important!
	$this->data = preg_replace('/[^(\x20-\x7F)\x0A]*/','', $this->data);
?>
	<pre><?php echo htmlentities($this->data); ?></pre>
<?php } elseif ($this->embed && file_exists(PATH_APP . $this->outputDir . DS . $this->embed)) {
		$source = Route::url('index.php?option=' . $this->option . '&controller=media&alias=' . $this->model->get('alias') . '&media=Compiled:' . $this->embed );
	?>
	<div id="compiled-doc" embed-src="<?php echo $source; ?>" embed-width="<?php echo $this->oWidth; ?>" embed-height="<?php echo $this->oHeight; ?>">
	  <object width="<?php echo $this->oWidth; ?>" height="<?php echo $this->oHeight; ?>" type="<?php echo $this->cType; ?>" data="<?php echo $source; ?>" id="pdf_content">
		<embed src="<?php echo $source; ?>" type="application/pdf" />
		<p><?php echo Lang::txt('PLG_PROJECTS_FILES_PREVIEW_NOT_LOAD'); ?> <a href="<?php echo $this->url . '/?' . 'action=compile' . $subdirlink . '&amp;download=1&amp;file=' . $this->item; ?>"><?php echo Lang::txt('PLG_PROJECTS_FILES_DOWNLOAD_FILE'); ?></a>
		<?php if ($this->image) { ?>
			<img alt="" src="<?php echo Route::url('index.php?option=' . $this->option . '&task=media&alias=' . $this->model->get('alias') . '&media=Compiled:' . $this->image ); ?>" />
		<?php } ?>
		</p>
	  </object>
	</div>
<?php } ?>
</div>