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

$this->row->fulltxt = ($this->row->fulltxt) ? stripslashes($this->row->fulltxt): stripslashes($this->row->introtext);

$type = new \Components\Resources\Tables\Type( $this->database );
$type->load( $this->row->type );

$data = array();
preg_match_all("#<nb:(.*?)>(.*?)</nb:(.*?)>#s", $this->row->fulltxt, $matches, PREG_SET_ORDER);
if (count($matches) > 0)
{
	foreach ($matches as $match)
	{
		$data[$match[1]] = \Components\Resources\Site\Controllers\Create::_txtUnpee($match[2]);
	}
}

$this->row->fulltxt = preg_replace("#<nb:(.*?)>(.*?)</nb:(.*?)>#s", '', $this->row->fulltxt);
$this->row->fulltxt = trim($this->row->fulltxt);

include_once(JPATH_ROOT . DS . 'components' . DS . 'com_resources' . DS . 'models' . DS . 'elements.php');

$elements = new \Components\Resources\Models\Elements($data, $type->customFields);
$fields = $elements->render();

$this->css('create.css')
     ->js('create.js');
?>
<header id="content-header">
	<h2><?php echo $this->title; ?></h2>

	<div id="content-header-extra">
		<p>
			<a class="icon-add add btn" href="<?php echo Route::url('index.php?option=' . $this->option . '&task=draft'); ?>">
				<?php echo Lang::txt('COM_CONTRIBUTE_NEW_SUBMISSION'); ?>
			</a>
		</p>
	</div><!-- / #content-header -->
</header><!-- / #content-header -->

<section class="main section">
	<?php
		$this->view('steps')
		     ->set('option', $this->option)
		     ->set('step', $this->step)
		     ->set('steps', $this->steps)
		     ->set('id', $this->id)
		     ->set('resource', $this->row)
		     ->set('progress', $this->progress)
		     ->display();
	?>
<?php if ($this->getError()) { ?>
	<p class="warning"><?php echo implode('<br />', $this->getErrors()); ?></p>
<?php } ?>
	<form action="<?php echo Route::url('index.php?option=' . $this->option . '&task=draft&step=' . $this->next_step . '&id=' . $this->id); ?>" method="post" id="hubForm" accept-charset="utf-8">
		<div class="explaination">
			<p><?php echo Lang::txt('COM_CONTRIBUTE_COMPOSE_EXPLANATION'); ?></p>

			<p><?php echo Lang::txt('COM_CONTRIBUTE_COMPOSE_ABSTRACT_HINT'); ?></p>
		</div>
		<fieldset>
			<legend><?php echo Lang::txt('COM_CONTRIBUTE_COMPOSE_ABOUT'); ?></legend>

			<label for="field-title">
				<?php echo Lang::txt('COM_CONTRIBUTE_COMPOSE_TITLE'); ?>: <span class="required"><?php echo Lang::txt('COM_CONTRIBUTE_REQUIRED'); ?></span>
				<input type="text" name="title" id="field-title" maxlength="250" value="<?php echo $this->escape(stripslashes($this->row->title)); ?>" />
			</label>

			<label for="field-fulltxt">
				<?php echo Lang::txt('COM_CONTRIBUTE_COMPOSE_ABSTRACT'); ?>:
				<?php echo JFactory::getEditor()->display('fulltxt', $this->escape(stripslashes($this->row->fulltxt)), '', '', 50, 20, false, 'field-fulltxt'); ?>
			</label>

			<fieldset>
				<legend>Manage files</legend>
				<div class="field-wrap">
					<iframe width="100%" height="160" name="filer" id="filer" src="index.php?option=<?php echo $this->option; ?>&amp;controller=media&amp;tmpl=component&amp;resource=<?php echo $this->row->id; ?>"></iframe>
				</div>
			</fieldset>
		</fieldset><div class="clear"></div>
	<?php if ($fields) { ?>
		<div class="explaination">
			<p><?php echo Lang::txt('COM_CONTRIBUTE_COMPOSE_CUSTOM_FIELDS_EXPLANATION'); ?></p>
		</div>
		<fieldset>
			<legend><?php echo Lang::txt('COM_CONTRIBUTE_COMPOSE_DETAILS'); ?></legend>
			<?php
			echo $fields;
			?>
		</fieldset><div class="clear"></div>
	<?php } ?>
		<input type="hidden" name="published" value="<?php echo $this->row->published; ?>" />
		<input type="hidden" name="standalone" value="1" />
		<input type="hidden" name="id" value="<?php echo $this->row->id; ?>" />
		<input type="hidden" name="type" value="<?php echo $this->row->type; ?>" />
		<input type="hidden" name="created" value="<?php echo $this->row->created; ?>" />
		<input type="hidden" name="created_by" value="<?php echo $this->row->created_by; ?>" />
		<input type="hidden" name="publish_up" value="<?php echo $this->row->publish_up; ?>" />
		<input type="hidden" name="publish_down" value="<?php echo $this->row->publish_down; ?>" />
		<input type="hidden" name="group_owner" value="<?php echo $this->row->group_owner; ?>" />

		<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
		<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
		<input type="hidden" name="task" value="<?php echo $this->task; ?>" />
		<input type="hidden" name="step" value="<?php echo $this->next_step; ?>" />
		<p class="submit">
			<input class="btn btn-success" type="submit" value="<?php echo Lang::txt('COM_CONTRIBUTE_NEXT'); ?>" />
		</p>
	</form>
</section><!-- / .main section -->
