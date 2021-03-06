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
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$base = 'index.php?option=com_groups&cn=' . $this->group->get('cn') . '&active=blog';

$this->css()
     ->js();
?>
<ul id="page_options">
	<li>
		<a class="icon-archive archive btn" href="<?php echo Route::url($base); ?>">
			<?php echo Lang::txt('PLG_GROUPS_BLOG_ARCHIVE'); ?>
		</a>
	</li>
</ul>

<?php if ($this->getError()) { ?>
	<p class="error"><?php echo $this->getError(); ?></p>
<?php } ?>
<?php if ($this->message) { ?>
	<p class="passed"><?php echo $this->message; ?></p>
<?php } ?>
	<form action="<?php echo Route::url($base . '&action=savesettings'); ?>" method="post" id="hubForm" class="full">
		<fieldset class="settings">
			<legend><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_POSTS'); ?></legend>
			<p><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_POSTS_EXPLANATION'); ?></p>
		</fieldset>
		<fieldset class="settings">
			<legend><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_ENTRIES'); ?></legend>

			<label for="param-posting">
				<?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_ENTRY_POST'); ?>
				<select name="params[posting]" id="param-posting">
					<option value="0"<?php if (!$this->config->get('posting', 0)) { echo ' selected="selected"'; }?>><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_ENTRY_POST_ALL'); ?></option>
					<option value="1"<?php if ($this->config->get('posting', 0) == 1) { echo ' selected="selected"'; }?>><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_ENTRY_POST_MANAGERS'); ?></option>
				</select>
			</label>

			<p class="help">
				<?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_ENTRY_POST_HELP'); ?>
			</p>
		</fieldset>
		<fieldset class="settings">
			<legend><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_FEEDS'); ?></legend>

			<label for="param-feeds_enabled">
				<?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_ENTRY_FEED'); ?>
				<select name="params[feeds_enabled]" id="param-feeds_enabled">
					<option value="0"<?php if (!$this->config->get('feeds_enabled', 1)) { echo ' selected="selected"'; }?>><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_DISABLED'); ?></option>
					<option value="1"<?php if ($this->config->get('feeds_enabled', 1) == 1) { echo ' selected="selected"'; }?>><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_ENABLED'); ?></option>
				</select>
			</label>

			<label for="param-feeds_entries">
				<?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_FEED_ENTRY_LENGTH'); ?>
				<select name="params[feed_entries]" id="param-feeds_entries">
					<option value="full"<?php if ($this->config->get('feed_entries', 'partial') == 'full') { echo ' selected="selected"'; }?>><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_FULL'); ?></option>
					<option value="partial"<?php if ($this->config->get('feed_entries', 'partial') == 'partial') { echo ' selected="selected"'; }?>><?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_PARTIAL'); ?></option>
				</select>
			</label>

			<p class="help">
				<?php echo Lang::txt('PLG_GROUPS_BLOG_SETTINGS_FEED_HELP'); ?>
			</p>

			<input type="hidden" name="settings[id]" value="<?php echo $this->settings->id; ?>" />
			<input type="hidden" name="settings[object_id]" value="<?php echo $this->group->get('gidNumber'); ?>" />
			<input type="hidden" name="settings[folder]" value="groups" />
			<input type="hidden" name="settings[element]" value="blog" />
		</fieldset>
		<div class="clear"></div>

		<input type="hidden" name="cn" value="<?php echo $this->group->get('cn'); ?>" />
		<input type="hidden" name="process" value="1" />
		<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
		<input type="hidden" name="active" value="blog" />
		<input type="hidden" name="action" value="savesettings" />

		<p class="submit">
			<input class="btn btn-success" type="submit" value="<?php echo Lang::txt('PLG_GROUPS_BLOG_SAVE'); ?>" />

			<a class="btn btn-secondary" href="<?php echo Route::url($base); ?>">
				<?php echo Lang::txt('PLG_GROUPS_BLOG_CANCEL'); ?>
			</a>
		</p>
	</form>
