<?php
/**
 * @package		HUBzero CMS
 * @author		Alissa Nedossekina <alisa@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

include_once(JPATH_ROOT . DS . 'components' . DS . 'com_publications' . DS . 'models' . DS . 'elements.php');

// Parse data
$data = array();
preg_match_all("#<nb:(.*?)>(.*?)</nb:(.*?)>#s", $this->pub->metadata, $matches, PREG_SET_ORDER);
if (count($matches) > 0)
{
	foreach ($matches as $match)
	{
		$data[$match[1]] = $match[2];
	}
}

$required 		= (isset($this->manifest->params->required)
				&& $this->manifest->params->required) ? true : false;
$complete 		= isset($this->status->status) && $this->status->status == 1 ? 1 : 0;
$elName   		= 'element' . $this->elementId;
$aliasmap 		= $this->manifest->params->aliasmap;
$field 			= $this->manifest->params->field;
$value 			= $this->pub && isset($this->pub->$field) ? $this->pub->$field : NULL;
$size  			= isset($this->manifest->params->maxlength)
				&& $this->manifest->params->maxlength
				? 'maxlength="' . $this->manifest->params->maxlength . '"' : '';
$placeholder 	= isset($this->manifest->params->placeholder)
				? 'placeholder="' . $this->manifest->params->placeholder . '"' : '';
$editor			= $this->manifest->params->input == 'editor' ? 1 : 0;
$cols 			= isset($this->manifest->params->cols) ? $this->manifest->params->cols : 50;
$rows 			= isset($this->manifest->params->rows) ? $this->manifest->params->rows : 6;
$editorMacros 	= isset($this->manifest->params->editorMacros)
				? $this->manifest->params->editorMacros : 0;
$editorMinimal 	= isset($this->manifest->params->editorMinimal)
				? $this->manifest->params->editorMinimal : 1;
$editorImages 	= isset($this->manifest->params->editorImages)
				? $this->manifest->params->editorImages : 0;

$props = $this->master->block . '-' . $this->master->sequence . '-' . $this->elementId;

// Metadata field?
if ($field == 'metadata')
{
	$field  = 'nbtag[' . $aliasmap . ']';
	$value	= isset($data[$aliasmap]) ? $data[$aliasmap] : NULL;
}

$class = $value ? ' be-complete' : '';

// Determine if current element is active/ not yet filled/ last in order
$active = (($this->active == $this->elementId) || !$this->collapse) ? 1 : 0;
$coming = $this->pub->_curationModel->isComing($this->master->block, $this->master->sequence, $this->active, $this->elementId);
$last   = ($this->order == $this->total) ? 1 : 0;

// Get curator status
$curatorStatus = $this->pub->_curationModel->getCurationStatus($this->pub, $this->master->sequence, $this->elementId, 'author');

$aboutText = $this->manifest->about ? $this->manifest->about : NULL;

if ($this->pub->_project->provisioned == 1 && isset($this->manifest->aboutProv))
{
	$aboutText = $this->manifest->aboutProv;
}

// Wrap text in a paragraph
if (strlen($aboutText) == strlen(strip_tags($aboutText)))
{
	$aboutText = '<p>' . $aboutText . '</p>';
}

$complete = $curatorStatus->status == 1 && $required ? $curatorStatus->status : $complete;
$updated = $curatorStatus->updated && (($curatorStatus->status == 3 && !$complete) || $curatorStatus->status == 1 || $curatorStatus->status == 0) ? true : false;
?>

<div id="<?php echo $elName; ?>" class="blockelement <?php echo $required ? ' el-required' : ' el-optional';
echo $complete ? ' el-complete' : ' el-incomplete'; ?> <?php if ($editor) { echo ' el-editor'; } ?> <?php if ($coming) { echo ' el-coming'; } ?> <?php echo $curatorStatus->status == 1 ? ' el-passed el-reviewed' : ''; echo $curatorStatus->status == 0 ? ' el-failed' : ''; echo $updated ? ' el-updated' : ''; echo ($curatorStatus->status == 3 && !$complete) ? ' el-skipped' : ''; ?> ">
	<!-- Showing status only -->
	<div class="element_overview<?php if ($active) { echo ' hidden'; } ?>">
		<div class="block-aside"></div>
		<div class="block-subject">
			<span class="checker">&nbsp;</span>
			<h5 class="element-title"><?php echo $this->manifest->label; ?>
			<span class="element-options"><a href="<?php echo $this->pub->url . '?version=' . $this->pub->version . '&el=' . $this->elementId . '#element' . $this->elementId; ?>" class="edit-element" id="<?php echo $elName; ?>-edit"><?php echo Lang::txt('[edit]'); ?></a></span>
			</h5>
			<?php if (!$coming && $value) {
				// Parse editor text
				$val = $value;
				if ($editor)
				{
					$model = new \Components\Publications\Models\Publication($this->pub);
					$val = $model->parse($aliasmap, $this->manifest->params->field, 'parsed');
				}
				?>
				<div class="element-value"><?php echo $val; ?></div>
			<?php } ?>
		</div>
	</div>
	<!-- Active editing -->
	<div class="element_editing<?php if (!$active) { echo ' hidden'; } ?>">
		<div class="block-aside">
			<div class="block-info">
			<?php
				$shorten = ($aboutText && strlen($aboutText) > 200) ? 1 : 0;

				if ($shorten)
				{
					$about = \Hubzero\Utility\String::truncate($aboutText, 200, array('html' => true));
					$about.= ' <a href="#more-' . $elName . '" class="more-content">'
								. Lang::txt('PLG_PROJECTS_PUBLICATIONS_READ_MORE') . ' &raquo;</a>';
					$about.= ' <div class="hidden">';
					$about.= ' 	<div class="full-content" id="more-' . $elName . '">' . $aboutText . '</div>';
					$about.= ' </div>';
				}
				else
				{
					$about = $aboutText;
				}

				echo $about;
			?></div>
		</div>
		<div class="block-subject">
			<span class="checker">&nbsp;</span>
			<label id="<?php echo $elName; ?>-lbl"> <?php if ($required) { ?><span class="required"><?php echo Lang::txt('Required'); ?></span><?php } ?><?php if (!$required) { ?><span class="optional"><?php echo Lang::txt('Optional'); ?></span><?php } ?>
				<?php echo $this->manifest->label; ?>
				<?php echo $this->pub->_curationModel->drawCurationNotice($curatorStatus, $props, 'author', $elName); ?>
				<?php
				$output = '  <span class="field-wrap' . $class . '">';
				switch ($this->manifest->params->input)
				{
					case 'editor':

						$classes  = $editorMinimal == 1 ? 'minimal ' : '';
						$classes .= ' no-footer ';
						$classes .= $editorImages == 1 ? 'images ' : '';
						$classes .= $editorMacros == 1 ? 'macros ' : '';
						$output .= JFactory::getEditor()->display($field, $value
							, '', '', $cols, $rows, false
							, 'pub-' . $elName, null, null, array('class' => $classes));

					break;

					case 'textarea':
						$value = preg_replace("/\r\n/", "\r", trim($value));
						$output .= '<textarea name="' . $field . '" id="pub-' . $elName
							. '" ' . $size.' ' . $placeholder . ' cols="' . $cols . '" rows="' . $rows . '">' . $value . '</textarea>';
					break;

					case 'text':
					default:
						$output .= '<input type="text" name="' . $field . '" id="pub-' . $elName
							. '" value="' . $value.'" ' . $size.' ' . $placeholder . ' />';

					break;
				}
				$output .= '  </span>';
				echo $output; ?>
			</label>
			<?php if ($curatorStatus->status == 3 && !$complete) { ?>
				<p class="warning"><?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_SKIPPED_ITEM'); echo $curatorStatus->authornotice ? ' ' . Lang::txt('PLG_PROJECTS_PUBLICATIONS_REASON') . ':"' . $curatorStatus->authornotice . '"' : ''; ?></p>
			<?php } ?>
			<?php // Navigate to next element
				if ($active && $this->collapse) { ?>
				<p class="element-move">
					<span class="button-wrapper icon-next" id="next-<?php echo $props; ?>">
						<input type="button" value="<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_GO_NEXT'); ?>" id="<?php echo $elName; ?>-apply" class="save-element btn icon-next"/>
					</span>
					<span class="button-wrapper icon-apply">
						<input type="button" value="<?php echo Lang::txt('PLG_PROJECTS_PUBLICATIONS_APPLY_CHANGES'); ?>" id="apply-<?php echo $props; ?>" class="save-element btn icon-apply" />
					</span>
				</p>
			<?php } ?>
		</div>
	</div>
</div>
