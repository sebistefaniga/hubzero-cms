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

$tmpl = Request::getVar('tmpl', '');
$no_html = Request::getInt('no_html', 0);

$juser = JFactory::getUser();
?>
<?php
if (!$tmpl && !$no_html) {
	// Push some styles to the template
	$this->js('json2.js');
	$this->js('condition.builder.js');
	$this->css('conditions.css');
?>
	<form action="index.php" method="post" name="adminForm" id="item-form">
		<div class="col width-100">
			<fieldset class="adminform">
				<legend><?php echo Lang::txt('JDETAILS'); ?></legend>

				<table class="admintable">
					<tbody>
						<tr>
							<td class="key"><label for="field-iscore"><?php echo Lang::txt('COM_SUPPORT_FIELD_TYPE'); ?></label></td>
							<td colspan="2">
								<select name="fields[iscore]" id="field-iscore">
									<optgroup label="<?php echo Lang::txt('COM_SUPPORT_QUERY_TYPE_COMMON'); ?>">
										<option value="2"<?php if ($this->row->iscore == 2) { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_TYPE_COMMON_ACL'); ?></option>
										<option value="4"<?php if ($this->row->iscore == 4) { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_TYPE_COMMON_NO_ACL'); ?></option>
									</optgroup>
									<option value="1"<?php if ($this->row->iscore == 1) { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_TYPE_MINE'); ?></option>
									<option value="0"<?php if ($this->row->iscore == 0) { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_TYPE_CUSTOM'); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td class="key"><label for="field-title"><?php echo Lang::txt('COM_SUPPORT_FIELD_TITLE'); ?></label></td>
							<td colspan="2"><input type="text" name="fields[title]" id="field-title" value="<?php echo $this->escape(stripslashes($this->row->title)); ?>" /></td>
						</tr>
						<tr>
							<td colspan="3">
								<fieldset class="query">
									<?php
										if ($this->row->conditions)
										{
											$condition = json_decode($this->row->conditions);
											//foreach ($conditions as $condition)
											//{
												$this->view('condition')
												     ->set('option', $this->option)
												     ->set('controller', $this->controller)
												     ->set('condition', $condition)
												     ->set('conditions', $this->conditions)
												     ->set('row', $this->row)
												     ->display();
											//}
										}
									?>
								</fieldset>
							</td>
						</tr>
						<tr>
							<td class="key"><label for="field-sort"><?php echo Lang::txt('Sort results by:'); ?></label></td>
							<td>
								<select name="fields[sort]" id="field-sort">
									<option value="open"<?php if ($this->row->sort == 'open') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_OPEN'); ?></option>
									<option value="status"<?php if ($this->row->sort == 'status') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_STATUS'); ?></option>
									<option value="login"<?php if ($this->row->sort == 'login') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_SUBMITTER'); ?></option>
									<option value="owner"<?php if ($this->row->sort == 'owner') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_OWNER'); ?></option>
									<option value="group"<?php if ($this->row->sort == 'group') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('Group'); ?></option>
									<option value="id"<?php if ($this->row->sort == 'id') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_ID'); ?></option>
									<option value="report"<?php if ($this->row->sort == 'report') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_REPORT'); ?></option>
									<?php /*<option value="resolved"<?php if ($this->row->sort == 'resolved') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_RESOLUTION'); ?></option>*/ ?>
									<option value="severity"<?php if ($this->row->sort == 'severity') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_SEVERITY'); ?></option>
									<option value="tag"<?php if ($this->row->sort == 'tag') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_TAG'); ?></option>
									<option value="type"<?php if ($this->row->sort == 'type') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_TYPE'); ?></option>
									<option value="created"<?php if ($this->row->sort == 'created') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_CREATED'); ?></option>
									<option value="closed"<?php if ($this->row->sort == 'closed') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_CLOSED'); ?></option>
									<option value="category"<?php if ($this->row->sort == 'category') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_CATEGORY'); ?></option>
								</select>
							</td>
							<td>
								<select name="fields[sort_dir]" id="field-sort_dir">
									<option value="DESC"<?php if (strtolower($this->row->sort_dir) == 'desc') { echo ' selected="selected"'; }; ?>>desc</option>
									<option value="ASC"<?php if (strtolower($this->row->sort_dir) == 'asc') { echo ' selected="selected"'; }; ?>>asc</option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</fieldset>
		</div>

		<input type="hidden" name="fields[id]" value="<?php echo $this->row->id; ?>" />
		<input type="hidden" name="fields[conditions]" id="field-conditions" value="<?php echo $this->escape(stripslashes($this->row->conditions)); ?>" />
		<input type="hidden" name="fields[user_id]" value="<?php echo $juser->get('id'); ?>" />

		<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
		<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
		<input type="hidden" name="no_html" value="<?php echo ($tmpl) ? 1 : Request::getInt('no_html', 0); ?>" />
		<input type="hidden" name="tmpl" value="<?php echo $tmpl; ?>" />
		<input type="hidden" name="task" value="save" />

		<?php echo JHTML::_('form.token'); ?>
	</form>
	<script type="text/javascript">
		function submitbutton(pressbutton)
		{
			var $ = jq, query = {};

			if (pressbutton == 'cancel') {
				submitform( pressbutton );
				return;
			}

			var query = {};
			query = Conditions.getCondition('.query > fieldset');
			$('#field-conditions').val(JSON.stringify(query));

			submitform( pressbutton );
		}

		Conditions.option = <?php echo json_encode($this->conditions); ?>

		jQuery(document).ready(function($){
			Conditions.addqueryroot('.query', true);
		});
	</script>
<?php
} else {
	if ($this->row->iscore != 0)
	{
		$this->row->title .= ' ' . Lang::txt('(copy)');
	}
?>
	<form action="index.php" method="post" name="adminForm" id="queryForm">
		<h3>
			<span style="float: right">
				<input type="submit" value="<?php echo Lang::txt('COM_SUPPORT_QUERY_SAVE'); ?>" />
			</span>
			<span class="configuration">
				<?php echo Lang::txt('COM_SUPPORT_QUERY_BUILDER') ?>
			</span>
		</h3>
		<fieldset class="wrapper">

		<fieldset class="fields title">
			<label for="field-title"><?php echo Lang::txt('COM_SUPPORT_FIELD_TITLE'); ?></label>
			<input type="text" name="fields[title]" id="field-title" value="<?php echo $this->escape(stripslashes($this->row->title)); ?>" />
		</fieldset>

		<fieldset class="query">
<?php
	if ($this->row->conditions)
	{
		$condition = json_decode($this->row->conditions);
		//foreach ($conditions as $condition)
		//{
			$this->view('condition')
			     ->set('option', $this->option)
			     ->set('controller', $this->controller)
			     ->set('condition', $condition)
			     ->set('conditions', $this->conditions)
			     ->set('row', $this->row)
			     ->display();

		//}
	}
?>
		</fieldset>

		<fieldset class="fields sort">
			<p>
				<label for="field-sort"><?php echo Lang::txt('In folder'); ?></label>
				<select name="fields[folder_id]" id="field-folder_id">
					<?php
					$database = JFactory::getDBO();
					include_once(JPATH_ROOT . DS . 'components' . DS . 'com_support' . DS . 'tables' . DS . 'queryfolder.php');

					$sr = new \Components\Support\Tables\QueryFolder($database);
					$folders = $sr->find('list', array(
						'user_id'  => JFactory::getUser()->get('id'),
						'sort'     => 'ordering',
						'sort_Dir' => 'ASC'
					));
					if ($folders)
					{
						foreach ($folders as $folder)
						{
							?><option value="<?php echo $folder->id; ?>"<?php if ($this->row->folder_id == $folder->id) { echo ' selected="selected"'; } ?>><?php echo $this->escape(stripslashes($folder->title)); ?></option><?php
						}
					}
					?>
				</select>

				<label for="field-sort"><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_BY'); ?></label>
				<select name="fields[sort]" id="field-sort">
					<option value="open"<?php if ($this->row->sort == 'open') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_OPEN'); ?></option>
					<option value="status"<?php if ($this->row->sort == 'status') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_STATUS'); ?></option>
					<option value="login"<?php if ($this->row->sort == 'login') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_SUBMITTER'); ?></option>
					<option value="owner"<?php if ($this->row->sort == 'owner') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_OWNER'); ?></option>
					<option value="group"<?php if ($this->row->sort == 'group') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('Group'); ?></option>
					<option value="id"<?php if ($this->row->sort == 'id') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_ID'); ?></option>
					<option value="report"<?php if ($this->row->sort == 'report') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_REPORT'); ?></option>
					<?php /*<option value="resolved"<?php if ($this->row->sort == 'resolved') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_RESOLUTION'); ?></option>*/ ?>
					<option value="severity"<?php if ($this->row->sort == 'severity') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_SEVERITY'); ?></option>
					<option value="tag"<?php if ($this->row->sort == 'tag') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_TAG'); ?></option>
					<option value="type"<?php if ($this->row->sort == 'type') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_TYPE'); ?></option>
					<option value="created"<?php if ($this->row->sort == 'created') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_CREATED'); ?></option>
					<option value="closed"<?php if ($this->row->sort == 'closed') { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_CLOSED'); ?></option>
					<option value="category"<?php if ($this->row->sort == 'category') { echo ' selected="selected"'; }; ?>><?php echo Lang::txt('COM_SUPPORT_QUERY_SORT_CATEGORY'); ?></option>
				</select>
				<select name="fields[sort_dir]" id="field-sort_dir">
					<option value="DESC"<?php if (strtolower($this->row->sort_dir) == 'desc') { echo ' selected="selected"'; }; ?>>desc</option>
					<option value="ASC"<?php if (strtolower($this->row->sort_dir) == 'asc') { echo ' selected="selected"'; }; ?>>asc</option>
				</select>
			</p>
		</fieldset>

		<input type="hidden" name="fields[id]" value="<?php echo ($this->row->iscore == 0) ? $this->row->id : 0; ?>" />
		<input type="hidden" name="fields[conditions]" id="field-conditions" value="<?php echo $this->escape(stripslashes($this->row->conditions)); ?>" />
		<input type="hidden" name="fields[user_id]" value="<?php echo $juser->get('id'); ?>" />

		<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
		<input type="hidden" name="controller" value="<?php echo $this->controller; ?>" />
		<input type="hidden" name="no_html" value="<?php echo ($tmpl) ? 1 : Request::getInt('no_html', 0); ?>" />
		<input type="hidden" name="tmpl" value="<?php echo $tmpl; ?>" />
		<input type="hidden" name="task" value="save" />

		<?php echo JHTML::_('form.token'); ?>
		</fieldset>
	</form>
	<script type="text/javascript">
		/*function submitbutton(pressbutton)
		{
			var $ = jq;

			var form = document.adminForm;

			if (pressbutton == 'cancel') {
				submitform( pressbutton );
				return;
			}

			var query = {};
			query = Conditions.getCondition('.query > fieldset');
			$('#field-conditions').val(JSON.stringify(query));

			//submitform( pressbutton );
		}

		function saveAndUpdate()
		{
			var $ = jq, query = {};

			if (!$('#field-title').val()) {
				alert('Please provide a title.');
				return false;
			}

			query = Conditions.getCondition('.query > fieldset');
			$('#field-conditions').val(JSON.stringify(query));

			$.post('index.php', $("#queryForm").serialize(), function(data){
				window.parent.document.getElementById('custom-views').innerHTML = data;
				window.top.setTimeout('window.parent.document.getElementById(\'sbox-window\').close()', 700);
			});
		}*/

		Conditions.option = <?php echo json_encode($this->conditions); ?>

		/*jQuery(document).ready(function(jq){
			Conditions.addqueryroot('.query', true);
		});*/
	</script>
<?php } ?>