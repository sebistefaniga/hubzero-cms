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
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$canDo = \Components\Blog\Admin\Helpers\Permissions::getActions('entry');

$text = ($this->task == 'edit' ? Lang::txt('JACTION_EDIT') : Lang::txt('JACTION_CREATE'));

Toolbar::title(Lang::txt('COM_BLOG_TITLE') . ': ' . Lang::txt('COM_BLOG_COL_COMMENTS') . ': ' . $text, 'blog.png');
if ($canDo->get('core.edit'))
{
	Toolbar::apply();
	Toolbar::save();
	Toolbar::spacer();
}
Toolbar::cancel();
Toolbar::spacer();
Toolbar::help('comment');
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
	if ($('#field-content').val() == ''){
		alert("<?php echo Lang::txt('COM_BLOG_ERROR_MISSING_CONTENT'); ?>");
	} else {
		<?php echo JFactory::getEditor()->save('text'); ?>

		submitform(pressbutton);
	}
}
</script>

<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" method="post" name="adminForm" class="editform" id="item-form">
	<div class="col width-60 fltlft">
		<fieldset class="adminform">
			<legend><span><?php echo Lang::txt('JDETAILS'); ?></span></legend>

			<div class="input-wrap" data-hint="<?php echo Lang::txt('COM_BLOG_FIELD_ANONYMOUS_HINT'); ?>">
				<input class="option" type="checkbox" name="fields[anonymous]" id="field-anonymous" value="1"<?php if ($this->row->get('anonymous')) { echo ' checked="checked"'; } ?> />
				<label for="field-anonymous"><?php echo Lang::txt('COM_BLOG_FIELD_ANONYMOUS'); ?></label>
			</div>

			<div class="input-wrap">
				<label for="field-content"><?php echo Lang::txt('COM_BLOG_FIELD_CONTENT'); ?> <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label><br />
				<?php echo $this->editor('fields[content]', $this->escape($this->row->content('raw')), 50, 15, 'field-content', array('class' => 'minimal no-footer')); ?>
			</div>
		</fieldset>
	</div>
	<div class="col width-40 fltrt">
		<table class="meta">
			<tbody>
				<tr>
					<th><?php echo Lang::txt('COM_BLOG_FIELD_CREATOR'); ?>:</th>
					<td>
						<?php
						$editor = User::getInstance($this->row->get('created_by'));
						echo $this->escape($editor->get('name'));
						?>
						<input type="hidden" name="fields[created_by]" id="field-created_by" value="<?php echo $this->escape($this->row->get('created_by')); ?>" />
					</td>
				</tr>
				<tr>
					<th><?php echo Lang::txt('COM_BLOG_FIELD_CREATED'); ?>:</th>
					<td>
						<?php echo $this->row->get('created'); ?>
						<input type="hidden" name="fields[created]" id="field-created" value="<?php echo $this->escape($this->row->get('created')); ?>" />
					</td>
				</tr>
				<tr>
					<th><?php echo Lang::txt('COM_BLOG_FIELD_ENTRY'); ?>:</th>
					<td>
						<?php echo $this->row->get('entry_id'); ?>
						<input type="hidden" name="fields[entry_id]" id="field-entry_id" value="<?php echo $this->escape($this->row->get('entry_id')); ?>" />
						<input type="hidden" name="entry_id" value="<?php echo $this->escape($this->row->get('entry_id')); ?>" />
					</td>
				</tr>
			</tbody>
		</table>

		<fieldset class="adminform">
			<legend><span><?php echo Lang::txt('JGLOBAL_FIELDSET_PUBLISHING'); ?></span></legend>

			<div class="input-wrap">
				<label for="field-state"><?php echo Lang::txt('COM_BLOG_FIELD_STATE'); ?>:</label><br />
				<select name="fields[state]" id="field-state">
					<option value="0"<?php if ($this->row->get('state') == 0) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JUNPUBLISHED'); ?></option>
					<option value="1"<?php if ($this->row->get('state') == 1) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JPUBLISHED'); ?></option>
					<option value="2"<?php if ($this->row->get('state') == 2) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JTRASHED'); ?></option>
					<option value="3"<?php if ($this->row->get('state') == 3) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_BLOG_FIELD_STATE_FLAGGED'); ?></option>
				</select>
			</div>
		</fieldset>
	</div>
	<div class="clr"></div>

	<input type="hidden" name="fields[parent]" value="<?php echo $this->row->get('parent'); ?>" />
	<input type="hidden" name="fields[id]" value="<?php echo $this->row->get('id'); ?>" />
	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
	<input type="hidden" name="task" value="save" />

	<?php echo JHTML::_('form.token'); ?>
</form>