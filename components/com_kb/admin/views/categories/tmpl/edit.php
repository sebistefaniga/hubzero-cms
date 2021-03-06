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

$canDo = \Components\Kb\Admin\Helpers\Permissions::getActions('category');

$text = ($this->task == 'edit' ? Lang::txt('COM_KB_EDIT') : Lang::txt('COM_KB_NEW'));

Toolbar::title(Lang::txt('COM_KB') . ': ' . Lang::txt('COM_KB_CATEGORY') . ': ' . $text, 'kb.png');
if ($canDo->get('core.edit'))
{
	Toolbar::apply();
	Toolbar::save();
}
Toolbar::cancel();
Toolbar::spacer();
Toolbar::help('category');

?>
<script type="text/javascript">
function submitbutton(pressbutton)
{
	if (pressbutton =='resethits') {
		if (confirm("<?php echo Lang::txt('COM_KB_RESET_HITS_WARNING'); ?>")){
			submitform(pressbutton);
			return;
		} else {
			return;
		}
	}

	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}

	// do field validation
	if (document.getElementById('field-title').value == ''){
		alert("<?php echo Lang::txt('COM_KB_ERROR_MISSING_TITLE'); ?>");
	} else {
		submitform(pressbutton);
	}
}
</script>

<form action="<?php echo Route::url('index.php?option=' . $this->option  . '&controller=' . $this->controller); ?>" method="post" name="adminForm" id="item-form">
	<div class="col width-60 fltlft">
		<fieldset class="adminform">
			<legend><span><?php echo Lang::txt('COM_KB_DETAILS'); ?></span></legend>

			<div class="input-wrap">
				<label for="field-section"><?php echo Lang::txt('COM_KB_PARENT_CATEGORY'); ?>:</label><br />
				<select name="fields[section]" id="field-section">
					<option value="0"><?php echo Lang::txt('COM_KB_SELECT_CATEGORY'); ?></option>
					<?php foreach ($this->sections as $section) { ?>
						<?php if ($section->get('id') == $this->row->get('id')) { continue; } ?>
						<option value="<?php echo $section->get('id'); ?>" <?php if ($this->row->get('section') == $section->get('id')) { echo ' selected="selected"'; } ?>><?php echo $this->escape(stripslashes($section->get('title'))); ?></option>
					<?php } ?>
				</select>
			</div>
			<div class="input-wrap">
				<label for="field-title"><?php echo Lang::txt('COM_KB_TITLE'); ?>: <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label><br />
				<input type="text" name="fields[title]" id="field-title" size="30" maxlength="100" value="<?php echo $this->escape(stripslashes($this->row->get('title'))); ?>" />
			</div>
			<div class="input-wrap" data-hint="<?php echo Lang::txt('COM_KB_ALIAS_HINT'); ?>">
				<label for="field-alias"><?php echo Lang::txt('COM_KB_ALIAS'); ?>:</label><br />
				<input type="text" name="fields[alias]" id="field-alias" size="30" maxlength="100" value="<?php echo $this->escape(stripslashes($this->row->get('alias'))); ?>" />
				<span class="hint"><?php echo Lang::txt('COM_KB_ALIAS_HINT'); ?></span>
			</div>
			<div class="input-wrap">
				<label for="field-description"><?php echo Lang::txt('COM_KB_DESCRIPTION'); ?>: <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label><br />
				<?php echo JFactory::getEditor()->display('fields[description]', $this->escape(stripslashes($this->row->get('description'))), '', '', 50, 10, false, 'field-description'); ?>
			</div>
		</fieldset>
	</div>
	<div class="col width-40 fltrt">
		<table class="meta">
			<tbody>
				<tr>
					<th class="key"><?php echo Lang::txt('COM_KB_ID'); ?>:</th>
					<td>
						<?php echo $this->row->get('id'); ?>
						<input type="hidden" name="fields[id]" id="field-id" value="<?php echo $this->escape($this->row->get('id')); ?>" />
					</td>
				</tr>
			</tbody>
		</table>

		<fieldset class="adminform">
			<legend><span><?php echo Lang::txt('COM_KB_PARAMETERS'); ?></span></legend>

			<div class="input-wrap">
				<label for="field-state"><?php echo Lang::txt('COM_KB_PUBLISH'); ?>:</label>
				<select name="fields[state]" id="field-state">
					<option value="0"<?php if ($this->row->get('state') == 0) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JUNPUBLISHED'); ?></option>
					<option value="1"<?php if ($this->row->get('state') == 1) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JPUBLISHED'); ?></option>
					<option value="2"<?php if ($this->row->get('state') == 2) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('JTRASHED'); ?></option>
				</select>
			</div>
			<div class="input-wrap">
				<label for="field-access"><?php echo Lang::txt('COM_KB_ACCESS_LEVEL'); ?>:</label>
				<select name="fields[access]" id="field-access">
					<?php echo JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->row->get('access')); ?>
				</select>
			</div>
		</fieldset>
	</div>
	<div class="clr"></div>

	<?php /*
		<?php if ($canDo->get('core.admin')): ?>
			<div class="col width-100 fltlft">
				<fieldset class="panelform">
					<?php echo $this->form->getLabel('rules'); ?>
					<?php echo $this->form->getInput('rules'); ?>
				</fieldset>
			</div>
			<div class="clr"></div>
		<?php endif; ?>
	*/ ?>

	<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
	<input type="hidden" name="task" value="save" />

	<?php echo JHTML::_('form.token'); ?>
</form>
