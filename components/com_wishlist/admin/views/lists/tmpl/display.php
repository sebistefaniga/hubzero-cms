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
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$canDo = \Components\Wishlist\Helpers\Permissions::getActions('component');

Toolbar::title(Lang::txt('COM_WISHLIST'), 'wishlist.png');
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
if ($canDo->get('core.edit'))
{
	Toolbar::editList();
}
if ($canDo->get('core.delete'))
{
	Toolbar::deleteList();
}
Toolbar::spacer();
Toolbar::help('lists');
?>
<script type="text/javascript">
/*function submitbutton(pressbutton)
{
	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}
	// do field validation
	submitform(pressbutton);
}*/
</script>

<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<div class="col width-50 fltlft">
			<label for="filter_search"><?php echo Lang::txt('COM_WISHLIST_SEARCH'); ?>:</label>
			<input type="text" name="search" id="filter_search" value="<?php echo $this->escape($this->filters['search']); ?>" placeholder="<?php echo Lang::txt('COM_WISHLIST_SEARCH_PLACEHOLDER'); ?>" />

			<input type="submit" value="<?php echo Lang::txt('COM_WISHLIST_GO'); ?>" />
			<button type="button" onclick="$('#filter_search').val('');this.form.submit();"><?php echo Lang::txt('JSEARCH_FILTER_CLEAR'); ?></button>
		</div>
		<div class="col width-50 fltrt">
			<label for="filter-category"><?php echo Lang::txt('COM_WISHLIST_CATEGORY'); ?>:</label>
			<select name="category" id="filter-category" onchange="this.form.submit()">
				<option value=""<?php echo ($this->filters['category'] == '') ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_WISHLIST_SELECT_CATEGORY'); ?></option>
				<option value="general"<?php echo ($this->filters['category'] == 'general') ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_WISHLIST_CATEGORY_GENERAL'); ?></option>
				<option value="group"<?php echo ($this->filters['category'] == 'group') ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_WISHLIST_CATEGORY_GROUP'); ?></option>
				<option value="resource"<?php echo ($this->filters['category'] == 'resource') ? ' selected="selected"' : ''; ?>><?php echo Lang::txt('COM_WISHLIST_CATEGORY_RESOURCE'); ?></option>
			</select>
		</div>
	</fieldset>
	<div class="clr"></div>

	<table class="adminlist">
		<thead>
			<tr>
				<th scope="col"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->rows);?>);" /></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_WISHLIST_TITLE', 'title', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_WISHLIST_STATE', 'state', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-3"><?php echo JHTML::_('grid.sort', 'COM_WISHLIST_ACCESS', 'public', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-4" colspan="2"><?php echo JHTML::_('grid.sort', 'COM_WISHLIST_CATEGORY', 'category', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-2"><?php echo JHTML::_('grid.sort', 'COM_WISHLIST_WISHES', 'total', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="7"><?php
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
$k = 0;
for ($i=0, $n=count($this->rows); $i < $n; $i++)
{
	$row =& $this->rows[$i];
	switch ($row->state)
	{
		case 1:
			$class = 'publish';
			$task  = 'unpublish';
			$alt   = Lang::txt('COM_WISHLIST_PUBLISHED');
		break;
		case 2:
			$class = 'expire';
			$task  = 'publish';
			$alt   = Lang::txt('COM_WISHLIST_TRASHED');
		break;
		case 0:
			$class = 'unpublish';
			$task  = 'publish';
			$alt   = Lang::txt('COM_WISHLIST_UNPUBLISHED');
		break;
	}

	if (!$row->public)
	{
		$color_access = 'access private';
		$task_access  = 'accessregistered';
		$groupname    = 'Private';
	}
	else
	{
		$color_access = 'access public';
		$task_access  = 'accesspublic';
		$groupname    = 'Public';
	}
?>
			<tr class="<?php echo "row$k"; ?>">
				<td>
					<input type="checkbox" name="id[]" id="cb<?php echo $i; ?>" value="<?php echo $row->id; ?>" onclick="isChecked(this.checked, this);" />
				</td>
				<td>
					<?php if ($canDo->get('core.edit')) { ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->id); ?>">
							<span><?php echo $this->escape(stripslashes($row->title)); ?></span>
						</a>
					<?php } else { ?>
						<span>
							<span><?php echo $this->escape(stripslashes($row->title)); ?></span>
						</span>
					<?php } ?>
				</td>
				<td>
					<?php if ($canDo->get('core.edit.state')) { ?>
						<a class="state <?php echo $class; ?>" href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=' . $task . '&id=' . $row->id . '&' . JUtility::getToken() . '=1'); ?>" title="<?php echo Lang::txt('COM_WISHLIST_SET_TASK',$task);?>">
							<span><?php echo $alt; ?></span>
						</a>
					<?php } else { ?>
						<span class="state <?php echo $class; ?>">
							<span><?php echo $alt; ?></span>
						</span>
					<?php } ?>
				</td>
				<td class="priority-3">
					<?php if ($canDo->get('core.edit.state')) { ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=' . $task_access . '&id=' . $row->id . '&' . JUtility::getToken() . '=1'); ?>" class="<?php echo $color_access; ?>" title="<?php echo Lang::txt('COM_WISHLIST_CHANGE_ACCESS'); ?>">
							<?php echo $groupname; ?>
						</a>
					<?php } else { ?>
						<span class="<?php echo $color_access; ?>">
							<?php echo $groupname; ?>
						</span>
					<?php } ?>
				</td>
				<td class="priority-4">
					<span class="glyph category">
						<span><?php echo $this->escape(stripslashes($row->category)); ?></span>
					</span>
				</td>
				<td class="priority-4">
					<span>
						<span><?php echo $this->escape(stripslashes($row->referenceid)); ?></span>
					</span>
				</td>
				<td class="priority-2">
					<?php if ($row->wishes > 0) { ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=wishes&wishlist=' . $row->id); ?>">
							<span><?php echo $row->wishes . ' ' . Lang::txt('COM_WISHLIST_LIST_WISHES'); ?></span>
						</a>
					<?php } else { ?>
						<span>
							<span><?php echo $row->wishes; ?></span>
						</span>
					<?php } ?>
				</td>
			</tr>
<?php
	$k = 1 - $k;
}
?>
		</tbody>
	</table>

	<input type="hidden" name="option" value="<?php echo $this->option ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
	<input type="hidden" name="task" value="<?php echo $this->task; ?>" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->filters['sort']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->filters['sort_Dir']; ?>" />

	<?php echo JHTML::_('form.token'); ?>
</form>
