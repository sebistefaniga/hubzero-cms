<?php
/**
 * @package		HUBzero CMS
 * @author		Alissa Nedossekina <alisa@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

?>
<div id="abox-content">
<?php if ($this->ajax) { ?>
<h3><?php echo Lang::txt('PLG_PROJECTS_TEAM_EDIT_AUTHORS_ACCESS'); ?></h3>
<?php } ?>

<?php if (!$this->ajax) { ?>
<form action="<?php echo $this->url; ?>" method="post" id="plg-form" >
	<?php if ($this->model->isProvisioned()) { ?>
		<h3 class="prov-header"><a href="<?php echo $this->route; ?>"><?php echo ucfirst(Lang::txt('PLG_PROJECTS_TEAM_MY_SUBMISSIONS')); ?></a> &raquo; <a href="<?php echo $this->url . '?version=' . $this->version; ?>">"<?php echo $this->pub->title; ?>"</a> &raquo; <?php echo ucfirst(Lang::txt('PLG_PROJECTS_TEAM_EDIT_AUTHORS_TEAM')); ?></h3>
	<?php } else { ?>
		<h3 class="publications"><a href="<?php echo $this->route; ?>"><?php echo ucfirst(Lang::txt('PLG_PROJECTS_TEAM_PUBLICATIONS')); ?></a> &raquo; <span class="restype indlist"><?php echo $typetitle; ?></span> <span class="indlist"><a href="<?php echo $this->url; ?>">"<?php echo $this->pub->title; ?>"</a></span> <span class="indlist"> &raquo; <?php echo ucfirst(Lang::txt('PLG_PROJECTS_TEAM_EDIT_AUTHORS_TEAM')); ?></span>
		</h3>
	<?php }
}
else
{ ?>
<form id="hubForm-ajax" method="post" action="<?php echo $this->url; ?>">
<?php } ?>
<fieldset>
	<input type="hidden" name="id" value="<?php echo $this->model->get('id'); ?>" id="projectid" />
	<input type="hidden" name="active" value="team" />
	<input type="hidden" name="action" value="saveauthors" />
	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="version" value="<?php echo $this->version; ?>" />
	<input type="hidden" name="pid" id="pid" value="<?php echo $this->pub->id; ?>" />
	<input type="hidden" name="provisioned" id="provisioned" value="<?php echo $this->model->isProvisioned() ? 1 : 0; ?>" />
	<?php if ($this->model->isProvisioned()) { ?>
	<input type="hidden" name="task" value="submit" />
	<?php } ?>
</fieldset>
<div id="author-access">
	<p><?php echo Lang::txt('PLG_PROJECTS_TEAM_PUB_AUTHOR_ACCESS_TIPS'); ?></p>
	<table class="listing">
		<thead>
			<tr>
				<th class="th_image"></th>
				<th class="th_user"><?php echo Lang::txt('PLG_PROJECTS_TEAM_PUB_AUTHOR_MEMBER_NAME'); ?></th>
				<th class="checkbox"><?php echo Lang::txt('PLG_PROJECTS_TEAM_PUB_AUTHOR_FULL_ACCESS'); ?></th>
				<th class="checkbox"><?php echo Lang::txt('PLG_PROJECTS_TEAM_PUB_AUTHOR_DELETE'); ?></th>
			</tr>
		</thead>
		<tbody>
<?php foreach ($this->team as $owner)
	{
					$profile = \Hubzero\User\Profile::getInstance($owner->userid);
					$actor   = \Hubzero\User\Profile::getInstance($this->uid);
					$thumb   = $profile ? $profile->getPicture() : $actor->getPicture(true);

					// Determine css class for user
					$username 	= $owner->username ? $owner->username : $owner->invited_email;
					$creator 	= $this->model->get('owned_by_user') == $owner->userid ? 1 : 0;
					$usr_class 	= $creator ? ' class="usercreator"' : '';
?>
			<tr class="mline" id="tr_<?php echo $owner->id; ?>">
				<td <?php echo $usr_class; ?>><img width="30" height="30" src="<?php echo $thumb; ?>" alt="<?php echo $owner->fullname; ?>" /></td>
				<td><?php echo $owner->fullname; ?><span class="block mini short prominent"><?php echo $username; ?></span></td>
				<td><input class="option" name="role_<?php echo $owner->id; ?>" type="radio" value="<?php echo $owner->role == 1 ? 1 : 2; ?>" <?php if ($owner->role == 1 || $owner->role == 0 || $owner->role == 2) { echo 'checked="checked"'; } ?> <?php if ($creator) { echo 'disabled="disabled"'; } ?> /></td>
				<td><input class="option" name="role_<?php echo $owner->id; ?>" type="radio" value="9" <?php if ($creator) { echo 'disabled="disabled"'; } ?> /></td>
			</tr>
<?php } ?>
			</tbody>
			</table>
			<p class="submitarea">
				<input type="submit" class="btn" value="<?php echo Lang::txt('PLG_PROJECTS_TEAM_SAVE_MY_CHANGES'); ?>" />
			</p>
		</div>
</form>
</div>