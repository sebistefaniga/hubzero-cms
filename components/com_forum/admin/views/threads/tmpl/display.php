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
defined('_JEXEC') or die('Restricted access');

$canDo = \Components\Forum\Helpers\Permissions::getActions('thread');

Toolbar::title(Lang::txt('COM_FORUM') . ': ' . Lang::txt('COM_FORUM_THREADS'), 'forum.png');
if ($canDo->get('core.admin'))
{
	Toolbar::preferences($this->option, '550');
	Toolbar::spacer();
}
if ($canDo->get('core.edit.state'))
{
	Toolbar::publishList();
	Toolbar::unpublishList();
	Toolbar::spacer();
}
if ($canDo->get('core.create'))
{
	Toolbar::addNew();
}
if ($canDo->get('core.delete'))
{
	Toolbar::deleteList();
}
Toolbar::spacer();
Toolbar::help('threads');
?>
<script type="text/javascript">
function submitbutton(pressbutton) {
	var form = document.adminForm;
	if (pressbutton == 'cancel') {
		submitform( pressbutton );
		return;
	}
	// do field validation
	submitform( pressbutton );
}
</script>

<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<label for="scopeinfo"><?php echo Lang::txt('COM_FORUM_FILTER_SCOPE'); ?>:</label>
		<select name="scopeinfo" id="scopeinfo" style="max-width: 20em;" onchange="document.adminForm.submit();">
			<option value=""<?php if ($this->filters['scopeinfo'] == '') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_FORUM_FILTER_SCOPE_SELECT'); ?></option>
			<option value="site:0"<?php if ($this->filters['scopeinfo'] == 'site:0') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_FORUM_NONE'); ?></option>
			<?php
			$list = array();

			foreach ($this->forum->scopes() as $result)
			{
				if (!isset($list[$result->scope]))
				{
					$list[$result->scope] = array();
				}
				$list[$result->scope][$result->scope_id] = $result;
			}

			$html = '';
			foreach ($list as $label => $optgroup)
			{
				if ($label == 'site')
				{
					continue;
				}
				$html .= ' <optgroup label="' . $label . '">';
				foreach ($optgroup as $result)
				{
					$html .= ' <option value="' . $result->scope . ':' . $result->scope_id . '"';
					if ($this->filters['scopeinfo'] == $result->scope . ':' . $result->scope_id)
					{
						$html .= ' selected="selected"';
					}
					$html .= '>' . $this->escape(stripslashes($result->caption));
					$html .= '</option>'."\n";
				}
				$html .= '</optgroup>'."\n";
			}
			echo $html;
			?>
		</select>

		<?php if (count($this->sections) > 0) { ?>
			<label for="field-section_id"><?php echo Lang::txt('COM_FORUM_FILTER_SECTION'); ?>:</label>
			<select name="section_id" id="field-section_id" onchange="document.adminForm.submit( );">
				<option value="-1"><?php echo Lang::txt('COM_FORUM_FILTER_SECTION_SELECT'); ?></option>
				<?php
				foreach ($this->sections as $section)
				{
					?>
					<option value="<?php echo $section->id; ?>"<?php if ($this->filters['section_id'] == $section->id) { echo ' selected="selected"'; } ?>><?php echo $this->escape(stripslashes($section->title)); ?></option>
					<?php
				}
				?>
			</select>
		<?php } ?>

		<?php if ($this->filters['section_id'] && $this->filters['section_id'] > 0) { ?>
			<label for="field-category_id"><?php echo Lang::txt('COM_FORUM_FILTER_CATEGORY'); ?>:</label>
			<select name="category_id" id="field-category_id" onchange="document.adminForm.submit( );">
				<option value="-1"><?php echo Lang::txt('COM_FORUM_FILTER_CATEGORY_SELECT'); ?></option>
				<?php
				foreach ($this->categories as $category)
				{
					?>
					<option value="<?php echo $category->id; ?>"<?php if ($this->filters['category_id'] == $category->id) { echo ' selected="selected"'; } ?>><?php echo $this->escape(stripslashes($category->title)); ?></option>
					<?php
				}
				?>
			</select>
		<?php } ?>
	</fieldset>
	<div class="clr"></div>

	<table class="adminlist">
		<thead>
			<tr>
				<th scope="col"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->results );?>);" /></th>
				<th scope="col" class="priority-5"><?php echo JHTML::_('grid.sort', 'COM_FORUM_COL_ID', 'id', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_FORUM_COL_TITLE', 'title', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_FORUM_COL_STATE', 'state', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-4"><?php echo JHTML::_('grid.sort', 'COM_FORUM_COL_STICKY', 'sticky', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-3"><?php echo JHTML::_('grid.sort', 'COM_FORUM_COL_ACCESS', 'access', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-3"><?php echo JHTML::_('grid.sort', 'COM_FORUM_COL_SCOPE', 'scope', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-5"><?php echo JHTML::_('grid.sort', 'COM_FORUM_COL_CREATOR', 'created_by', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-4"><?php echo JHTML::_('grid.sort', 'COM_FORUM_COL_CREATED', 'created', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="9"><?php
				// Initiate paging
				jimport('joomla.html.pagination');
				$pageNav = new JPagination(
					$this->total,
					$this->filters['start'],
					$this->filters['limit']
				);
				echo $pageNav->getListFooter();
				?></td>
			</tr>
		</tfoot>
		<tbody>
<?php
if ($this->results)
{
	$k = 0;
	for ($i=0, $n=count($this->results); $i < $n; $i++)
	{
		$row =& $this->results[$i];
		switch (intval($row->state))
		{
			case 2:
				$task = 'publish';
				$alt = Lang::txt('JTRASHED');
				$cls = 'trash';
			break;
			case 1:
				$task = 'unpublish';
				$alt = Lang::txt('JPUBLISHED');
				$cls = 'publish';
			break;
			case 0:
			default:
				$task = 'publish';
				$alt = Lang::txt('JUNPUBLISHED');
				$cls = 'unpublish';
			break;
		}

		switch ($row->sticky)
		{
			case '1':
				$stickyTask = '0';
				$stickyAlt = Lang::txt('COM_FORUM_STICKY');
				$stickyTitle = Lang::txt('COM_FORUM_NOT_STICKY');
				$scls = 'publish';
			break;
			case '0':
			default:
				$stickyTask = '1';
				$stickyAlt = Lang::txt('COM_FORUM_NOT_STICKY');
				$stickyTitle = Lang::txt('COM_FORUM_STICKY');
				$scls = 'unpublish';
			break;
		}

		switch ($row->access)
		{
			case 0:
				$color_access = 'public';
				$task_access  = '1';
				$row->groupname = Lang::txt('COM_FORUM_ACCESS_PUBLIC');
				break;
			case 1:
				$color_access = 'registered';
				$task_access  = '2';
				$row->groupname = Lang::txt('COM_FORUM_ACCESS_REGISTERED');
				break;
			case 2:
				$color_access = 'special';
				$task_access  = '3';
				$row->groupname = Lang::txt('COM_FORUM_ACCESS_SPECIAL');
				break;
			case 3:
				$color_access = 'protected';
				$task_access  = '4';
				$row->groupname = Lang::txt('COM_FORUM_ACCESS_PROTECTED');
				break;
			case 4:
				$color_access = 'private';
				$task_access  = '0';
				$row->groupname = Lang::txt('COM_FORUM_ACCESS_PRIVATE');
				break;
		}
?>
			<tr class="<?php echo "row$k" . ($row->state ==2 ? ' archived' : ''); ?>">
				<td>
					<input type="checkbox" name="id[]" id="cb<?php echo $i; ?>" value="<?php echo $row->id; ?>" onclick="isChecked(this.checked, this);" />
				</td>
				<td class="priority-5">
					<?php echo $row->id; ?>
				</td>
				<td>
					<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&category_id=' . $this->filters['category_id'] . '&task=thread&thread=' . $row->thread); ?>">
						<?php echo $this->escape(stripslashes($row->title)); ?>
					</a>
				</td>
				<td>
					<?php if ($canDo->get('core.edit.state')) { ?>
						<a class="state <?php echo $cls; ?>" href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&category_id=' . $this->filters['category_id'] . '&task=' . $task . '&id=' . $row->id . '&' . JUtility::getToken() . '=1'); ?>" title="<?php echo Lang::txt('COM_FORUM_SET_TO', $task); ?>">
							<span><?php echo $alt; ?></span>
						</a>
					<?php } else { ?>
						<span class="state <?php echo $cls; ?>">
							<span><?php echo $alt; ?></span>
						</span>
					<?php } ?>
				</td>
				<td class="priority-4">
					<?php if ($canDo->get('core.edit.state')) { ?>
						<a class="state <?php echo $scls; ?>" href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&category_id=' . $this->filters['category_id'] . '&task=sticky&sticky=' . $stickyTask . '&id=' . $row->id . '&' . JUtility::getToken() . '=1'); ?>" title="<?php echo Lang::txt('COM_FORUM_SET_TO', $stickyTitle); ?>">
							<span><?php echo $stickyAlt; ?></span>
						</a>
					<?php } else { ?>
						<span class="state <?php echo $scls; ?>">
							<span><?php echo $stickyAlt; ?></span>
						</span>
					<?php } ?>
				</td>
				<td class="priority-3">
					<span class="access <?php echo $color_access; ?>">
						<span><?php echo $this->escape($row->groupname); ?></span>
					</span>
				</td>
				<td class="priority-3">
					<span class="scope">
						<span><?php echo $row->scope . ' (' . (isset($list[$row->scope][$row->scope_id]) ? $this->escape($list[$row->scope][$row->scope_id]->caption) : $this->escape($row->scope_id)) . ')'; ?></span>
					</span>
				</td>
				<td class="priority-5">
					<span class="creator">
						<span><?php echo $this->escape($row->created_by); ?></span>
					</span>
				</td>
				<td class="priority-4">
					<span class="created">
						<span><?php echo $this->escape($row->created); ?></span>
					</span>
				</td>
			</tr>
<?php
		$k = 1 - $k;
	}
}
?>
		</tbody>
	</table>

	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->filters['sort']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->filters['sort_Dir']; ?>" />

	<?php echo JHTML::_('form.token'); ?>
</form>