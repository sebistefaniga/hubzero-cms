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

$text = ($this->task == 'edit' ? Lang::txt('JACTION_EDIT') : Lang::txt('JACTION_CREATE'));

$canDo = \Components\Courses\Helpers\Permissions::getActions();

Toolbar::title(Lang::txt('COM_COURSES').': ' . Lang::txt('COM_COURSES_OFFERING') . ': ' . $text, 'courses.png');
if ($canDo->get('core.edit'))
{
	Toolbar::apply();
	Toolbar::save();
	Toolbar::spacer();
}
Toolbar::cancel();
Toolbar::spacer();
Toolbar::help('offering');

$base = str_replace('/administrator', '', Request::base(true));

$this->css();
?>
<script type="text/javascript">
function submitbutton(pressbutton)
{
	var form = document.adminForm;

	if (pressbutton == 'cancel') {
		submitform(pressbutton);
		return;
	}

	// form field validation
	if ($('field-title').value == '') {
		alert('<?php echo Lang::txt('COM_COURSES_ERROR_MISSING_TITLE'); ?>');
	} else {
		submitform(pressbutton);
	}
}
</script>
<?php if ($this->getError()) { ?>
	<p class="error"><?php echo implode('<br />', $this->getError()); ?></p>
<?php } ?>
<form action="<?php echo Route::url('index.php?option=' . $this->option  . '&controller=' . $this->controller); ?>" method="post" name="adminForm" id="item-form">
	<div class="col width-60 fltlft">
		<fieldset class="adminform">
			<legend><span><?php echo Lang::txt('JDETAILS'); ?></span></legend>

			<input type="hidden" name="fields[id]" value="<?php echo $this->row->get('id'); ?>" />
			<input type="hidden" name="fields[course_id]" value="<?php echo $this->row->get('course_id'); ?>" />
			<input type="hidden" name="course" value="<?php echo $this->row->get('course_id'); ?>" />
			<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
			<input type="hidden" name="controller" value="<?php echo $this->controller; ?>">
			<input type="hidden" name="task" value="save" />

			<div class="input-wrap">
				<label for="field-title"><?php echo Lang::txt('COM_COURSES_FIELD_TITLE'); ?>: <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label><br />
				<input type="text" name="fields[title]" id="field-title" value="<?php echo $this->escape(stripslashes($this->row->get('title'))); ?>" />
			</div>
			<div class="input-wrap" data-hint="<?php echo Lang::txt('COM_COURSES_FIELD_ALIAS_HINT'); ?>">
				<label for="field-alias"><?php echo Lang::txt('COM_COURSES_FIELD_ALIAS'); ?>:</label><br />
				<input type="text" name="fields[alias]" id="field-alias" value="<?php echo $this->escape(stripslashes($this->row->get('alias'))); ?>" />
				<span class="hint"><?php echo Lang::txt('COM_COURSES_FIELD_ALIAS_HINT'); ?></span>
			</div>
		</fieldset>

		<fieldset class="adminform">
			<legend><span><?php echo Lang::txt('COM_COURSES_FIELDSET_PUBLISHING'); ?></span></legend>

			<div class="input-wrap">
				<label for="field-state"><?php echo Lang::txt('COM_COURSES_FIELD_STATE'); ?>: <span class="required"><?php echo Lang::txt('JOPTION_REQUIRED'); ?></span></label><br />
				<select name="fields[state]" id="field-state">
					<option value="0"<?php if ($this->row->get('state') == 0) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COURSES_UNPUBLISHED'); ?></option>
					<option value="1"<?php if ($this->row->get('state') == 1) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COURSES_PUBLISHED'); ?></option>
					<option value="2"<?php if ($this->row->get('state') == 2) { echo ' selected="selected"'; } ?>><?php echo Lang::txt('COM_COURSES_TRASHED'); ?></option>
				</select>
			</div>

			<p><?php echo Lang::txt('COM_COURSES_OFFERING_START_END_HINT'); ?></p>

			<div class="col width-50 fltlft">
				<div class="input-wrap" data-hint="YYYY-MM-DD HH:mm:ss">
					<label for="publish_up"><?php echo Lang::txt('COM_COURSES_FIELD_STARTS'); ?>:</label><br />
					<?php echo JHTML::_('calendar', ($this->row->get('publish_up') != '0000-00-00 00:00:00' ? $this->row->get('publish_up') : ''), 'fields[publish_up]', 'publish_up'); ?>
					<span class="hint"><?php echo Lang::txt('COM_COURSES_FIELD_STARTS_HINT'); ?></span>
				</div>
			</div>
			<div class="col width-50 fltrt">
				<div class="input-wrap" data-hint="YYYY-MM-DD HH:mm:ss">
					<label for="publish_down"><?php echo Lang::txt('COM_COURSES_FIELD_ENDS'); ?>:</label><br />
					<?php echo JHTML::_('calendar', ($this->row->get('publish_down') != '0000-00-00 00:00:00' ? $this->row->get('publish_down') : ''), 'fields[publish_down]', 'publish_down'); ?>
					<span class="hint"><?php echo Lang::txt('COM_COURSES_FIELD_ENDS_HINT'); ?></span>
				</div>
			</div>
			<div class="clr"></div>
		</fieldset>
	</div>
	<div class="col width-40 fltrt">
		<table class="meta">
			<tbody>
				<tr>
					<th><?php echo Lang::txt('COM_COURSES_FIELD_COURSE_ID'); ?></th>
					<td><?php echo $this->escape($this->row->get('course_id')); ?></td>
				</tr>
				<tr>
					<th><?php echo Lang::txt('COM_COURSES_FIELD_OFFERING_ID'); ?></th>
					<td><?php echo $this->escape($this->row->get('id')); ?></td>
				</tr>
			<?php if ($this->row->get('created')) { ?>
				<tr>
					<th><?php echo Lang::txt('COM_COURSES_FIELD_CREATED'); ?></th>
					<td><?php echo $this->escape($this->row->get('created')); ?></td>
				</tr>
			<?php } ?>
			<?php if ($this->row->get('created_by')) { ?>
				<tr>
					<th><?php echo Lang::txt('COM_COURSES_FIELD_CREATOR'); ?></th>
					<td><?php
					$creator = JUser::getInstance($this->row->get('created_by'));
					echo $this->escape(stripslashes($creator->get('name'))); ?></td>
				</tr>
			<?php } ?>
			</tbody>
		</table>

		<fieldset class="adminform">
			<legend><span><?php echo Lang::txt('COM_COURSES_LOGO'); ?></span></legend>

			<?php
			if ($this->row->exists())
			{
				$logo = $this->row->params('logo');
				?>
				<div style="padding-top: 2.5em">
					<div id="ajax-uploader" data-action="<?php echo Route::url('index.php?option=' . $this->option  . '&controller=logo&task=upload&type=offering&id=' . $this->row->get('id') . '&no_html=1&' . JUtility::getToken() . '=1'); ?>">
						<noscript>
							<iframe width="100%" height="350" name="filer" id="filer" frameborder="0" src="<?php echo Route::url('index.php?option=' . $this->option  . '&controller=logo&tmpl=component&file=' . $logo . '&type=offering&id=' . $this->row->get('id')); ?>"></iframe>
						</noscript>
					</div>
				</div>
					<?php
					$width  = 0;
					$height = 0;
					$this_size = 0;
					if ($logo)
					{
						$path = $this->row->logo('path');

						$this_size = filesize(PATH_APP . $path . DS . $logo);
						list($width, $height, $type, $attr) = getimagesize(PATH_APP . $path . DS . $logo);
						$pic = $logo;
					}
					else
					{
						$pic  = 'blank.png';
						$path = '/components/com_courses/admin/assets/img';
					}
					?>
					<div id="img-container">
						<img id="img-display" src="<?php echo '..' . $path . DS . $pic; ?>" alt="<?php echo Lang::txt('COM_COURSES_LOGO'); ?>" />
						<input type="hidden" name="currentfile" id="currentfile" value="<?php echo $this->escape($logo); ?>" />
					</div>
					<table class="formed">
						<tbody>
							<tr>
								<th><?php echo Lang::txt('COM_COURSES_FILE'); ?>:</th>
								<td>
									<span id="img-name"><?php echo $this->row->params('logo', Lang::txt('COM_COURSES_NONE')); ?></span>
								</td>
								<td>
									<a id="img-delete" <?php echo $logo ? '' : 'style="display: none;"'; ?> href="<?php echo Route::url('index.php?option=' . $this->option  . '&controller=logo&tmpl=component&task=remove&currentfile=' . $logo . '&type=offering&id=' . $this->row->get('id') . '&' . JUtility::getToken() . '=1'); ?>" title="<?php echo Lang::txt('COM_COURSES_DELETE'); ?>">[ x ]</a>
								</td>
							</tr>
							<tr>
								<th><?php echo Lang::txt('COM_COURSES_PICTURE_SIZE'); ?>:</th>
								<td><span id="img-size"><?php echo \Hubzero\Utility\Number::formatBytes($this_size); ?></span></td>
								<td></td>
							</tr>
							<tr>
								<th><?php echo Lang::txt('COM_COURSES_PICTURE_WIDTH'); ?>:</th>
								<td><span id="img-width"><?php echo $width; ?></span> px</td>
								<td></td>
							</tr>
							<tr>
								<th><?php echo Lang::txt('COM_COURSES_PICTURE_HEIGHT'); ?>:</th>
								<td><span id="img-height"><?php echo $height; ?></span> px</td>
								<td></td>
							</tr>
						</tbody>
					</table>

					<script type="text/javascript" src="<?php echo $base; ?>/media/system/js/jquery.fileuploader.js"></script>
					<script type="text/javascript">
					String.prototype.nohtml = function () {
						if (this.indexOf('?') == -1) {
							return this + '?no_html=1';
						} else {
							return this + '&no_html=1';
						}
					};
					jQuery(document).ready(function($){
						if ($("#ajax-uploader").length) {
							var uploader = new qq.FileUploader({
								element: $("#ajax-uploader")[0],
								action: $("#ajax-uploader").attr("data-action"),
								multiple: true,
								debug: true,
								template: '<div class="qq-uploader">' +
											'<div class="qq-upload-button"><span><?php echo Lang::txt('COM_COURSES_UPLOAD_CLICK_OR_DROP'); ?></span></div>' +
											'<div class="qq-upload-drop-area"><span><?php echo Lang::txt('COM_COURSES_UPLOAD_CLICK_OR_DROP'); ?></span></div>' +
											'<ul class="qq-upload-list"></ul>' +
										   '</div>',
								onComplete: function(id, file, response) {
									if (response.success) {
										$('#img-display').attr('src', '..' + response.directory + '/' + response.file);
										$('#img-name').text(response.file);
										$('#img-size').text(response.size);
										$('#img-width').text(response.width);
										$('#img-height').text(response.height);

										$('#img-delete').show();
									}
								}
							});
						}
						$('#img-delete').on('click', function (e) {
							e.preventDefault();
							var el = $(this);
							$.getJSON(el.attr('href').nohtml(), {}, function(response) {
								if (response.success) {
									$('#img-display').attr('src', '../components/com_courses/admin/assets/img/blank.png');
									$('#img-name').text('[ none ]');
									$('#img-size').text('0');
									$('#img-width').text('0');
									$('#img-height').text('0');
								}
								el.hide();
							});
						});
					});
					</script>
			<?php
			} else {
				echo '<p class="warning">'.Lang::txt('COM_COURSES_PICTURE_ADDED_LATER').'</p>';
			}
			?>
		</fieldset>

		<?php $params = new JRegistry($this->row->get('params')); ?>

		<fieldset class="adminform offeringparams">
			<legend><?php echo Lang::txt('COM_COURSES_FIELDSET_PARAMS'); ?></legend>
			<div class="input-wrap">
				<label for="params-progress-calculation"><?php echo Lang::txt('COM_COURSES_PROGRESS_CALCULATION'); ?>:</label><br />
				<select name="params[progress_calculation]" id="params-progress-calculation">
					<option value=""<?php echo ($params->get('progress_calculation', '') == '') ? 'selected="selected"' : '' ?>><?php echo Lang::txt('COM_COURSES_PROGRESS_CALCULATION_INHERIT'); ?></option>
					<option value="all"<?php echo ($params->get('progress_calculation', '') == 'all') ? 'selected="selected"' : '' ?>><?php echo Lang::txt('COM_COURSES_PROGRESS_CALCULATION_ALL'); ?></option>
					<option value="graded"<?php echo ($params->get('progress_calculation', '') == 'graded') ? 'selected="selected"' : '' ?>><?php echo Lang::txt('COM_COURSES_PROGRESS_CALCULATION_GRADED'); ?></option>
					<option value="videos"<?php echo ($params->get('progress_calculation', '') == 'videos') ? 'selected="selected"' : '' ?>><?php echo Lang::txt('COM_COURSES_PROGRESS_CALCULATION_VIDEOS'); ?></option>
					<option value="manual"<?php echo ($params->get('progress_calculation', '') == 'manual') ? 'selected="selected"' : '' ?>><?php echo Lang::txt('COM_COURSES_PROGRESS_CALCULATION_MANUAL'); ?></option>
				</select>
			</div>
		</fieldset>

		<?php
			if ($plugins = Event::trigger('courses.onOfferingEdit'))
			{
				$data = $this->row->get('params');

				foreach ($plugins as $plugin)
				{
					$param = new JParameter(
						(is_object($data) ? $data->toString() : $data),
						PATH_CORE . DS . 'plugins' . DS . 'courses' . DS . $plugin['name'] . DS . $plugin['name'] . '.xml'
					);
					$out = $param->render('params', 'onOfferingEdit');
					if (!$out)
					{
						continue;
					}
					?>
					<fieldset class="adminform eventparams" id="params-<?php echo $plugin['name']; ?>">
						<legend><?php echo Lang::txt('COM_COURSES_FIELDSET_PARAMETERS', $plugin['title']); ?></legend>
						<?php echo $out; ?>
					</fieldset>
					<?php
				}
			}
		?>
	</div>
	<div class="clr"></div>

	<?php echo JHTML::_('form.token'); ?>
</form>
