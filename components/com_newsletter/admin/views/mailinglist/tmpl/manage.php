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
 * @author    Christopher Smoak <csmoak@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

Toolbar::title(Lang::txt('COM_NEWSLETTER_NEWSLETTER_MAILINGLISTS') . ': ' . $this->list->name, 'list.png');
Toolbar::addNew('addemail', 'COM_NEWSLETTER_TOOLBAR_ADDEMAILS');
Toolbar::deleteList('COM_NEWSLETTER_MAILINGLIST_DELETE_EMAILS_CHECK', 'deleteemail', 'COM_NEWSLETTER_TOOLBAR_REMOVE');
Toolbar::spacer();
Toolbar::custom('export', 'export', '', 'COM_NEWSLETTER_TOOLBAR_EXPORT', false);
Toolbar::spacer();
Toolbar::cancel();
?>

<?php
	if ($this->getError())
	{
		echo '<p class="error">' . $this->getError() . '</p>';
	}
?>

<form action="<?php echo Route::url('index.php?option=' . $this->option); ?>" method="post" name="adminForm" id="adminForm">
	<fieldset id="filter-bar">
		<label><?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_STATUS'); ?>:</label>
		<select name="status">
			<option value="all" <?php if ($this->filters['status'] == 'all') { echo 'selected="selected"'; } ?>><?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_STATUS_ALL'); ?></option>
			<option value="active" <?php if ($this->filters['status'] == 'active') { echo 'selected="selected"'; } ?>><?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_STATUS_ACTIVE'); ?></option>
			<option value="removed" <?php if ($this->filters['status'] == 'removed') { echo 'selected="selected"'; } ?>><?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_STATUS_REMOVED'); ?></option>
			<option value="unsubscribed" <?php if ($this->filters['status'] == 'unsubscribed') { echo 'selected="selected"'; } ?>><?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_STATUS_UNSUBSCRIBED'); ?></option>
			<option value="inactive" <?php if ($this->filters['status'] == 'inactive') { echo 'selected="selected"'; } ?>><?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_STATUS_INACTIVE'); ?></option>
		</select>
		<input type="submit" value="Go" onclick="javascript:submitbutton('manage');" />
	</fieldset>
	<table class="adminlist">
		<thead>
			<tr>
				<th><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count($this->list_emails); ?>);" /></th>
				<th>
					<?php if ($this->filters['sort'] == 'email ASC') : ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=mailinglist&task=manage&id=' . $this->list->id . '&status=' . $this->filters['status'] . '&sort=email DESC'); ?>">
							<?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_EMAIL') . ' &uarr;'; ?>
						</a>
					<?php else : ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=mailinglist&task=manage&id=' . $this->list->id . '&status=' . $this->filters['status'] . '&sort=email ASC'); ?>">
							<?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_EMAIL'); ?>
							<?php echo ($this->filters['sort'] == 'email DESC') ? ' &darr;' : ''; ?>
						</a>
					<?php endif; ?>
				</th>
				<th><?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_STATUS'); ?></th>
				<th><?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_CONFIRMED'); ?></th>
				<th>
					<?php if ($this->filters['sort'] == 'date_added ASC') : ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=mailinglist&task=manage&id=' . $this->list->id . '&status=' . $this->filters['status'] . '&sort=date_added DESC'); ?>">
							<?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_DATE_ADDED') . ' &uarr;'; ?>
						</a>
					<?php else : ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=mailinglist&task=manage&id=' . $this->list->id . '&status=' . $this->filters['status'] . '&sort=date_added ASC'); ?>">
							<?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_DATE_ADDED'); ?>
							<?php echo ($this->filters['sort'] == 'date_added DESC') ? ' &darr;' : ''; ?>
						</a>
					<?php endif; ?>
				</th>
				<th>
					<?php if ($this->filters['sort'] == 'date_confirmed ASC') : ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=mailinglist&task=manage&id=' . $this->list->id . '&status=' . $this->filters['status'] . '&sort=date_confirmed DESC'); ?>">
							<?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_DATE_CONFIRMED') . ' &uarr;'; ?>
						</a>
					<?php else : ?>
						<a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=mailinglist&task=manage&id=' . $this->list->id . '&status=' . $this->filters['status'] . '&sort=date_confirmed ASC'); ?>">
							<?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_DATE_CONFIRMED'); ?>
							<?php echo ($this->filters['sort'] == 'date_confirmed DESC') ? ' &darr;' : ''; ?>
						</a>
					<?php endif; ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php if (count($this->list_emails) > 0) : ?>
				<?php foreach ($this->list_emails as $k => $le) : ?>
					<tr>
						<td width="30">
							<input type="checkbox" name="email_id[]" id="cb<?php echo $k;?>" value="<?php echo $le->id; ?>" onclick="isChecked(this.checked);" />
						</td>
						<td>
							<a href="mailto:<?php echo $le->email; ?>"><?php echo $le->email; ?></a>
							<?php
								if ($le->unsubscribe_reason)
								{
									echo '<p><strong>' . Lang::txt('COM_NEWSLETTER_MAILINGLIST_MANAGE_UNSUBSCRIBE_REASON') . '</strong> ' . $le->unsubscribe_reason . '</p>';
								}
							?>
						</td>
						<td>
							<?php echo ucfirst($le->status); ?>
						</td>
						<td>
							<?php
								if ($le->confirmed)
								{
									echo Lang::txt('JYES');
								}
								else
								{
									$resendLink = Route::url('index.php?option=com_newsletter&controller=mailinglist&task=sendconfirmation&id='.$le->id.'&mid='.$this->list->id);
									echo Lang::txt('JNO') . '(<a href="'.$resendLink.'">' . Lang::txt('Send Confirmation') . '</a>)';
								}
							?>
						</td>
						<td>
							<?php echo Date::of($le->date_added)->format('l, F d, Y @ g:ia'); ?>
						</td>
						<td>
							<?php
								if ($le->date_confirmed && $le->date_confirmed != '0000-00-00 00:00:00')
								{
									echo Date::of($le->date_confirmed)->format('l, F d, Y @ g:ia');
								}
								else
								{
									echo Lang::txt('NA');
								}
							 ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6">
						<?php echo Lang::txt('COM_NEWSLETTER_MAILINGLIST_NO_EMAILS',"javascript:submitbutton('addemail');"); ?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<input type="hidden" name="option" value="com_newsletter" />
	<input type="hidden" name="controller" value="mailinglist" />
	<input type="hidden" name="task" value="manage" />
	<input type="hidden" name="id[]" value="<?php echo $this->list->id; ?>" />
	<input type="hidden" name="boxchecked" value="0" />

	<?php echo JHTML::_('form.token'); ?>
</form>