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
 * @license   GNU General Public License, version 2 (GPLv2)
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

$this->css()
     ->js();

$txt = '';
$mode = strtolower(Request::getWord('mode', ''));

if ($mode != 'preview')
{
	switch ($this->model->resource->published)
	{
		case 1: $txt .= ''; break; // published
		case 2: $txt .= '<span>[' . Lang::txt('COM_RESOURCES_DRAFT_EXTERNAL') . ']</span> '; break;  // external draft
		case 3: $txt .= '<span>[' . Lang::txt('COM_RESOURCES_PENDING') . ']</span> ';        break;  // pending
		case 4: $txt .= '<span>[' . Lang::txt('COM_RESOURCES_DELETED') . ']</span> ';        break;  // deleted
		case 5: $txt .= '<span>[' . Lang::txt('COM_RESOURCES_DRAFT_INTERNAL') . ']</span> '; break;  // internal draft
		case 0; $txt .= '<span>[' . Lang::txt('COM_RESOURCES_UNPUBLISHED') . ']</span> ';    break;  // unpublished
	}
}
?>
<section class="main section upperpane">
	<div class="subject">
		<div class="grid overviewcontainer">
			<div class="col span8">
				<header id="content-header">
					<h2>
						<?php echo $txt . $this->escape(stripslashes($this->model->resource->title)); ?>
						<?php if ($this->model->params->get('access-edit-resource')) { ?>
							<a class="icon-edit edit btn" href="<?php echo Route::url('index.php?option=com_resources&task=draft&step=1&id=' . $this->model->resource->id); ?>"><?php echo Lang::txt('COM_RESOURCES_EDIT'); ?></a>
						<?php } ?>
					</h2>
					<input type="hidden" name="rid" id="rid" value="<?php echo $this->model->resource->id; ?>" />
				</header>

				<?php if ($this->model->params->get('show_authors', 1)) { ?>
					<div id="authorslist">
						<?php
						$this->view('_contributors')
						     ->set('option', $this->option)
						     ->set('contributors', $this->model->contributors('!submitter'))
						     ->display();
						?>
					</div><!-- / #authorslist -->
				<?php } ?>
			</div><!-- / .overviewcontainer -->

			<div class="col span4 omega launcharea">
				<?php
					// Private/Public resource access check
					if (!$this->model->access('view-all'))
					{
						$ghtml = array();
						foreach ($this->model->resource->getGroups() as $allowedgroup)
						{
							$ghtml[] = '<a href="' . Route::url('index.php?option=com_groups&cn=' . $allowedgroup) . '">' . $allowedgroup . '</a>';
						}
				?>
				<p class="warning">
					<?php echo Lang::txt('COM_RESOURCES_ERROR_MUST_BE_PART_OF_GROUP') . ' ' . implode(', ', $ghtml); ?>
				</p>
				<?php
				}
				else
				{
					$ccount = count($this->model->children('standalone'));

					if ($ccount > 0)
					{
						echo \Components\Resources\Helpers\Html::primary_child($this->option, $this->model->resource, '', '');
					}

					$video = 0;
					$audio = 0;
					$notes = 0;

					$children = $this->model->children('standalone');

					if (!empty($children))
					{
						foreach ($children as $child)
						{
							$rhelper = new \Components\Resources\Helpers\Helper($child->id, $this->database);
							$rhelper->getChildren();
							if ($rhelper->children && count($rhelper->children) > 0)
							{
								foreach ($rhelper->children as $grandchild)
								{
									switch (\Components\Resources\Helpers\Html::getFileExtension($grandchild->path))
									{
										case 'm4v':
										case 'mp4':
										case 'wmv':
										case 'mov':
										case 'qt':
										case 'mpg':
										case 'mpeg':
										case 'mpe':
										case 'mp2':
										case 'mpv2':
											$videos++;
										break;

										case 'mp3':
										case 'm4a':
										case 'aiff':
										case 'aif':
										case 'wav':
										case 'ra':
										case 'ram':
											$audio++;
										break;

										case 'ppt':
										case 'pps':
										case 'pdf':
										case 'doc':
										case 'txt':
										case 'html':
										case 'htm':
											$notes++;
										break;
									}
								}
							}
						}
					}

					$live_site = rtrim(Request::base(),'/');

					if ($notes || $audio || $video)
					{
						?>
						<p>
						<?php if ($audio) { ?>
							<a class="feed" id="resource-audio-feed" href="<?php echo $live_site .'/resources/'.$this->model->resource->id.'/feed.rss?format=audio'; ?>"><?php echo Lang::txt('Audio podcast'); ?></a><br />
						<?php } ?>
						<?php if ($video) { ?>
							<a class="feed" id="resource-video-feed" href="<?php echo $live_site .'/resources/'.$this->model->resource->id.'/feed.rss?format=video'; ?>"><?php echo Lang::txt('Video podcast'); ?></a><br />
						<?php } ?>
						<?php if ($notes) { ?>
							<a class="feed" id="resource-slides-feed" href="<?php echo $live_site . '/resources/'.$this->model->resource->id.'/feed.rss?format=slides'; ?>"><?php echo Lang::txt('Slides/Notes podcast'); ?></a>
						<?php } ?>
						</p>
						<?php
					}
					if ($this->tab != 'play')
					{
						echo \Components\Resources\Helpers\Html::license($this->model->params->get('license', ''));
					}
				} // --- end else (if group check passed)
				?>
			</div><!-- / .aside launcharea -->
		</div>

		<?php
		// Display canonical
		$this->view('_canonical')
		     ->set('option', $this->option)
		     ->set('model', $this->model)
		     ->display();
		?>
	</div><!-- / .subject -->
	<aside class="aside rankarea">
		<?php
		// Show metadata
		if ($this->model->params->get('show_metadata', 1))
		{
			$this->view('_metadata')
			     ->set('option', $this->option)
			     ->set('sections', $this->sections)
			     ->set('model', $this->model)
			     ->display();
		}
		?>
	</aside><!-- / .aside -->
</section><!-- / .main section -->

<?php if ($this->model->access('view-all')) { ?>
	<section class="main section">
		<div class="subject tabbed">
			<?php echo \Components\Resources\Helpers\Html::tabs($this->option, $this->model->resource->id, $this->cats, $this->tab, $this->model->resource->alias); ?>
			<?php echo \Components\Resources\Helpers\Html::sections($this->sections, $this->cats, $this->tab, 'hide', 'main'); ?>
		</div><!-- / .subject -->
		<div class="aside extracontent">
			<?php
			// Show related content
			$out = Event::trigger('resources.onResourcesSub', array($this->model->resource, $this->option, 1));
			if (count($out) > 0)
			{
				foreach ($out as $ou)
				{
					if (isset($ou['html']))
					{
						echo $ou['html'];
					}
				}
			}
			// Show what's popular
			if ($this->tab == 'about')
			{
				echo \Hubzero\Module\Helper::renderModules('extracontent');
			}
			?>
		</div><!-- / .aside extracontent -->
	</section>

	<?php
	// Show course listings under 'about' tab
	if ($this->tab == 'about' && $ccount > 0)
	{
		$filters = array(
			'sortby' => Request::getVar('sortby', $this->model->params->get('sort_children', 'ordering')),
			'limit'  => Request::getInt('limit', 0),
			'start'  => Request::getInt('limitstart', 0),
			'id'     => $this->model->resource->id
		);

		// Get children
		$children = $this->model->children('standalone', $filters['limit'], $filters['start'], $filters['sortby']);

		// Build the results
		$sortbys = array(
			'date'     => Lang::txt('DATE'),
			'title'    => Lang::txt('TITLE'),
			'author'   => Lang::txt('AUTHOR'),
			'ordering' => Lang::txt('ORDERING')
		);
		if ($this->model->params->get('show_ranking'))
		{
			$sortbys['ranking'] = Lang::txt('RANKING');
		}
		?>
		<form method="get" id="series" action="<?php echo Route::url('index.php?option=' . $this->_option . '&' . ($this->model->resource->alias ? 'alias=' . $this->model->resource->alias : 'id=' . $this->model->resource->id)); ?>">
			<section class="section">
				<div class="subject">
					<h3>
						<?php echo Lang::txt('In This Workshop'); ?>
					</h3>

					<?php
					$this->view('_list', 'browse')
					     ->set('lines', $children)
					     ->set('show_edit', $this->model->access('edit'))
					     ->display();
					?>

					<div class="clear"></div><!-- / .clear -->

					<?php
					// Initiate paging for children
					jimport('joomla.html.pagination');
					$pageNav = new JPagination(
						$ccount,
						$filters['start'],
						$filters['limit']
					);
					$pageNav->setAdditionalUrlParam('id', $this->model->resource->id);
					$pageNav->setAdditionalUrlParam('sortby', $filters['sortby']);

					echo $pageNav->getListFooter();
					?>
				</div><!-- / .subject -->
				<div class="aside">
					<fieldset class="controls">
						<label for="sortby">
							<?php echo Lang::txt('COM_RESOURCES_SORT_BY'); ?>:
							<?php echo \Components\Resources\Helpers\Html::formSelect('sortby', $sortbys, $filters['sortby'], ''); ?>
						</label>
						<p class="submit">
							<input type="submit" value="<?php echo Lang::txt('COM_RESOURCES_GO'); ?>" />
						</p>
					</fieldset>
				</div><!-- / .aside -->
			</section>
		</form>
	<?php } ?>
<?php } ?>
