<?php
/**
 * @package     hubzero-cms
 * @author      Christopher Smoak <csmoak@purdue.edu>
 * @copyright   Copyright 2005-2011 Purdue University. All rights reserved.
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
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
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

//get the database object
$database = JFactory::getDBO();

$this->css()
     ->js();
?>

<form action="<?php echo Route::url($this->member->getLink() . '&active=messages&task=archive'); ?>" method="post">

	<div id="filters">
		<input type="hidden" name="inaction" value="archive" />
		<?php echo Lang::txt('PLG_MEMBERS_MESSAGES_FROM'); ?>
		<select class="option" name="filter">
			<option value=""><?php echo Lang::txt('PLG_MEMBERS_MESSAGES_ALL'); ?></option>
			<?php
				if ($this->components) {
					foreach ($this->components as $component)
					{
						$component = substr($component, 4);
						$sbjt  = "\t\t\t".'<option value="'.$component.'"';
						$sbjt .= ($component == $this->filter) ? ' selected="selected"' : '';
						$sbjt .= '>'.$component.'</option>'."\n";
						echo $sbjt;
					}
				}
			?>
		</select>
		<input class="option" type="submit" value="<?php echo Lang::txt('PLG_MEMBERS_MESSAGES_FILTER'); ?>" />
	</div>

	<div id="actions">
		<select class="option" name="action">
			<option value=""><?php echo Lang::txt('PLG_MEMBERS_MESSAGES_MSG_WITH_SELECTED'); ?></option>
			<option value="sendtoinbox"><?php echo Lang::txt('PLG_MEMBERS_MESSAGES_MSG_SEND_TO_INBOX'); ?></option>
			<option value="sendtotrash"><?php echo Lang::txt('PLG_MEMBERS_MESSAGES_MSG_SEND_TO_TRASH'); ?></option>
		</select>
		<input type="hidden"name="activetab" value="archive" />
		<input class="option" type="submit" value="<?php echo Lang::txt('PLG_MEMBERS_MESSAGES_MSG_APPLY'); ?>" />
	</div>
	<br class="clear" />

	<table class="data">
		<thead>
			<tr>
				<th scope="col"><input type="checkbox" name="msgall" id="msgall" value="all" /></th>
				<th scope="col"> </th>
				<th scope="col"><?php echo Lang::txt('PLG_MEMBERS_MESSAGES_SUBJECT'); ?></th>
				<th scope="col"><?php echo Lang::txt('PLG_MEMBERS_MESSAGES_FROM'); ?></th>
				<th scope="col"><?php echo Lang::txt('PLG_MEMBERS_MESSAGES_DATE_RECEIVED'); ?></th>
				<th scope="col"> </th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="6">
					<?php echo $this->pagenavhtml; ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php if ($this->rows) : ?>
				<?php foreach ($this->rows as $row) : ?>
					<?php
						$check = "<input class=\"chkbox\" type=\"checkbox\" id=\"msg{$row->id}\" value=\"{$row->id}\" name=\"mid[]\" />";

						//get the message status
						$status = ($row->whenseen != '' && $row->whenseen != '0000-00-00 00:00:00') ? '<span class="read">read</span>' : '<span class="unread">unread</span>';

						//get the component that created message
						$component = (substr($row->component, 0, 4) == 'com_') ? substr($row->component, 4) : $row->component;

						//url to view message
						$url = Route::url($this->member->getLink() . '&active=messages&msg=' . $row->id);

						//get the message subject
						$subject = $row->subject;

						//support - special
						if ($component == 'support')
						{
							$fg = explode(' ', $row->subject);
							$fh = array_pop($fg);
							$subject = implode(' ', $fg);
						}

						//get the message
						$preview = ($row->message) ? "<h3>Message Preview:</h3>" . nl2br(stripslashes($row->message)) : "";

						//subject link
						$subject_cls = "message-link";
						$subject_cls .= ($row->whenseen != '' && $row->whenseen != '0000-00-00 00:00:00') ? "" : " unread";

						$subject  = "<a class=\"{$subject_cls}\" href=\"{$url}\">{$subject}";
						//$subject .= "<div class=\"preview\"><span>" . $preview . "</span></div>";
						$subject .= "</a>";

						//get who the message is from
						if (substr($row->type, -8) == '_message')
						{
							$u = JUser::getInstance($row->created_by);
							$from = '<a href="' . Route::url('index.php?option='.$this->option.'&id='.$u->get('id')) . '">' . $u->get('name') . '</a>';
						}
						else
						{
							$from = Lang::txt('PLG_MEMBERS_MESSAGES_SYSTEM', $component);
						}

						//date received
						$date = JHTML::_('date', $row->created, Lang::txt('DATE_FORMAT_HZ1'));

						//delete link
						$del_link = Route::url($this->member->getLink() . '&active=messages&mid[]=' . $row->id . '&action=sendtotrash&activetab=archive');
						$delete = '<a title="' . Lang::txt('PLG_MEMBERS_MESSAGES_REMOVE_MESSAGE') . '" class="trash" href="' . $del_link . '">' . Lang::txt('PLG_MEMBERS_MESSAGES_TRASH') . '</a>';

						//special action
						/*if ($row->actionid)
						{
							$xma = new \Hubzero\Message\Action( $database );
							$xma->load( $row->actionid );
							if ($xma)
							{
								$url = Route::url(stripslashes($xma->description));
							}

							if ($row->whenseen == '' || $row->whenseen == '0000-00-00 00:00:00')
							{
								//we dont want them to be able to move
								$check = "";

								//we dont want them to be able to delete
								$delete = "";
							}
						}*/
					?>

					<tr<?php /*if ($row->actionid) { echo ' class="actionitem"'; }*/ ?>>
						<td class="check"><?php echo $check; ?></td>
						<td class="status"><?php echo $status; ?></td>
						<td><?php echo $subject; ?></td>
						<td><?php echo $from; ?></td>
						<td><?php echo $date; ?></td>
						<td><?php echo $delete; ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else: ?>
				<tr>
					<td colspan="6"><?php echo Lang::txt('PLG_MEMBERS_MESSAGES_NONE'); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</form>
