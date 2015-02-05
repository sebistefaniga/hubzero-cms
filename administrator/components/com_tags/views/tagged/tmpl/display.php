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
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$canDo = TagsHelperPermissions::getActions();

JToolBarHelper::title(JText::_('COM_TAGS') . ': ' . JText::_('COM_TAGS_TAGGED'), 'tags.png');
if ($canDo->get('core.create'))
{
	JToolBarHelper::addNew();
}
if ($canDo->get('core.delete'))
{
	JToolBarHelper::deleteList();
}
JToolBarHelper::spacer();
JToolBarHelper::help('tagged');
?>

<form action="<?php echo JRoute::_('index.php?option=' . $this->option); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<div class="col width-50 fltrt">
			<label for="filter-tbl"><?php echo JText::_('COM_TAGS_FILTER'); ?>:</label>
			<select name="tbl" id="filter-tbl" onchange="document.adminForm.submit();">
				<option value=""<?php if (!$this->filters['tbl']) { echo ' selected="selected"'; } ?>><?php echo JText::_('COM_TAGS_FILTER_TYPE'); ?></option>
				<?php foreach ($this->types as $type) { ?>
					<option value="<?php echo $type; ?>"<?php if ($this->filters['tbl'] == $type) { echo ' selected="selected"'; } ?>><?php echo $type; ?></option>
				<?php } ?>
			</select>
		</div>

		<input type="hidden" name="tagid" value="<?php echo $this->filters['tagid']; ?>" />
	</fieldset>
	<div class="clr"></div>

	<table class="adminlist">
		<?php if ($this->filters['tagid']) { ?>
			<caption><?php
			$tag = new TagsModelTag($this->filters['tagid']);
			echo JText::_('COM_TAGS_TAG') . ': ' . $this->escape($tag->get('raw_tag')) . ' (' . $this->escape($tag->get('tag')) . ')';
			?></caption>
		<?php } ?>
		<thead>
			<tr>
				<th scope="col"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->rows);?>);" /></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_TAGS_COL_ID', 'id', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<?php if (!$this->filters['tagid']) { ?>
					<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_TAGS_COL_TAGID', 'tagid', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<?php } ?>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_TAGS_COL_TBL', 'tbl', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_TAGS_COL_OBJECTID', 'objectid', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_TAGS_COL_CREATED', 'taggedon', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th scope="col"><?php echo JHTML::_('grid.sort', 'COM_TAGS_COL_CREATED_BY', 'taggerid', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="<?php echo (!$this->filters['tagid'] ? 7 : 6); ?>"><?php echo $this->pageNav->getListFooter(); ?></td>
			</tr>
		</tfoot>
		<tbody>
		<?php
		$k = 0;
		$i = 0;
		foreach ($this->rows as $row)
		{
			$row = new TagsModelObject($row);
			$row->set('id', $row->get('taggedid'));
		?>
			<tr class="<?php echo "row$k"; ?>">
				<td>
					<?php if ($canDo->get('core.edit')) { ?>
						<input type="checkbox" name="id[]" id="cb<?php echo $i; ?>" value="<?php echo $row->get('id'); ?>" onclick="isChecked(this.checked);" />
					<?php } ?>
				</td>
				<td>
					<?php if ($canDo->get('core.edit')) { ?>
						<a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->get('id')); ?>">
							<?php echo $this->escape($row->get('id')); ?>
						</a>
					<?php } else { ?>
						<span>
							<?php echo $this->escape($row->get('id')); ?>
						</span>
					<?php } ?>
				</td>
				<?php if (!$this->filters['tagid']) { ?>
					<td>
						<?php if ($canDo->get('core.edit')) { ?>
							<a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->get('id')); ?>">
								<?php echo $this->escape($row->get('tagid')); ?>
							</a>
						<?php } else { ?>
							<span>
								<?php echo $this->escape($row->get('tagid')); ?>
							</span>
						<?php } ?>
					</td>
				<?php } ?>
				<td>
					<?php if ($canDo->get('core.edit')) { ?>
						<a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->get('id')); ?>">
							<?php echo $this->escape($row->get('tbl')); ?>
						</a>
					<?php } else { ?>
						<span>
							<?php echo $this->escape($row->get('tbl')); ?>
						</span>
					<?php } ?>
				</td>
				<td>
					<?php if ($canDo->get('core.edit')) { ?>
						<a href="<?php echo JRoute::_('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->get('id')); ?>">
							<?php echo $this->escape($row->get('objectid')); ?>
						</a>
					<?php } else { ?>
						<span>
							<?php echo $this->escape($row->get('objectid')); ?>
						</span>
					<?php } ?>
				</td>
				<td>
					<time datetime="<?php echo $row->get('taggedon'); ?>"><?php echo ($row->get('taggedon') != '0000-00-00 00:00:00' ? $row->get('taggedon') : JText::_('COM_TAGS_UNKNOWN')); ?></time>
				</td>
				<td>
					<?php if ($row->get('taggerid')) { ?>
						<a href="<?php echo JRoute::_('index.php?option=com_members&controller=members&task=edit&id=' . $row->get('taggerid')); ?>">
							<?php echo $row->creator('name', JText::_('COM_TAGS_UNKNOWN')); ?>
						</a>
					<?php } else { ?>
						<?php echo JText::_('COM_TAGS_UNKNOWN'); ?>
					<?php } ?>
				</td>
			</tr>
		<?php
			$i++;
			$k = 1 - $k;
		}
		?>
		</tbody>
	</table>

	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
	<input type="hidden" name="task" value="<?php echo $this->task; ?>" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->filters['sort']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->filters['sort_Dir']; ?>" />

	<?php echo JHTML::_('form.token'); ?>
</form>