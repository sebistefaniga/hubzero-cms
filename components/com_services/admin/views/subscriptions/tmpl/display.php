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
defined('_JEXEC') or die( 'Restricted access' );

Toolbar::title(Lang::txt('COM_SERVICES') . ': ' . Lang::txt('COM_SERVICES_SUBSCRIPTIONS'), 'addedit.png');
Toolbar::preferences('com_services', '550');

$now = Date::toSql();

// Push some styles to the template
$this->css('admin.subscriptions.css');
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

<form action="<?php echo Route::url('index.php?option=' . $this->option  . '&controller=' . $this->controller); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<?php echo Lang::txt('COM_SERVICES_TOTAL_SUBSCRIPTIONS', $this->total); ?>.
		<label for="filter-filterby"><?php echo Lang::txt('COM_SERVICES_FILTER_BY'); ?>:</label>
		<select name="filterby" id="filter-filterby" onchange="document.adminForm.submit( );">
			<option value="pending"<?php if ($this->filters['filterby'] == 'pending') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SERVICES_FILTER_BY_PENDING'); ?></option>
			<option value="active"<?php if ($this->filters['filterby'] == 'processed') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SERVICES_FILTER_BY_ACTIVE'); ?></option>
			<option value="cancelled"<?php if ($this->filters['filterby'] == 'cancelled') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SERVICES_FILTER_BY_CANCELLED'); ?></option>
			<option value="all"<?php if ($this->filters['filterby'] == 'all') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SERVICES_FILTER_BY_ALL'); ?></option>
		</select>

		<label for="filter-sortby"><?php echo Lang::txt('COM_SERVICES_SORT_BY'); ?>:</label>
		<select name="sortby" id="filter-sortby" onchange="document.adminForm.submit( );">
			<option value="date"<?php if ($this->filters['sortby'] == 'date') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SERVICES_COL_ADDED'); ?></option>
			<option value="date_updated"<?php if ($this->filters['sortby'] == 'date_updated') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SERVICES_COL_LAST_UPDATED'); ?></option>
			<option value="date_expires"<?php if ($this->filters['sortby'] == 'date_expires') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SERVICES_COL_EXPIRES'); ?></option>
			<option value="pending"<?php if ($this->filters['sortby'] == 'pending') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SERVICES_COL_PENDING'); ?></option>
			<option value="status"<?php if ($this->filters['sortby'] == 'status') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SERVICES_COL_STATUS'); ?></option>
		</select>
	</fieldset>
	<div class="clr"></div>

	<table class="adminlist">
		<thead>
			<tr>
				<th scope="col"><?php echo Lang::txt('COM_SERVICES_COL_ID_CODE'); ?></th>
				<th scope="col"><?php echo Lang::txt('COM_SERVICES_COL_STATUS'); ?></th>
				<th scope="col"><?php echo Lang::txt('COM_SERVICES_COL_SERVICE'); ?></th>
				<th scope="col"><?php echo Lang::txt('COM_SERVICES_COL_PENDING'); ?></th>
				<th scope="col"><?php echo Lang::txt('COM_SERVICES_COL_USER'); ?></th>
				<th scope="col"><?php echo Lang::txt('COM_SERVICES_COL_ADDED'); ?></th>
				<th scope="col"><?php echo Lang::txt('COM_SERVICES_COL_LAST_UPDATED'); ?></th>
				<th scope="col"><?php echo Lang::txt('COM_SERVICES_COL_EXPIRES'); ?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="8">
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

	$name  = Lang::txt('COM_SERVICES_UNKNOWN');
	$login = Lang::txt('COM_SERVICES_UNKNOWN');
	$ruser = User::getInstance($row->uid);
	if (is_object($ruser))
	{
		$name  = $ruser->get('name');
		$login = $ruser->get('username');
	}

	$status = '';
	$pending = Lang::txt('COM_SERVICES_FOR_UNITS', $row->currency . ' ' . $row->pendingpayment, $row->pendingunits);

	$expires = (intval($row->expires) <> 0) ? Date::of($row->expires)->toLocal(Lang::txt('DATE_FORMAT_HZ1')) : Lang::txt('COM_SERVICES_NOT_APPLICABLE');

	switch ($row->status)
	{
		case '1':
			$status = ($row->expires > $now) ? '<span style="color:#197f11;">' . strtolower(Lang::txt('COM_SERVICES_STATE_ACTIVE')) . '</span>' : '<span style="color:#ef721e;">' . strtolower(Lang::txt('COM_SERVICES_EXPIRED')) . '</span>';
			break;
		case '0':
			$status = '<span style="color:#ff0000;">' . strtolower(Lang::txt('COM_SERVICES_STATE_PENDING')) . '</span>';
			break;
		case '2':
			$status = '<span style="color:#999;">' . strtolower(Lang::txt('COM_SERVICES_STATE_CANCELED')) . '</span>';
			$pending .= $row->pendingpayment ? ' (' . Lang::txt('COM_SERVICES_REFUND') . ')' : '';
			break;
	}
?>
			<tr class="<?php echo "row$k"; ?>">
				<td><a href="<?php echo Route::url('index.php?option=' . $this->option  . '&controller=' . $this->controller . '&task=edit&id=' . $row->id); ?>" title="<?php echo Lang::txt('COM_SERVICES_VIEW_SUBSCRIPTION_DETAILS'); ?>"><?php echo $row->id . ' -- ' . $row->code; ?></a></td>
				<td><?php echo $status;  ?></td>
				<td>
					<a href="<?php echo Route::url('index.php?option=' . $this->option  . '&controller=' . $this->controller . '&task=edit&id=' . $row->id); ?>" title="<?php echo Lang::txt('COM_SERVICES_VIEW_SUBSCRIPTION_DETAILS'); ?>">
						<span><?php echo $this->escape($row->category) . ' -- ' . $this->escape($row->title); ?></span>
					</a>
				</td>
				<td><?php echo $row->pendingpayment && ($row->pendingpayment > 0 or $row->pendingunits > 0)  ? '<span style="color:#ff0000;">' . $pending . '</span>' : $pending; ?></td>
				<td><?php echo $name . ' (' . $login . ')'; ?></td>
				<td><?php echo Date::of($row->added)->toLocal(Lang::txt('DATE_FORMAT_HZ1')); ?></td>
				<td><?php echo $row->updated ? Date::of($row->updated)->toLocal(Lang::txt('DATE_FORMAT_HZ1')) : Lang::txt('COM_SERVICES_NEVER'); ?></td>
				<td><?php echo $expires; ?></td>
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

	<?php echo JHTML::_('form.token'); ?>
</form>
