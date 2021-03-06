<?php
/**
 * @package		Joomla.Administrator
 * @subpackage	com_modules
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

	$fieldSets = $this->form->getFieldsets('params');

	foreach ($fieldSets as $name => $fieldSet) :
		$label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_MODULES_'.$name.'_FIELDSET_LABEL';
		echo JHtml::_('sliders.panel', Lang::txt($label), $name.'-options');
			if (isset($fieldSet->description) && trim($fieldSet->description)) :
				echo '<p class="tip">'.$this->escape(Lang::txt($fieldSet->description)).'</p>';
			endif;
			?>
		<fieldset class="panelform">
		<?php $hidden_fields = ''; ?>

			<?php foreach ($this->form->getFieldset($name) as $field) : ?>
				<?php if (!$field->hidden) : ?>
					<div class="input-wrap">
						<?php echo $field->label; ?><br />
						<?php echo $field->input; ?>
					</div>
				<?php else : $hidden_fields.= $field->input; ?>
				<?php endif; ?>
			<?php endforeach; ?>

		<?php echo $hidden_fields; ?>
		</fieldset>
	<?php endforeach; ?>
