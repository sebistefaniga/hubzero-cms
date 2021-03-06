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
defined('_JEXEC') or die('Restricted access');

// set title
Toolbar::title(Lang::txt('COM_MEMBERS') . ': ' . Lang::txt('COM_MEMBERS_IMPORT_TITLE_RUN'), 'script.png');

// add import styles and scripts
$this->js('import')
     ->js('handlebars', 'system')
     ->css('import');
?>
<script type="text/javascript">
function submitbutton(pressbutton)
{
	var form = document.adminForm;
	if (pressbutton == 'cancel') {
		submitform( pressbutton );
		return;
	}
	// do field validation
	submitform( pressbutton );
}
</script>

<?php foreach ($this->getErrors() as $error) : ?>
	<p class="error"><?php echo $error; ?></p>
<?php endforeach; ?>

<form action="<?php echo Route::url('index.php?option=com_members&controller=import&task=dorun'); ?>" method="post" name="adminForm" id="adminForm">

	<fieldset class="adminform import-results">

		<?php if ($this->dryRun) : ?>
			<div class="dryrun-message">
				<strong><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_NOTICE'); ?></strong>
				<p><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_NOTICE_DESC'); ?></p>
			</div>
		<?php endif; ?>

		<div class="countdown" data-timeout="5">
			<?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_START', '<span>5</span>'); ?>
		</div>
		<div class="countdown-actions" data-progress="<?php echo Route::url('index.php?option=com_members&controller=import&task=progress&id=' . $this->import->get('id')); ?>">
			<button type="button" class="start"><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_BUTTON_START'); ?></button>
			<button type="button" class="stop"><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_BUTTON_STOP'); ?></button>

			<button type="button" class="start-over"><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_BUTTON_RERUN'); ?></button>
			<?php if ($this->dryRun) : ?>
				<button type="button" class="start-real"><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_BUTTON_REAL'); ?></button>
			<?php endif; ?>
		</div>

		<hr />

		<strong><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_PROGRESS'); ?><span class="progress-percentage">0%</span></strong>
		<div class="progress"></div>

		<hr />

		<strong><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULTS'); ?><span class="results-stats"></span></strong>
		<div class="results">
			<span class="hint"><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULTS_WAITING'); ?></span>
		</div>
		<script id="entry-template" type="text/x-handlebars-template">
			<h3 class="resource-title">
				{{#if record.errors}}<span class="has-errors"><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULT_CONTAINSERRORS'); ?></span>{{/if}}
				{{#if record.notices}}<span class="has-notices"><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULT_CONTAINSNOTICES'); ?></span>{{/if}}
				{{{ record.entry.name }}}
			</h3>

			<div class="resource-data">
				<div class="grid">
					{{#if record.errors}}
						<div class="col width-100">
							<div class="errors">
								<strong><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULT_ERRORMESSAGE'); ?></strong>
								<ol>
									{{#each record.errors}}
										<li>{{this}}</li>
									{{/each}}
								</ol>
							</div>
						</div>
					{{/if}}

					{{#if record.notices}}
						<div class="col width-100">
							<div class="notices">
								<strong><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULT_NOTICEMESSAGE'); ?></strong>
								<ol>
									{{#each record.notices}}
										<li>{{{this}}}</li>
									{{/each}}
								</ol>
							</div>
						</div>
					{{/if}}

					<div class="col width-60 fltlft">
						{{{entry_data record}}}
					</div>
					<div class="col width-40 fltrt">

						<h4><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULT_DISABILITY'); ?></h4>
						<table>
							<tr>
								<td>
									{{#each record.entry.disability}}
										{{{ this }}}<br />
									{{else}}
										<span class="hint"><?php echo Lang::txt('COM_MEMBERS_NONE'); ?></span>
									{{/each}}
								</td>
							</tr>
						</table>

						<hr />

						<h4><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULT_RACE'); ?></h4>
						<table>
							<tr>
								<td>
									{{#each record.entry.race}}
										{{{ this }}}<br />
									{{else}}
										<span class="hint"><?php echo Lang::txt('COM_MEMBERS_NONE'); ?></span>
									{{/each}}
								</td>
							</tr>
						</table>

						<hr />

						<h4><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULT_TAGS'); ?></h4>
						<table>
							<tr>
								<td>
									{{#each record.tags}}
										{{{ this }}}<br />
									{{else}}
										<span class="hint"><?php echo Lang::txt('COM_MEMBERS_NONE'); ?></span>
									{{/each}}
								</td>
							</tr>
						</table>

					</div>
					<br class="clr" />
					<hr />

					<div class="unused-data">
						<h4><?php echo Lang::txt('COM_MEMBERS_IMPORT_RUN_RESULT_UNUSED'); ?></h4>
						<pre>{{print_json_data raw._unused}}</pre>
					</div>
				</div>
			</div>
		</script>

	</fieldset>

	<input type="hidden" name="option" value="<?php echo $this->option ?>" />
	<input type="hidden" name="controller" value="<?php echo $this->controller; ?>">
	<input type="hidden" name="task" value="dorun" />
	<input type="hidden" name="id" value="<?php echo $this->import->get('id'); ?>" />
	<input type="hidden" name="dryrun" value="<?php echo $this->dryRun; ?>" />

	<?php echo JHTML::_('form.token'); ?>
</form>