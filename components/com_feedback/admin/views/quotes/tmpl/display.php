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

$canDo = \Components\Feedback\Helpers\Permissions::getActions('quote');

Toolbar::title(Lang::txt('COM_FEEDBACK'), 'feedback.png');
if ($canDo->get('core.admin'))
{
	Toolbar::preferences($this->option, '550');
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
Toolbar::help('quotes');

?>
<script type="text/javascript">
function submitbutton(pressbutton)
{
	var form = document.adminForm;
	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}
	// do field validation
	submitform(pressbutton);
}
</script>

<form action="<?php echo Route::url('index.php?option=' . $this->option); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<label for="filter_search"><?php echo Lang::txt('JSEARCH_FILTER'); ?>:</label>
		<input type="text" name="search" id="filter_search" value="<?php echo $this->escape($this->filters['search']); ?>" placeholder="<?php echo Lang::txt('COM_FEEDBACK_FILTER_SEARCH_PLACEHOLDER'); ?>" />

		<input type="submit" value="<?php echo Lang::txt('COM_FEEDBACK_GO'); ?>" />
		<button type="button" onclick="$('#filter_search').val('');this.form.submit();"><?php echo Lang::txt('JSEARCH_FILTER_CLEAR'); ?></button>
	</fieldset>
	<div class="clr"></div>

	<table class="adminlist">
		<thead>
			<tr>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_FEEDBACK_COL_ID', 'id', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><input type="checkbox" name="toggle" value="" onClick="checkAll(<?php echo count($this->rows);?>);" /></th>
				<th scope="col" class="priority-2"><?php echo JHTML::_('grid.sort', 'COM_FEEDBACK_COL_SUBMITTED', 'date', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-3"><?php echo JHTML::_('grid.sort', 'COM_FEEDBACK_COL_AUTHOR', 'fullname', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col" class="priority-5"><?php echo JHTML::_('grid.sort', 'COM_FEEDBACK_COL_ORGANIZATION', 'org', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo Lang::txt('COM_FEEDBACK_COL_QUOTE'); ?></th>
				<th scope="col"><?php echo Lang::txt('COM_FEEDBACK_COL_QUOTES'); ?></th>
				<th scope="col" class="priority-4"><?php echo Lang::txt('COM_FEEDBACK_COL_OK_PUBLISH'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="8"><?php
				// Initiate paging class
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
	$row = &$this->rows[$i];

	if (!trim($row->quote))
	{
		$row->quote = $row->short_quote;
	}
	if (!trim($row->quote))
	{
		$row->quote = $row->miniquote;
	}
	if (!trim($row->quote))
	{
		$row->quote = Lang::txt('COM_FEEDBACK_BLANK');
	}
?>
			<tr class="<?php echo "row$k"; ?>">
				<td>
					<?php echo $row->id; ?>
				</td>
				<td>
					<input type="checkbox" name="id[]" id="cb<?php echo $i;?>" value="<?php echo $row->id; ?>" onClick="isChecked(this.checked);" />
				</td>
				<td class="priority-2">
					<?php if ($row->date && $row->date != '0000-00-00 00:00:00') { ?>
						<time datetime="<?php echo $row->date; ?>"><?php echo Date::of($row->date)->toLocal(Lang::txt('DATE_FORMAT_HZ1')); ?></time>
					<?php } ?>
				</td>
				<td class="priority-3">
					<?php if ($canDo->get('core.edit')) { ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->id); ?>">
							<?php echo $this->escape(stripslashes($row->fullname)); ?>
						</a>
					<?php } else { ?>
						<span>
							<?php echo $this->escape(stripslashes($row->fullname)); ?>
						</span>
					<?php } ?>
				</td>
				<td class="priority-5">
					<?php echo ($row->org) ? $this->escape(stripslashes($row->org)) : '&nbsp;'; ?>
				</td>
				<td>
					<?php if ($canDo->get('core.edit')) { ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->id); ?>">
							<?php echo $this->escape(\Hubzero\Utility\String::truncate(strip_tags($row->quote), 100)); ?>
						</a>
					<?php } else { ?>
						<span>
							<?php echo $this->escape(\Hubzero\Utility\String::truncate(strip_tags($row->quote), 100)); ?>
						</span>
					<?php } ?>
				</td>
				<td>
					<?php echo ($row->notable_quote == 1) ? '<span class="state yes"><span>' . Lang::txt('JYES') . '</span></span>' : ''; ?>

				</td>
				<td class="priority-4">
					<?php echo ($row->publish_ok == 1) ? '<span class="state yes"><span>' . Lang::txt('JYES') . '</span></span>' : ''; ?>
				</td>
			</tr>
<?php
	$k = 1 - $k;
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