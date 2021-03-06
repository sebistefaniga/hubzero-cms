<?php
/**
 * @package		HUBzero CMS
 * @author		Shawn Rice <zooley@purdue.edu>
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
defined('_JEXEC') or die('Restricted access');

	$juser = JFactory::getUser();

	$cls = isset($this->cls) ? $this->cls : 'odd';

	if (!($this->comment instanceof \Plugins\Hubzero\Comments\Models\Comment))
	{
		$this->comment = new \Plugins\Hubzero\Comments\Models\Comment($this->comment);
	}
	$this->comment->set('option', $this->option);
	$this->comment->set('item_id', $this->obj_id);
	$this->comment->set('item_type', $this->obj_type);

	if ($this->obj->get('created_by') == $this->comment->get('created_by'))
	{
		$cls .= ' author';
	}

	if ($mark = $this->params->get('onCommentMark'))
	{
		if ($mark instanceof Closure)
		{
			$marked = (string) $mark($this->comment);
			$cls .= ($marked ? ' ' . $marked : '');
		}
	}

	$rtrn = $this->url ? $this->url : Request::getVar('REQUEST_URI', 'index.php?option=' . $this->option . '&id=' . $this->obj_id . '&active=comments', 'server');

	$this->comment->set('url', $rtrn);
?>
		<li class="comment <?php echo $cls; ?>" id="c<?php echo $this->comment->get('id'); ?>">
			<p class="comment-member-photo">
				<img src="<?php echo $this->comment->creator()->getPicture($this->comment->get('anonymous')); ?>" alt="" />
			</p>
			<div class="comment-content">
				<?php
				if ($this->params->get('comments_votable', 1))
				{
					$this->view('vote')
					     ->set('option', $this->option)
					     ->set('item', $this->comment)
					     ->set('params', $this->params)
					     ->set('url', $this->url)
					     ->display();
				}
				?>

				<p class="comment-title">
					<strong>
						<?php if (!$this->comment->get('anonymous')) { ?>
							<?php if ($this->comment->creator('public')) { ?>
								<a href="<?php echo Route::url($this->comment->creator()->getLink()); ?>"><!--
									--><?php echo $this->escape(stripslashes($this->comment->creator('name'))); ?><!--
								--></a>
							<?php } else { ?>
								<?php echo $this->escape(stripslashes($this->comment->creator('name'))); ?>
							<?php } ?>
						<?php } else { ?>
							<?php echo Lang::txt('PLG_HUBZERO_COMMENTS_ANONYMOUS'); ?>
						<?php } ?>
					</strong>
					<a class="permalink" href="<?php echo $this->comment->link(); ?>" title="<?php echo Lang::txt('PLG_HUBZERO_COMMENTS_PERMALINK'); ?>">
						<span class="comment-date-at"><?php echo Lang::txt('PLG_HUBZERO_COMMENTS_AT'); ?></span>
						<span class="time"><time datetime="<?php echo $this->comment->created(); ?>"><?php echo $this->comment->created('time'); ?></time></span>
						<span class="comment-date-on"><?php echo Lang::txt('PLG_HUBZERO_COMMENTS_ON'); ?></span>
						<span class="date"><time datetime="<?php echo $this->comment->created(); ?>"><?php echo $this->comment->created('date'); ?></time></span>
					</a>
				</p>

				<div class="comment-body">
					<?php
					if ($this->comment->isReported())
					{
						echo '<p class="warning">' . Lang::txt('PLG_HUBZERO_COMMENTS_REPORTED_AS_ABUSIVE') . '</p>';
					}
					else
					{
						echo $this->comment->content('parsed');
					}
					?>
				</div><!-- / .comment-body -->

				<?php if (!$this->comment->isReported() && $this->comment->attachments()->total()) { ?>
					<div class="comment-attachments">
						<?php
						foreach ($this->comment->attachments() as $attachment)
						{
							if (!trim($attachment->get('description')))
							{
								$attachment->set('description', $attachment->get('filename'));
							}

							if ($attachment->isImage())
							{
								if ($attachment->width() > 400)
								{
									$html = '<p><a href="' . Route::url($attachment->link()) . '"><img src="' . Route::url($attachment->link()) . '" alt="' . $attachment->get('description') . '" width="400" /></a></p>';
								}
								else
								{
									$html = '<p><img src="' . Route::url($attachment->link()) . '" alt="' . $attachment->get('description') . '" /></p>';
								}
							}
							else
							{
								$html = '<p class="attachment"><a href="' . Route::url($attachment->link()) . '" title="' . $attachment->get('description') . '">' . $attachment->get('description') . '</a></p>';
							}

							echo $html;
						}
						?>
					</div><!-- / .comment-attachments -->
				<?php } ?>

				<?php if (!$this->comment->isReported()) { ?>
					<p class="comment-options">
						<?php if (($this->params->get('access-delete-comment') && $this->comment->get('created_by') == $juser->get('id')) || $this->params->get('access-manage-comment')) { ?>
							<a class="icon-delete delete" href="<?php echo Route::url($this->comment->link('delete')); ?>" data-txt-confirm="<?php echo Lang::txt('PLG_HUBZERO_COMMENTS_CONFIRM'); ?>"><!--
								--><?php echo Lang::txt('PLG_HUBZERO_COMMENTS_DELETE'); ?><!--
							--></a>
						<?php } ?>
						<?php if (($this->params->get('access-edit-comment') && $this->comment->get('created_by') == $juser->get('id')) || $this->params->get('access-manage-comment')) { ?>
							<a class="icon-edit edit" href="<?php echo Route::url($this->comment->link('edit')); ?>"><!--
								--><?php echo Lang::txt('PLG_HUBZERO_COMMENTS_EDIT'); ?><!--
							--></a>
						<?php } ?>
						<?php if ($this->params->get('access-create-comment') && $this->depth < $this->params->get('comments_depth', 3)) { ?>
							<a class="icon-reply reply" data-txt-active="<?php echo Lang::txt('PLG_HUBZERO_COMMENTS_CANCEL'); ?>" data-txt-inactive="<?php echo Lang::txt('PLG_HUBZERO_COMMENTS_REPLY'); ?>"href="<?php echo Route::url($this->comment->link('reply')); ?>" rel="comment-form<?php echo $this->comment->get('id'); ?>"><!--
								--><?php echo Lang::txt('PLG_HUBZERO_COMMENTS_REPLY'); ?><!--
							--></a>
						<?php } ?>
							<a class="icon-abuse abuse" data-txt-flagged="<?php echo Lang::txt('PLG_HUBZERO_COMMENTS_REPORTED_AS_ABUSIVE'); ?>" href="<?php echo Route::url($this->comment->link('report')); ?>"><!--
								--><?php echo Lang::txt('PLG_HUBZERO_COMMENTS_REPORT_ABUSE'); ?><!--
							--></a>
					</p><!-- / .comment-options -->
				<?php } ?>
				<?php if ($this->params->get('access-create-comment') && $this->depth < $this->params->get('comments_depth', 3)) { ?>
					<div class="addcomment hide" id="comment-form<?php echo $this->comment->get('id'); ?>">
						<form action="<?php echo Route::url($this->comment->link('base')); ?>" method="post" enctype="multipart/form-data">
							<fieldset>
								<legend>
									<span><?php echo Lang::txt('PLG_HUBZERO_COMMENTS_REPLYING_TO', (!$this->comment->get('anonymous') ? $this->comment->get('name') : Lang::txt('PLG_HUBZERO_COMMENTS_ANONYMOUS'))); ?></span>
								</legend>

								<input type="hidden" name="comment[id]" value="0" />
								<input type="hidden" name="comment[item_id]" value="<?php echo $this->escape($this->comment->get('item_id')); ?>" />
								<input type="hidden" name="comment[item_type]" value="<?php echo $this->escape($this->comment->get('item_type')); ?>" />
								<input type="hidden" name="comment[parent]" value="<?php echo $this->comment->get('id'); ?>" />
								<input type="hidden" name="comment[created]" value="" />
								<input type="hidden" name="comment[created_by]" value="<?php echo $this->escape($juser->get('id')); ?>" />
								<input type="hidden" name="comment[state]" value="1" />
								<input type="hidden" name="option" value="<?php echo $this->escape($this->option); ?>" />
								<input type="hidden" name="action" value="commentsave" />

								<?php echo JHTML::_('form.token'); ?>

								<label for="comment_<?php echo $this->comment->get('id'); ?>_content">
									<span class="label-text"><?php echo Lang::txt('PLG_HUBZERO_COMMENTS_ENTER_COMMENTS'); ?></span>
									<?php
									echo $this->editor('comment[content]', '', 35, 4, 'comment_' . $this->comment->get('id') . '_content', array('class' => 'minimal no-footer'));
									?>
								</label>

								<label class="comment-<?php echo $this->comment->get('id'); ?>-file" for="comment-<?php echo $this->comment->get('id'); ?>-file">
									<span class="label-text"><?php echo Lang::txt('PLG_HUBZERO_COMMENTS_ATTACH_FILE'); ?>:</span>
									<input type="file" name="comment_file" id="comment-<?php echo $this->comment->get('id'); ?>-file" />
								</label>

								<label class="reply-anonymous-label" for="comment-<?php echo $this->comment->get('id'); ?>-anonymous">
									<input class="option" type="checkbox" name="comment[anonymous]" id="comment-<?php echo $this->comment->get('id'); ?>-anonymous" value="1" />
									<?php echo Lang::txt('PLG_HUBZERO_COMMENTS_POST_COMMENT_ANONYMOUSLY'); ?>
								</label>

								<p class="submit">
									<input type="submit" value="<?php echo Lang::txt('PLG_HUBZERO_COMMENTS_POST_COMMENT'); ?>" />
								</p>
							</fieldset>
						</form>
					</div><!-- / .addcomment -->
				<?php } ?>
			</div><!-- / .comment-content -->

			<?php
			if ($this->depth < $this->params->get('comments_depth', 3))
			{
				if ($this->comment->replies()->total())
				{
					$this->view('list')
					     ->set('option', $this->option)
					     ->set('comments', $this->comment->replies())
					     ->set('obj_type', $this->obj_type)
					     ->set('obj_id', $this->obj_id)
					     ->set('obj', $this->obj)
					     ->set('params', $this->params)
					     ->set('depth', $this->depth)
					     ->set('url', $this->url)
					     ->set('cls', $cls)
					     ->display();
				}
			}
			?>
		</li>