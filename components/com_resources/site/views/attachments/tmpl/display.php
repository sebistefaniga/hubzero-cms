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

$this->css('create.css')
     ->js('create.js');
?>
	<div id="small-page">
		<?php if (!Request::getInt('hideform', 0)) { ?>
		<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" name="hubForm" id="attachments-form" method="post" enctype="multipart/form-data">
			<fieldset>
				<label>
					<input type="file" class="option" name="upload" />
				</label>
				<input type="submit" class="option" value="<?php echo Lang::txt('COM_CONTRIBUTE_UPLOAD'); ?>" />

				<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
				<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
				<input type="hidden" name="tmpl" value="component" />
				<input type="hidden" name="pid" id="pid" value="<?php echo $this->id; ?>" />
				<input type="hidden" name="path" id="path" value="<?php echo $this->path; ?>" />
				<input type="hidden" name="task" value="save" />
			</fieldset>
		</form>
		<?php } ?>

	<?php if ($this->getError()) { ?>
		<p class="error"><?php echo implode('<br />', $this->getErrors()); ?></p>
	<?php } ?>

		<?php
		$out = '';
		// loop through children and build list
		if ($this->children)
		{
			$base = $this->config->get('uploadpath');

			$k = 0;
			$i = 0;
			$files = array(13,15,26,33,35,38);
			$n = count($this->children);
		?>
		<p><?php echo Lang::txt('COM_CONTRIBUTE_ATTACH_EDIT_TITLE_EXPLANATION'); ?></p>
		<table class="list">
			<tbody>
			<?php
			foreach ($this->children as $child)
			{
				$k++;

				// figure ou the URL to the file
				switch ($child->type)
				{
					case 12:
						if ($child->path)
						{
							// internal link, not a resource
							$url = $child->path;
						}
						else
						{
							// internal link but a resource
							$url = '/index.php?option=com_resources&id=' . $child->id;
						}
						break;
					default:
						$url = $child->path;
						break;
				}

				// figure out the file type so we can give it the appropriate CSS class
				$type = JFile::getExt($url);
				if (!$child->type != 12 && $child->type != 11)
				{
					$type = ($type) ? $type : 'html';
				}

				$isFile = true;
				if (($child->type == 12 || $child->type == 11)
				 || in_array($type, array('html', 'htm', 'php', 'asp', 'shtml'))
				 || strstr($url, '?'))
				{
					$isFile = false;
				}
				?>
				<tr>
					<td width="100%">
						<span class="ftitle item:name id:<?php echo $child->id; ?>" data-id="<?php echo $child->id; ?>">
							<?php echo $this->escape($child->title); ?>
						</span>
						<?php echo ($isFile) ? \Components\Resources\Helpers\Html::getFileAttribs($url, $base) : '<span class="caption">' . $url . '</span>'; ?>
					</td>
					<td class="u">
						<?php
						if ($i > 0 || ($i+0 > 0)) {
							echo '<a href="index.php?option=' . $this->option . '&amp;controller=' . $this->controller . '&amp;tmpl=component&amp;pid='.$this->id.'&amp;id='.$child->id.'&amp;task=reorder&amp;move=up" class="order up" title="'.Lang::txt('COM_CONTRIBUTE_MOVE_UP').'"><span>'.Lang::txt('COM_CONTRIBUTE_MOVE_UP').'</span></a>';
						} else {
							echo '&nbsp;';
						}
						?>
					</td>
					<td class="d">
						<?php
						if ($i < $n-1 || $i+0 < $n-1) {
							echo '<a href="index.php?option=' . $this->option . '&amp;controller=' . $this->controller . '&amp;tmpl=component&amp;pid='.$this->id.'&amp;id='.$child->id.'&amp;task=reorder&amp;move=down" class="order down" title="'.Lang::txt('COM_CONTRIBUTE_MOVE_DOWN').'"><span>'.Lang::txt('COM_CONTRIBUTE_MOVE_DOWN').'</span></a>';
						} else {
							echo '&nbsp;';
						}
						?>
					</td>
					<td class="t">
						<a class="icon-delete delete" href="index.php?option=<?php echo $this->option; ?>&amp;controller=<?php echo $this->controller; ?>&amp;task=delete&amp;tmpl=component&amp;id=<?php echo $child->id; ?>&amp;pid=<?php echo $this->id; ?>">
							<span><?php echo Lang::txt('COM_CONTRIBUTE_DELETE'); ?></span>
						</a>
					</td>
				</tr>
				<?php
				$i++;
			}
			?>
			</tbody>
		</table>
	<?php } else { ?>
		<p><?php echo Lang::txt('COM_CONTRIBUTE_ATTACH_NONE_FOUND'); ?></p>
	<?php } ?>
	</div>