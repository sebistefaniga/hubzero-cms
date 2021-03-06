<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	com_templates
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task)
	{
		if (task == 'source.cancel' || document.formvalidator.isValid($('#item-form'))) {
			<?php echo $this->form->getField('source')->save(); ?>
			Joomla.submitform(task, document.getElementById('item-form'));
		} else {
			alert('<?php echo $this->escape(Lang::txt('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>

<form action="<?php echo Route::url('index.php?option=com_templates&layout=edit'); ?>" method="post" name="adminForm" id="item-form" class="form-validate">
	<?php if ($this->ftp) : ?>
		<?php echo $this->loadTemplate('ftp'); ?>
	<?php endif; ?>

	<fieldset class="adminform">
		<legend><?php echo Lang::txt('COM_TEMPLATES_TEMPLATE_FILENAME', $this->source->filename, $this->template->element); ?></legend>

		<?php echo $this->form->getLabel('source'); ?>
		<div class="clr"></div>

		<div class="editor-border">
			<?php echo $this->form->getInput('source'); ?>
		</div>

		<input type="hidden" name="task" value="" />
		<?php echo JHtml::_('form.token'); ?>
	</fieldset>

	<?php echo $this->form->getInput('extension_id'); ?>
	<?php echo $this->form->getInput('filename'); ?>
</form>
