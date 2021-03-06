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

$canDo = \Components\Members\Helpers\Permissions::getActions('component');

// Menu
Toolbar::title(Lang::txt('COM_MEMBERS_QUOTAS'), 'user.png');
if ($canDo->get('core.edit'))
{
	Toolbar::addNew();
	Toolbar::editList();
	Toolbar::custom('restoreDefault', 'restore', 'restore', 'COM_MEMBERS_DEFAULT');
}

$this->css('quotas.css');
?>

<script type="text/javascript">
	jQuery(document).ready(function ( $ ) {
		setTimeout(doWork, 10);

		function doWork() {
			var rows = $('.quota-row');

			rows.each(function ( i, el ) {
				var id = $(el).find('.row-id').val();
				var usage = $(el).find('.usage-outer');

				$.ajax({
					url      : 'index.php?option=com_members&controller=quotas&task=getQuotaUsage',
					dataType : 'JSON',
					type     : 'GET',
					data     : {"id":id},
					success  : function ( data, textStatus, jqXHR ) {
						if (data.percent > 100) {
							data.percent = 100;
							usage.find('.usage-inner').addClass('max');
						}
						usage.prev('.usage-calculating').hide();
						usage.fadeIn();
						usage.find('.usage-inner').css('width', data.percent+"%");
					},
					error : function ( ) {
						usage.prev('.usage-calculating').hide();
						usage.next('.usage-unavailable').show();
					}
				});
			});
		};
	});
</script>

<?php
	$this->view('_submenu')
	     ->display();
?>

<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<div class="col width-40 fltlft">
			<label for="filter_search_field"><?php echo Lang::txt('COM_MEMBERS_SEARCH'); ?></label>
			<select name="search_field" id="filter_search_field">
				<option value="username"<?php if ($this->filters['search_field'] == 'username') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_MEMBERS_QUOTA_USERNAME'); ?></option>
				<option value="name"<?php if ($this->filters['search_field'] == 'name') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_MEMBERS_QUOTA_NAME'); ?></option>
			</select>

			<label for="filter_search"><?php echo Lang::txt('COM_MEMBERS_SEARCH_FOR'); ?></label>
			<input type="text" name="search" id="filter_search" value="<?php echo $this->escape($this->filters['search']); ?>" placeholder="<?php echo Lang::txt('COM_MEMBERS_SEARCH_PLACEHOLDER'); ?>" />

			<input type="submit" value="<?php echo Lang::txt('COM_MEMBERS_GO'); ?>" />
		</div>
		<div class="col width-60 fltrt">
			<select name="class_alias" id="filter_class_alias" onchange="document.adminForm.submit( );">
				<option value=""<?php if ($this->filters['class_alias'] == '') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_MEMBERS_FILTER_QUOTA_CLASS'); ?></option>
				<?php foreach ($this->classes as $class) : ?>
					<option value="<?php echo $class->alias; ?>"<?php if ($this->filters['class_alias'] == $class->alias) { echo ' selected="selected"'; } ?>><?php echo $this->escape($class->alias); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="clr"></div>
	</fieldset>
	<table class="adminlist">
		<thead>
			<tr>
				<th><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->rows);?>);" /></th>
				<th><?php echo JHTML::_('grid.sort', 'COM_MEMBERS_QUOTA_USER_ID', 'user_id', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th><?php echo JHTML::_('grid.sort', 'COM_MEMBERS_QUOTA_USERNAME', 'username', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th><?php echo JHTML::_('grid.sort', 'COM_MEMBERS_QUOTA_NAME', 'name', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th><?php echo JHTML::_('grid.sort', 'COM_MEMBERS_QUOTA_CLASS', 'class_alias', @$this->filters['sort_Dir'], @$this->filters['sort']); ?></th>
				<th><?php echo Lang::txt('COM_MEMBERS_QUOTA_DISK_USAGE'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="6">
					<?php
					// Initiate paging
					jimport('joomla.html.pagination');
					$pageNav = new JPagination(
						$this->total,
						$this->filters['start'],
						$this->filters['limit']
					);
					echo $pageNav->getListFooter();
					?>
				</td>
			</tr>
		</tfoot>
		<tbody>
<?php
$k = 0;
for ($i=0, $n=count($this->rows); $i < $n; $i++)
{
	$row = &$this->rows[$i];
?>
			<tr class="<?php echo "row$k quota-row"; ?>">
				<td>
					<input class="row-id" type="checkbox" name="id[]" id="cb<?php echo $i; ?>" value="<?php echo $row->id; ?>" onclick="isChecked(this.checked);" />
				</td>
				<td>
					<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->id); ?>">
						<?php echo $this->escape($row->user_id); ?>
					</a>
				</td>
				<td>
					<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id=' . $row->id); ?>">
						<?php echo $this->escape($row->username); ?>
					</a>
				</td>
				<td>
					<?php echo $this->escape($row->name); ?>
				</td>
				<td>
					<?php echo ($row->class_alias) ? $this->escape($row->class_alias) : 'custom'; ?>
				</td>
				<td>
					<div class="usage-calculating"><?php echo Lang::txt('COM_MEMBERS_QUOTA_CALCULATING'); ?></div>
					<div class="usage-outer">
						<div class="usage-inner"></div>
					</div>
					<div class="usage-unavailable"><?php echo Lang::txt('COM_MEMBERS_QUOTA_UNAVAILABLE'); ?></div>
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