<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2014 Purdue University. All rights reserved.
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
 * @copyright Copyright 2005-2014 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

defined('_JEXEC') or die('Restricted access');

$this->css();

//get whos online summary
$siteUserCount  = 0;
$adminUserCount = 0;
foreach ($this->rows as $row)
{
	if ($row->client_id == 0)
	{
		$siteUserCount++;
	}
	else
	{
		$adminUserCount++;
	}
}
?>

<div class="<?php echo $this->module->module; ?>" id="<?php echo $this->module->module . $this->module->id; ?>">
	<table class="whosonline-summary">
		<thead>
			<tr>
				<th scope="col"><?php echo Lang::txt('MOD_WHOSONLINE_COL_SITE'); ?></th>
				<th scope="col"><?php echo Lang::txt('MOD_WHOSONLINE_COL_ADMIN'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="front-end"><?php echo $siteUserCount; ?></td>
				<td class="back-end"><?php echo $adminUserCount; ?></td>
			</tr>
		</tbody>
	</table>

	<table class="adminlist whosonline-list">
		<thead>
			<tr>
				<th scope="col"><?php echo Lang::txt('MOD_WHOSONLINE_COL_USER'); ?></td>
				<th scope="col"><?php echo Lang::txt('MOD_WHOSONLINE_COL_LOCATION'); ?></th>
				<th scope="col"><?php echo Lang::txt('MOD_WHOSONLINE_COL_ACTIVITY'); ?></th>
				<th scope="col"><?php echo Lang::txt('MOD_WHOSONLINE_COL_LOGOUT'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (count($this->rows) > 0) : ?>
				<?php foreach ($this->rows as $k => $row) : ?>
					<?php if (($k+1) <= $this->params->get('display_limit', 25)) : ?>
						<tr>
							<td>
								<?php
									// Get user object
									$user = User::getInstance($row->username);

									// Display link if we are authorized
									if ($editAuthorized = User::authorize('com_users', 'manage'))
									{
										echo '<a href="' . Route::url('index.php?option=com_users&task=edit&cid[]='. $row->userid) . '" title="' . Lang::txt('MOD_WHOSONLINE_EDIT_USER') . '">' . $this->escape($user->get('name')) . ' [' . $this->escape($user->get('username')) . ']' . '</a>';
									}
									else
									{
										echo $this->escape($juser->get('name')) . ' [' . $this->escape($juser->get('username')) . ']';
									}
								?>
							</td>
							<td>
								<?php
									$clientInfo = JApplicationHelper::getClientInfo($row->client_id);
									echo ucfirst($clientInfo->name);
								?>
							</td>
							<td>
								<?php echo Lang::txt('MOD_WHOSONLINE_HOURS_AGO', (time() - $row->time)/3600.0); ?>
							</td>
							<td>
								<?php if ($editAuthorized) { ?>
									<a class="force-logout" href="<?php echo Route::url('index.php?option=com_login&task=logout&uid=' . $row->userid .'&'. JSession::getFormToken() .'=1'); ?>">
										<?php echo JHtml::_('image', 'mod_logged/icon-16-logout.png', Lang::txt('JLOGOUT'), null, true);?>
									</a>
								<?php } ?>
							</td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
				<tr>
					<td colspan="4" class="view-all">
						<a href="<?php echo Route::url('index.php?option=com_members&controller=whosonline'); ?>"><?php echo Lang::txt('MOD_WHOSONLINE_VIEW_ALL'); ?></a>
					</td>
				</tr>
			<?php else : ?>
				<tr>
					<td colspan="4">
						<?php echo Lang::txt('MOD_WHOSONLINE_NO_RESULTS'); ?>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
