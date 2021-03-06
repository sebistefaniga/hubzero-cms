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

$this->css()
     ->js();

$v = count($this->versions) + 1;

// Directory path breadcrumbs
$bc = \Components\Projects\Helpers\Html::buildFileBrowserCrumbs($this->subdir, $this->url, $parent);

$i 		 = 0;
$shown   = 0;
$skipped = 0;
$locals  = 0;
$sLocals = 0;

$candiff = count($this->versions);
foreach ($this->versions as $version) {
	if ($version['hide'] == 1 || $version['commitStatus'] == 'D' || $version['remote'])
	{
		$candiff--;
	}
	if (!$version['remote'])
	{
		$locals++;
	}
}

$endPath = ' &raquo; <span class="subheader">' . Lang::txt('PLG_PROJECTS_FILES_SHOW_REV_HISTORY_FOR') . ' <span class="italic">' . \Components\Projects\Helpers\Html::shortenFileName($this->file, 40) . '</span></span>';

$ext = \Components\Projects\Helpers\Html::getFileExtension($this->file);

$allowDiff = ($this->binary || ($this->remote && $this->remote['converted'] == 1) || $candiff <= 1 ) ? 0 : 1;

?>
<?php if ($this->ajax) { ?>
<div id="abox-content">
<h3><?php echo Lang::txt('PLG_PROJECTS_FILES_SHOW_HISTORY'); ?></h3>
<?php
// Display error
if ($this->getError()) {
	echo ('<p class="witherror">'.$this->getError().'</p>');
}
?>
<?php } ?>

<form id="<?php echo $this->ajax ? 'hubForm-ajax' : 'plg-form'; ?>" method="get" action="<?php echo $this->url; ?>">
	<?php if (!$this->ajax) { ?>
		<div id="plg-header">
			<h3 class="files">
				<a href="<?php echo $this->url; ?>"><?php echo $this->title; ?></a><?php if ($this->subdir) { ?> <?php echo $bc; ?><?php } ?>
			<?php echo $endPath; ?>
			</h3>
		</div>
	<?php } ?>

	<fieldset >
		<input type="hidden" name="subdir" value="<?php echo $this->subdir; ?>" />
		<input type="hidden" name="file" value="<?php echo $this->file; ?>" />
		<input type="hidden" name="action" value="diff" />
			<ul class="sample">
				<?php
					// Display list item with file data
					$view = $this->view('default', 'selected');
					$view->skip 		= false;
					$view->item 		= $this->file;
					$view->subdir 		= $this->subdir;
					$view->remote		= $this->remote;
					$view->type			= 'file';
					$view->action		= 'history';
					$view->multi		= '';

					if ($allowDiff && !$this->getError())
					{
						$view->extras = '<input type="submit" id="rundiff" value="' . Lang::txt('PLG_PROJECTS_FILES_DIFF_REVISIONS') . '" class="btn rightfloat" />';
					}
					echo $view->loadTemplate();
				?>
			</ul>

			<?php if (!$this->getError()) { ?>
			<table class="revisions">
				<thead>
					<tr>
						<?php if ($allowDiff) { ?>
						<th>Rev</th>
						<?php } ?>
						<th><?php echo Lang::txt('PLG_PROJECTS_FILES_REVISION_OWNER'); ?></th>
						<?php if ($allowDiff) { ?>
						<th class="diffing">Diff</th>
						<?php } ?>
						<th><?php echo Lang::txt('PLG_PROJECTS_FILES_REVISION_DIFF'); ?></th>
						<th><?php echo Lang::txt('PLG_PROJECTS_FILES_REVISION_OPTIONS'); ?></th>
					</tr>
				</thead>
				<tbody>
			<?php foreach ($this->versions as $version) {

				if ($version['hide'] == 1)
				{
					$skipped++;
					continue;
				}
				$last 		= $i == 0 ? true : false;

				$origin		= $version['remote']
					? Lang::txt('PLG_PROJECTS_FILES_FILE_STATUS_REMOTE')
					: Lang::txt('PLG_PROJECTS_FILES_FILE_STATUS_LOCAL');
				if (!$version['remote'] && preg_match("/[SFTP]/", $version['message']))
				{
					$origin = 'SFTP';
				}
				$status = '<span class="commit-type">[' . $origin . ']</span> ';
				$name		= $version['remote'] && $this->remote ? $this->remote['title'] : $version['name'];

				// Get url, name and status
				if ($version['remote'])
				{
					$url = $this->url
						. '/?action=open&amp;subdir='.urlencode($this->subdir)
						. '&amp;file='.urlencode($version['file']);

					if ($this->connected && $last == true)
					{
						$action  = '<a href="' . $url .'" class="open_file" title="'
							. Lang::txt('PLG_PROJECTS_FILES_REMOTE_OPEN') .'" target="_blank">&nbsp;</a>';
					}
					else
					{
						$action  = '';
					}
				}
				else
				{
					$url = $this->url
						.'/?file='.urlencode($version['name'])
						. '&amp;action=download&amp;hash='.$version['hash'];
					$action = (in_array($version['commitStatus'], array('A', 'M', 'R', 'W')))
						? '<a href="' . $url .'" class="download_file" title="' . Lang::txt('PLG_PROJECTS_FILES_DOWNLOAD') . '" >&nbsp;</a>'
						: '';
				}

				if ($version['change'])
				{
					// Other type of change
					$status .= ' ' . $version['change'];
				}

				if ($last)
				{
					$status .= ' <span class="crev">' . Lang::txt('PLG_PROJECTS_FILES_FILE_STATUS_CURRENT') . '</span>';
				}

				$charLimit = $last == true ? 400 : 400;

				$trclass = $last ? 'current-revision' : '';
				$trclass = $version['commitStatus'] == 'D' ? 'deleted-revision' : $trclass;

				$v--;

				if ($version['commitStatus'] == 'D')
				{
					$skipped++;
					continue;
				}

				$shown++;

				if (!$version['remote'])
				{
					$sLocals++;
				}

				// Oldest local shown?
				$oldest = (!$version['remote'] && ((($skipped + $shown) == count($this->versions))
							|| $sLocals == $locals)) ? true : false;

				?>
				<tr <?php if ($trclass) { echo 'class="' . $trclass . '"'; } ?>>
					<?php if ($allowDiff) { ?>
					<td><?php echo '@'.$v; ?></td>
					<?php } ?>
					<td class="commit-actor"><span class="prominent"><?php echo \Components\Projects\Helpers\Html::formatTime($version['date'], true); ?></span>
						<span class="block"><?php echo $version['author'] ? $version['author'] : $version['email']; ?></span>
					</td>
					<?php if ($allowDiff) { ?>
					<td class="diffing">
						<?php if (!$version['remote'] && count($this->versions) > 1) { ?>
						<input type="radio" value="<?php echo urlencode($v . '@' . substr($version['hash'], 0, 10) . '@' . $version['name'] ); ?>" name="old" <?php if ($oldest) { echo 'checked="checked"'; } ?> <?php if ($last) { echo 'disabled="disabled"'; } ?> class="diff-old" />
						<input type="radio" value="<?php echo urlencode($v . '@' . substr($version['hash'], 0, 10) . '@' . $version['name'] ); ?>" name="new" <?php if ($last) { echo 'checked="checked"'; } ?> <?php if ($oldest) { echo 'disabled="disabled"'; } ?> class="diff-new" />
						<?php } ?>
					</td>
					<?php } ?>
					<td class="commit-details">
							<?php if ($version['movedTo']) { ?>
								<span class="moved"><span class="<?php echo $version['movedTo'] == 'remote' ? 'send_to_remote' : 'send_to_local'; ?>"><span>&nbsp;</span></span></span>
							<?php } ?>
						<span class="commitstatus"><?php echo $status; ?></span>
						<span class="block italic faded"><?php echo $version['name']; echo $version['size'] ? ', ' . $version['size'] : '';  ?></span>
						<div class="commitcontent"><?php if ($version['content'] && in_array($version['commitStatus'], array('A', 'M')))
						{
							$over = strlen($version['content']) >= $charLimit ? 1 : 0;
							$content = $over ? \Hubzero\Utility\String::truncate($version['content'], $charLimit) : $version['content'];

							echo '<div class="short-txt" id="short-' . $i . '"><pre>' . $content . '</pre>';
							if ($over)
							{
								echo '<p class="showaslink showmore js">' . Lang::txt('PLG_PROJECTS_FILES_SHOW_MORE') . '</p>';
							}
							echo '</div>';
							if ($over)
							{
								echo '<div class="long-txt hidden" id="long-' . $i . '"><pre>' . $version['content'] . '</pre>';
								echo '<p class="showaslink showless">' . Lang::txt('PLG_PROJECTS_FILES_SHOW_LESS') . '</p>';
								echo '</div>';
							}
						}
						?>
						<?php if ($version['preview'] && $version['commitStatus'] != 'D') { ?>
							<div id="preview-image">
								<img src="<?php echo $version['preview']; ?>" alt="<?php echo Lang::txt('PLG_PROJECTS_FILES_LOADING_PREVIEW'); ?>" />
							</div>
						<?php } ?>
						</div>
					</td>
					<td class="commit-options">
						<?php echo $action; ?>
					</td>
				</tr>
			<?php $i++; } ?>
				</tbody>
			</table>
			<?php } ?>

			<?php if ($this->getError()) {
				echo ('<p class="witherror">'.$this->getError().'</p>');
			} ?>
		</fieldset>
</form>

<?php if ($this->ajax) { ?>
</div>
<?php } ?>