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
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$this->css();

$pageTitle = ($this->author->id) ? Lang::txt('COM_PUBLICATIONS_EDIT_AUTHOR_INFO') : Lang::txt('COM_PUBLICATIONS_ADD_AUTHOR');

$tmpl = Request::getVar('tmpl', '');

if ($tmpl != 'component')
{
	JToolBarHelper::title(Lang::txt('COM_PUBLICATIONS').': [ ' . $pageTitle . ' '
	. Lang::txt('COM_PUBLICATIONS_FOR_PUB') . ' #'
	. $this->pub->id . ' (v.' . $this->row->version_label . ')' . ' ]', 'groups.png');
	JToolBarHelper::save('saveauthor');
	JToolBarHelper::cancel();
}

$name 		= $this->author->name ? $this->author->name : NULL;
$firstname 	= NULL;
$lastname 	= NULL;

if (trim($name))
{
	$nameParts    = explode(" ", $name);
	$lastname  	  = end($nameParts);
	$firstname    = count($nameParts) > 1 ? $nameParts[0] : '';
}

$firstname 	= $this->author->firstName ? htmlspecialchars($this->author->firstName) : $firstname;
$lastname 	= $this->author->lastName ? htmlspecialchars($this->author->lastName) : $lastname;

?>
<script type="text/javascript">
function submitbutton(pressbutton)
{
	var form = document.adminForm;

	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}

	submitform(pressbutton);

}
</script>
<?php if ($this->getError()) { ?>
	<p class="error"><?php echo implode('<br />', $this->getError()); ?></p>
<?php } ?>
<p class="crumbs"><?php echo Lang::txt('COM_PUBLICATIONS_PUBLICATION_MANAGER'); ?> &raquo; <a href="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller . '&task=edit&id[]=' . $this->pub->id); ?>"><?php echo Lang::txt('COM_PUBLICATIONS_PUBLICATION') . ' #' . $this->pub->id; ?></a> &raquo; <?php echo $pageTitle; ?></p>

<form action="<?php echo Route::url('index.php?option=' . $this->option . '&controller=' . $this->controller); ?>" method="post" name="adminForm" id="item-form">
<?php if ($tmpl == 'component') { ?>
	<fieldset>
		<div style="float: right">
			<button type="button" onclick="submitbutton('addusers');"><?php echo Lang::txt( 'JSAVE' );?></button>
			<button type="button" onclick="window.parent.document.getElementById('sbox-window').close();"><?php echo Lang::txt( 'Cancel' );?></button>
		</div>
		<div class="configuration" >
			<?php echo Lang::txt('COM_PUBLICATIONS_EDIT_AUTHOR') ?>
		</div>
	</fieldset>
<?php } ?>
	<div class="col width-100">
		<fieldset class="adminform">
			<legend><span><?php echo $pageTitle; ?></span></legend>

			<input type="hidden" name="author" value="<?php echo $this->author->id; ?>" />
			<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
			<input type="hidden" name="controller" value="<?php echo $this->controller; ?>">
			<input type="hidden" name="no_html" value="<?php echo ($tmpl == 'component') ? '1' : '0'; ?>">
			<input type="hidden" name="task" value="saveauthor" />
			<input type="hidden" name="id" value="<?php echo $this->pub->id; ?>" />
			<input type="hidden" name="version" value="<?php echo $this->row->version_number; ?>" />
			<table class="admintable">
				<tbody>
					<?php if (!$this->author->id) { ?>
					<tr>
						<td class="key"><label><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_EMAIL'); ?>:</label></td>
						<td>
							<input type="text" name="email" value="" />
						</td>
					</tr>
					<?php } ?>
					<tr>
						<td class="key"><label><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_USER_ID'); ?>:</label></td>
						<td>
							<?php if (!$this->author->id || !$this->author->user_id) { ?>
							<input type="text" name="uid" value="<?php echo $this->author->user_id; ?>" size="25" />
							<?php } else { ?>
								<input type="hidden" name="uid" value="<?php echo $this->author->user_id; ?>" />
								<span><?php echo $this->author->user_id; ?></span>
							<?php } ?>
						</td>
					</tr>
					<tr>
						<td class="key"><label><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_AUTHOR_NAME_FIRST_AND_MIDDLE'); ?>:  <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label></td>
						<td>
							<input type="text" name="firstName" value="<?php echo $firstname; ?>" size="25" />
						</td>
					</tr>
					<tr>
						<td class="key"><label><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_AUTHOR_NAME_LAST'); ?>:  <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label></td>
						<td>
							<input type="text" name="lastName" value="<?php echo $lastname; ?>" size="25" />
						</td>
					</tr>
					<tr>
						<td class="key"><label><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_AUTHOR_ORGANIZATION'); ?>:  <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label></td>
						<td>
							<input type="text" name="organization" value="<?php echo $this->author->organization; ?>" size="25" />
						</td>
					</tr>
					<tr>
						<td class="key"><label><?php echo Lang::txt('COM_PUBLICATIONS_FIELD_AUTHOR_CREDIT'); ?>:</label></td>
						<td>
							<input type="text" name="credit" value="<?php echo $this->author->credit; ?>" size="25" />
						</td>
					</tr>
				</tbody>
			</table>
		</fieldset>
	</div>

	<?php echo JHTML::_('form.token'); ?>
</form>
