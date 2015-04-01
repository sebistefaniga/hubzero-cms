<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	com_installer
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * @since		2.5
 */

// no direct access
defined('_JEXEC') or die;


?>
<div id="installer-database">
	<form action="<?php echo Route::url('index.php?option=com_installer&view=warnings');?>" method="post" name="adminForm" id="adminForm">
		<?php if ($this->errorCount === 0) : ?>
			<p class="nowarning"><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_OK'); ?></p>
			<?php echo JHtml::_('sliders.start', 'database-sliders', array('useCookie'=>1)); ?>

		<?php else : ?>
			<p class="warning"><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_ERRORS'); ?></p>
			<?php echo JHtml::_('sliders.start', 'database-sliders', array('useCookie'=>1)); ?>

			<?php $panelName = Lang::txts('COM_INSTALLER_MSG_N_DATABASE_ERROR_PANEL', $this->errorCount); ?>
			<?php echo JHtml::_('sliders.panel', $panelName, 'error-panel'); ?>
			<fieldset class="panelform">
				<ul>
					<?php if (!$this->filterParams) : ?>
						<li><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_FILTER_ERROR'); ?>
					<?php endif; ?>

					<?php if ($this->schemaVersion != $this->changeSet->getSchema()) : ?>
						<li><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_SCHEMA_ERROR', $this->schemaVersion, $this->changeSet->getSchema()); ?></li>
					<?php endif; ?>

					<?php if (version_compare($this->updateVersion, JVERSION) != 0) : ?>
						<li><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_UPDATEVERSION_ERROR', $this->updateVersion, JVERSION); ?></li>
					<?php endif; ?>

					<?php foreach($this->errors as $line => $error) : ?>
						<?php $key = 'COM_INSTALLER_MSG_DATABASE_' . $error->queryType;
						$msgs = $error->msgElements;
						$file = basename($error->file);
						$msg0 = (isset($msgs[0])) ? $msgs[0] : ' ';
						$msg1 = (isset($msgs[1])) ? $msgs[1] : ' ';
						$msg2 = (isset($msgs[2])) ? $msgs[2] : ' ';
						$message = Lang::txt($key, $file, $msg0, $msg1, $msg2); ?>
						<li><?php echo $message; ?></li>
					<?php endforeach; ?>
				</ul>
			</fieldset>
		<?php endif; ?>

		<?php echo JHtml::_('sliders.panel', Lang::txt('COM_INSTALLER_MSG_DATABASE_INFO'), 'furtherinfo-pane'); ?>
			<fieldset class="panelform">
			<ul>
				<li><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_SCHEMA_VERSION', $this->schemaVersion); ?></li>
				<li><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_UPDATE_VERSION', $this->updateVersion); ?></li>
				<li><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_DRIVER', JFactory::getDbo()->name); ?></li>
				<li><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_CHECKED_OK', count($this->results['ok'])); ?></li>
				<li><?php echo Lang::txt('COM_INSTALLER_MSG_DATABASE_SKIPPED', count($this->results['skipped'])); ?></li>
			</ul>
			</fieldset>
		<?php echo JHtml::_('sliders.end'); ?>

		<div class="clr"> </div>

		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>