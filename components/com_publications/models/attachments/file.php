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

namespace Components\Publications\Models\Attachment;

use Components\Publications\Models\Attachment as Base;
use stdClass;
use ZipArchive;

/**
 * Handles a file attachment
 */
class File extends Base
{
	/**
	* Attachment type name
	*
	* @var		string
	*/
	protected	$_name = 'file';

	/**
	* Git handler
	*
	* @var
	*/
	protected	$_git = NULL;

	/**
	 * Get configs
	 *
	 * @return  boolean
	 */
	public function getConfigs( $element, $elementId, $pub, $blockParams )
	{
		$configs	= new stdClass;
		$typeParams = $element->typeParams;

		// replace current attachments?
		$configs->replace  	= Request::getInt( 'replace_current', 0, 'post');

		// which directory to copy files to
		$configs->directory = isset($typeParams->directory) && $typeParams->directory
							? $typeParams->directory : $pub->secret;

		// which subdirectory to copy files to
		$configs->subdir = isset($typeParams->subdir) && $typeParams->subdir
							? $typeParams->subdir : NULL;

		// Directory path within pub folder
		$configs->dirPath = $configs->subdir
							? $configs->directory . DS . $configs->subdir
							: $configs->directory;

		$defaultDirHierarchy = $configs->subdir ? 2 : 1;

		// Preserve directory structure when copying from project?
		$configs->dirHierarchy = isset($typeParams->dirHierarchy)
							? $typeParams->dirHierarchy : $defaultDirHierarchy;

		// Allow reuse of attachments to other elements
		$configs->reuse = isset($typeParams->reuse) ? $typeParams->reuse : 1;

		// Run outside script to check file content?
		$configs->scanScript  = isset($typeParams->scanScript) && $typeParams->scanScript
					? $typeParams->scanScript : false;

		// Fancy launcher?
		$configs->fancyLauncher = isset($typeParams->fancyLauncher)
			? $typeParams->fancyLauncher : 0;

		// Allow changes in non-draft version?
		$configs->freeze 	= isset($blockParams->published_editing)
							&& $blockParams->published_editing == 0
							&& ($pub->state == 1 || $pub->state == 5)
							? 1 : 0;

		// Verify file type against allowed before attaching?
		$configs->check = isset($blockParams->verify_types) ? $blockParams->verify_types : 0;

		// Default handler assigned in configs?
		$configs->handler = isset($typeParams->handler) && $typeParams->handler
							? $typeParams->handler : NULL;

		// Bundle multple files in an element together or serve independently?
		$configs->multiZip = isset($typeParams->multiZip) ? $typeParams->multiZip : 1;

		// Handler assigned in publication_handler_assoc?
		// TBD

		// Load handler
		if ($configs->handler)
		{
			$modelHandler = new \Components\Publications\Models\Handlers($this->_parent->_db);
			$configs->handler = $modelHandler->ini($configs->handler);
		}

		// Get project path
		$configs->path 	= \Components\Projects\Helpers\Html::getProjectRepoPath($pub->_project->alias);

		// Get publication paths
		$configs->pubBase = \Components\Publications\Helpers\Html::buildPubPath($pub->id, $pub->version_id, '', '', 1);
		$configs->pubPath = \Components\Publications\Helpers\Html::buildPubPath($pub->id, $pub->version_id, '', $configs->dirPath, 1);

		// Log path
		$configs->logPath = \Components\Publications\Helpers\Html::buildPubPath($pub->id, $pub->version_id, '', 'logs', 0);

		// Get default title
		$title = isset($element->title) ? str_replace('{pubtitle}', $pub->title, $element->title) : NULL;
		$configs->title = str_replace('{pubversion}', $pub->version_label, $title);

		// Get bundle name
		$versionParams 		  = new \JParameter( $pub->params );
		$bundleName			  = $versionParams->get('element' . $elementId . 'bundlename', $configs->title);
		$configs->bundleTitle = $bundleName ? $bundleName : $configs->title;
		$configs->bundleName  = $bundleName ? $bundleName . '.zip' : 'bundle.zip';

		// Allow rename?
		$configs->allowRename = false;

		// Include in bundle?
		$configs->includeInPackage = isset($typeParams->includeInPackage)
			&& $typeParams->includeInPackage == 0 ? false : true;

		// Bundle with dir hierarchy?
		$configs->bundleDirHierarchy = isset($typeParams->bundleDirHierarchy)
							? $typeParams->bundleDirHierarchy : 1;

		// Bundle directory
		$configs->bundleDirectory = isset($typeParams->bundleDirectory)
							? trim($typeParams->bundleDirectory) : NULL;

		// Archival path
		$tarname  = Lang::txt('Publication') . '_' . $pub->id . '.zip';
		$configs->archPath	= $configs->pubBase . DS . $tarname;

		return $configs;
	}

	/**
	 * Add to zip bundle
	 *
	 * @return  boolean
	 */
	public function addToBundle( $zip, $attachments, $element, $elementId,
		$pub, $blockParams, &$readme, $bundleDir)
	{
		// Get configs
		$configs  = $this->getConfigs($element->params, $elementId, $pub, $blockParams);
		$filePath = NULL;

		if ($configs->includeInPackage == false)
		{
			return false;
		}

		// Add inside bundles
		if ($configs->multiZip == 1 && $attachments && count($attachments) > 1)
		{
			$filePath  = $this->bundle($attachments, $configs, false);
			$bPath 	   = $configs->pubBase . DS . 'bundles';
			if (is_file($filePath))
			{
				$where  = $bundleDir;
				if ($configs->bundleDirectory)
				{
					$where .= DS . $configs->bundleDirectory;
				}
				if ($configs->directory
					&& strtolower($configs->bundleDirectory) != strtolower($configs->directory))
				{
					$where .= $configs->directory != $pub->secret ? DS . $configs->directory : '';
				}
				$where .= DS . basename($filePath);
				$zip->addFile($filePath, $where);
				$readme   .= "\n" . $element->label . ': ' . "\n";
				$readme   .= '>>> ' . str_replace($bPath . DS, '', $filePath) . "\n";
			}
		}
		elseif ($attachments)
		{
			$readme   .= "\n" . $element->label . ': ' . "\n";
			$added = array();

			// Add separately
			foreach ($attachments as $attach)
			{
				$filePath = $this->getFilePath($attach->path, $attach->id, $configs, $attach->params);

				$fileinfo = pathinfo($filePath);
				$a_dir  = $fileinfo['dirname'];
				$a_dir	= trim(str_replace($configs->pubPath, '', $a_dir), DS);

				$fPath  = $a_dir && $a_dir != '.' ? $a_dir . DS : '';
				$fPath .= basename($filePath);
				if (!$configs->bundleDirHierarchy)
				{
					$fPath = basename($filePath);
					if (in_array($fPath, $added))
					{
						$num   = \Components\Projects\Helpers\Html::getAppendedNumber($fPath);
						$num   = $num ? $num : 1;
						$fPath = \Components\Projects\Helpers\Html::fixFileName($fPath, '-' . $num);
					}
					else
					{
						$added[] = $fPath;
					}
				}

				$where  = $bundleDir;
				if ($configs->bundleDirectory)
				{
					$where .= DS . $configs->bundleDirectory;
				}
				if ($configs->directory
					&& strtolower($configs->bundleDirectory) != strtolower($configs->directory))
				{
					$where .= $configs->directory != $pub->secret ? DS . $configs->directory : '';
				}
				$where .= $configs->subdir ? DS . $configs->subdir : '';
				$where .= DS . $fPath;

				if ($zip->addFile($filePath, $where))
				{
					$readme   .= '>>> ' . str_replace($bundleDir . DS, '', $where) . "\n";
				}
			}
		}

		return true;
	}

	/**
	 * Draw list
	 *
	 * @return  boolean
	 */
	public function drawPackageList( $attachments, $element, $elementId,
		$pub, $blockParams, $authorized)
	{
		// Get configs
		$configs = $this->getConfigs($element->params, $elementId, $pub, $blockParams);

		if ($configs->includeInPackage == false)
		{
			return false;
		}

		$list = NULL;

		if (!$attachments)
		{
			return false;
		}

		$class = ($configs->multiZip == 1 && count($attachments) > 1) ? 'level2' : 'level1';

		// Draw bundles
		if ($configs->multiZip == 1 && $attachments && count($attachments) > 1)
		{
			$title = $configs->bundleTitle ? $configs->bundleTitle : 'Bundle';
			$icon  = '<img src="' . \Components\Projects\Helpers\Html::getFileIcon('zip') . '" alt="zip" />';

			// Bundle name
			$list .= '<li>' . $icon . ' ' . $title . '</li>';
		}
		// Draw directories
		if (($configs->multiZip == 2 && $configs->subdir) || $configs->bundleDirectory)
		{
			$icon  = '<img src="/plugins/projects/files/images/folder.gif" alt="" />';

			// Bundle name
			$name  = $configs->bundleDirectory ? $configs->bundleDirectory : $configs->subdir;
			$list .= '<li>' . $icon . ' ' . $name . '</li>';
			$class = 'level2';
		}
		// List individual
		foreach ($attachments as $attach)
		{
			$filePath = $this->getFilePath($attach->path, $attach->id, $configs, $attach->params);

			if (file_exists($filePath))
			{
				$fileinfo = pathinfo($filePath);
				$a_dir  = $fileinfo['dirname'];
				$a_dir	= trim(str_replace($configs->pubPath, '', $a_dir), DS);
				$fPath  = $a_dir && $a_dir != '.' ? $a_dir . DS : '';
				$fPath .= basename($filePath);

				$where = '';
				if ($configs->directory
					&& strtolower($configs->bundleDirectory) != strtolower($configs->directory))
				{
					$where .= $configs->directory != $pub->secret ? DS . $configs->directory : '';
				}

				$where .= $configs->subdir && $class == 'level1' ? DS . $configs->subdir : '';
				$where .= DS . $fPath;

				if (!$configs->bundleDirHierarchy)
				{
					$where = $configs->subdir && $class == 'level1' ? DS . $configs->subdir : '';
					$where .= DS . basename($filePath);
				}

				// Get ext
				$parts  = explode('.', $attach->path);
				$ext 	= count($parts) > 1 ? array_pop($parts) : NULL;
				$ext	= strtolower($ext);
				$icon   = '<img src="' . \Components\Projects\Helpers\Html::getFileIcon($ext) . '" alt="'.$ext.'" />';

				$list .= '<li class="' . $class . '"><span class="item-title">' . $icon . ' ' . trim($where, DS) . '</span>';
				$list .= '<span class="item-details">' . $attach->path . '</span>';
				$list .= '</li>';
			}
		}

		return $list;
	}

	/**
	 * Draw list
	 *
	 * @return  boolean
	 */
	public function drawList( $attachments, $element, $elementId,
		$pub, $blockParams, $authorized)
	{
		// Get configs
		$configs = $this->getConfigs($element->params, $elementId, $pub, $blockParams);

		$url =  Route::url('index.php?option=com_publications&task=serve&id='
				. $pub->id . '&v=' . $pub->version_number . '&el=' . $elementId );
		$url = preg_replace('/\/administrator/', '', $url);
		$html = '';

		// Is handler assigned?
		$handler =  $configs->handler;
		if ($handler)
		{
			// Handler will draw list
			$html = $handler->drawList($attachments, $configs, $pub, $authorized);
			if ($html)
			{
				return $html;
			}
		}
		$notice = $authorized ? ' (' . Lang::txt('unavailable')  . ')' : '';

		// Draw bundles
		if ($configs->multiZip && $attachments && count($attachments) > 1)
		{
			$title = $configs->bundleTitle ? $configs->bundleTitle : 'Bundle';
			$pop   = Lang::txt('Download') . ' ' . $title;

			$fpath = $this->bundle($attachments, $configs, false);

			// Get size
			$size = file_exists( $fpath ) ? filesize( $fpath ) : '';
			$size = $size ? \Hubzero\Utility\Number::formatBytes($size) : '';
			$ext  = 'zip';

			// Get file icon
			$icon  = '<img src="' . \Components\Projects\Helpers\Html::getFileIcon($ext) . '" alt="'.$ext.'" />';

			// Serve as bundle
			$html .= '<li>';
			$html .= is_file($fpath) && $authorized
					? '<a href="' . $url . '" title="' . $pop . '">' . $icon . ' ' . $title . '</a>'
					: $icon . ' ' . $title . $notice;
			$html .= '<span class="extras">';
			$html .= $ext ? '('.strtoupper($ext) : '';
			$html .= $size ? ' | '.$size : '';
			$html .= $ext ? ')' : '';
			if ($authorized === 'administrator')
			{
				$html .= ' <span class="edititem"><a href="index.php?option=com_publications&controller=items&task=editcontent&id='
				. $pub->id . '&el=' . $elementId . '&v=' . $pub->version_number . '">'
				. Lang::txt('COM_PUBLICATIONS_EDIT') . '</a></span>';
			}
			$html .= '</span>';
			$html .='</li>';
		}
		elseif ($attachments)
		{
			// Serve individually
			foreach ($attachments as $attach)
			{
				// Get size
				$fpath = $this->getFilePath($attach->path, $attach->id, $configs, $attach->params);
				$size = file_exists( $fpath ) ? filesize( $fpath ) : '';
				$size = $size ? \Hubzero\Utility\Number::formatBytes($size) : '';

				// Get ext
				$parts  = explode('.', $attach->path);
				$ext 	= count($parts) > 1 ? array_pop($parts) : NULL;
				$ext	= strtolower($ext);

				// Get file icon
				$icon  = '<img src="' . \Components\Projects\Helpers\Html::getFileIcon($ext) . '" alt="'.$ext.'" />';

				$itemUrl 	=  $url . '&a=' . $attach->id . '&download=1';
				$title 		= $attach->title ? $attach->title : $configs->title;
				$title 		= $title ? $title : basename($attach->path);
				$pop		= Lang::txt('Download') . ' ' . $title;
				$html .= '<li>';
				$html .= is_file($fpath) && $authorized
						? '<a href="' . $itemUrl . '" title="' . $pop . '">' . $icon . ' ' . $title . '</a>'
						: $icon . ' ' . $title . $notice;
				$html .= '<span class="extras">';
				$html .= $ext ? '('.strtoupper($ext) : '';
				$html .= $size ? ' | '.$size : '';
				$html .= $ext ? ')' : '';
				if ($authorized === 'administrator')
				{
					$html .= ' <span class="edititem"><a href="index.php?option=com_publications&controller=items&task=editcontent&id='
					. $pub->id . '&el=' . $elementId . '&v=' . $pub->version_number . '">'
					. Lang::txt('COM_PUBLICATIONS_EDIT') . '</a></span>';
				}
				$html .= '</span>';
				$html .='</li>';
			}
		}

		return $html;
	}

	/**
	 * Draw launcher
	 *
	 * @return  boolean
	 */
	public function drawLauncher( $element, $elementId, $pub, $blockParams, $elements, $authorized )
	{
		// Get configs
		$configs = $this->getConfigs($element->params, $elementId, $pub, $blockParams);

		$attachments = $pub->_attachments;
		$attachments = isset($attachments['elements'][$elementId])
					 ? $attachments['elements'][$elementId] : NULL;

		$showArchive = isset($pub->_curationModel->_manifest->params->show_archival)
				? $pub->_curationModel->_manifest->params->show_archival :  0;
	//	$showArchive = ($showArchive && file_exists($configs->archPath)) ? true : false;

		// Sort out attachments for this element
		$attachments = $this->_parent->getElementAttachments(
			$elementId,
			$attachments,
			$this->_name
		);

		$disabled = 0;
		$pop 	  = NULL;

		if ($pub->state == 0)
		{
			$pop 		= Lang::txt('COM_PUBLICATIONS_STATE_UNPUBLISHED_POP');
			$disabled 	= 1;
		}
		elseif (!$authorized)
		{
			$pop = $pub->access == 1
			     ? Lang::txt('COM_PUBLICATIONS_STATE_REGISTERED_POP')
			     : Lang::txt('COM_PUBLICATIONS_STATE_RESTRICTED_POP');
			$disabled = 1;
		}
		elseif (!$attachments)
		{
			$disabled = 1;
			$pop = Lang::txt('COM_PUBLICATIONS_ERROR_CONTENT_UNAVAILABLE');
		}

		$pop   = $pop ? '<p class="warning">' . $pop . '</p>' : '';

		// Is default handler assigned?
		$handler =  $configs->handler;
		if ($handler)
		{
			// TBD
			// Handler will draw launch link
		}

		$html = '';

		// Which role?
		$role = $element->params->role;

		$url = Route::url('index.php?option=com_publications&task=serve&id='
				. $pub->id . '&v=' . $pub->version_number )
				. '?el=' . $elementId;

		// Primary button
		if ($role == 1)
		{
			if (count($attachments) > 1)
			{
				$fpath = $this->bundle($attachments, $configs, false);
				$title = $configs->bundleTitle;
			}
			elseif ($attachments)
			{
				$attach = $attachments[0];
				$fpath = $this->getFilePath($attach->path, $attach->id, $configs, $attach->params);
				$title = $configs->title ? $configs->title : Lang::txt('Download content');
			}
			else
			{
				$fpath = NULL;
				$title = NULL;
			}

			if ($configs->fancyLauncher)
			{
				$html  = \Components\Publications\Helpers\Html::drawLauncher('ic-download', $pub, $url,
						$title, $disabled, $pop, 'download', $showArchive);
			}
			else
			{
				$label = Lang::txt('Download');
				// Link to bundle
				if ($showArchive == 1 || ($showArchive == 2 && count($attachments) > 1))
				{
					$url = Route::url('index.php?option=com_publications&id=' . $pub->id . '&task=serve&v=' . $pub->version_number . '&render=archive');
					$label .= ' ' . Lang::txt('Bundle');
					$title = $pub->title . ' ' . Lang::txt('Bundle');
				}
				else
				{
					// Get ext
					$parts  = explode('.', $fpath);
					$ext 	= count($parts) > 1 ? array_pop($parts) : NULL;
					$ext	= strtolower($ext);
					$label.= $ext ?  ' <span class="caption">(' . strtoupper($ext) . ')</span>' : '';
				}
				$class = 'btn btn-primary active icon-next';
				$class .= $disabled ? ' link_disabled' : '';

				$html  = \Components\Publications\Helpers\Html::primaryButton($class, $url, $label, NULL, $title, '', $disabled, $pop);
			}
		}
		elseif ($role == 2 && $attachments)
		{
			$html .= '<ul>';
			$html .= self::drawList( $attachments, $element, $elementId,
					$pub, $blockParams, $authorized);
			$html .= '</ul>';
		}

		return $html;
	}

	/**
	 * Transfer files from one version to another
	 *
	 * @return  boolean
	 */
	public function transferData( $elementparams, $elementId, $pub, $blockParams,
			$attachments, $oldVersion, $newVersion)
	{
		// Get configs
		$configs = $this->getConfigs($elementparams, $elementId, $pub, $blockParams);

		// Get configs for new version
		$typeParams = $elementparams->typeParams;
		$directory  = isset($typeParams->directory) && $typeParams->directory
					? $typeParams->directory : $newVersion->secret;

		$newConfigs = new stdClass;

		$juser = \JFactory::getUser();

		// Directory path within pub folder
		$newConfigs->dirPath = $configs->subdir
							? $directory . DS . $configs->subdir
							: $directory;
		// Build new path
		$newPath = \Components\Publications\Helpers\Html::buildPubPath(
			$pub->id,
			$newVersion->id,
			'',
			$newConfigs->dirPath,
			1
		);

		$newConfigs->pubPath = $newPath;
		$newConfigs->dirHierarchy = $configs->dirHierarchy;

		// Create new path
		if (!is_dir( $newPath ))
		{
			\JFolder::create( $newPath );
		}

		// Loop through attachments
		foreach ($attachments as $att)
		{
			// Make new attachment record
			$pAttach = new \Components\Publications\Tables\Attachment( $this->_parent->_db );
			if (!$pAttach->copyAttachment($att, $newVersion->id, $elementId, $juser->get('id') ))
			{
				continue;
			}

			// Get paths
			$copyFrom = $this->getFilePath($att->path, $att->id, $configs, $att->params);
			$copyTo   = $this->getFilePath($pAttach->path, $pAttach->id, $newConfigs, $pAttach->params);

			if (!is_file($copyFrom))
			{
				$pAttach->delete();
				continue;
			}

			// Make sure we have subdirectories
			if (!is_dir(dirname($copyTo)))
			{
				\JFolder::create( dirname($copyTo) );
			}

			// Copy file
			if (!\JFile::copy($copyFrom, $copyTo))
			{
				$pAttach->delete();
			}
			else
			{
				// Also make hash
				$md5hash = hash_file('sha256', $copyTo);
				$pAttach->content_hash = $md5hash;

				// Create hash file
				$hfile =  $copyTo . '.hash';
				if (!is_file($hfile))
				{
					$handle = fopen($hfile, 'w');
					fwrite($handle, $md5hash);
					fclose($handle);
					chmod($hfile, 0644);
				}
				$pAttach->store();

				// Produce thumbnail (if applicable)
				if ($configs->handler && $configs->handler->getName() == 'imageviewer')
				{
					$configs->handler->makeThumbnail($pAttach, $pub, $newConfigs);
				}
			}
		}

		return true;
	}

	/**
	 * Serve
	 *
	 * @return  boolean
	 */
	public function serve( $element, $elementId, $pub, $blockParams, $itemId = 0)
	{
		// Incoming
		$forceDownload = Request::getInt( 'download', 0 );		// Force downlaod action?

		// Get configs
		$configs = $this->getConfigs($element->params, $elementId, $pub, $blockParams);

		$attachments = $pub->_attachments;
		$attachments = isset($attachments['elements'][$elementId]) ? $attachments['elements'][$elementId] : NULL;

		// Sort out attachments for this element
		$attachments = $this->_parent->getElementAttachments($elementId, $attachments, $this->_name);

		if (!$forceDownload && $configs->handler)
		{
			// serve through handler
			// TBD
		}
		else
		{
			// Default action - download
			// Build download path
			$download = NULL;

			// Default serve - download
			if ($itemId)
			{
				foreach ($attachments as $attach)
				{
					if ($attach->id == $itemId)
					{
						$download = $this->getFilePath($attach->path, $attach->id, $configs, $attach->params);
						break;
					}
				}
			}
			elseif (count($attachments) > 1)
			{
				$overwrite = $pub->state == 1 ? false : true;
				$download  = $this->bundle($attachments, $configs, $overwrite);
			}
			elseif (count($attachments) == 1)
			{
				$download = $this->getFilePath($attachments[0]->path, $attachments[0]->id, $configs, $attachments[0]->params);
			}

			// Perform download
			if ($download && is_file($download))
			{
				// Log access
				if ( is_file(PATH_CORE . DS . 'components'. DS
						.'com_publications' . DS . 'tables' . DS . 'logs.php'))
				{
					require_once( PATH_CORE . DS . 'components'. DS
							.'com_publications' . DS . 'tables' . DS . 'logs.php');

					if ($pub->state == 1)
					{
						$pubLog = new \Components\Publications\Tables\Log($this->_parent->_db);
						$aType  = $element->params->role == 1 ? 'primary' : 'support';
						$pubLog->logAccess($pub, $aType, $configs->logPath);
					}
				}

				// Initiate a new content server and serve up the file
				$xserver = new \Hubzero\Content\Server();
				$xserver->filename($download);
				$xserver->disposition('attachment');
				$xserver->acceptranges(false); // @TODO fix byte range support
				$xserver->saveas(basename($download));

				if (!$xserver->serve())
				{
					// Should only get here on error
					throw new Exception(Lang::txt('PLG_PROJECTS_PUBLICATIONS_ERROR_SERVE'), 404);
				}
				else
				{
					exit;
				}
			}
			else
			{
				$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_ERROR_DOWNLOAD') );
				return false;
			}
		}

		return false;
	}

	/**
	 * Build file path depending on configs
	 *
	 * @return  string
	 */
	public function getFilePath( $path, $id, $configs = NULL, $params = NULL, $suffix = NULL )
	{
		if (!$suffix && $params)
		{
			// Get file attachment params
			$fParams = new \JParameter( $params );
			$suffix  = $fParams->get('suffix');
		}
		// Do we transfer file with subdirectories?
		if ($configs->dirHierarchy == 1)
		{
			$path = trim($path, DS);
			$name = $suffix ? \Components\Projects\Helpers\Html::fixFileName($path, ' (' . $suffix . ')') : $path;
			$fpath  = $configs->pubPath . DS . $name;
		}
		elseif ($configs->dirHierarchy == 2)
		{
			// Do not preserve dir hierarchy, but append number for same-name files
			$name 	= $suffix ? \Components\Projects\Helpers\Html::fixFileName(basename($path), ' (' . $suffix . ')') : basename($path);
			$fpath  = $configs->pubPath . DS . $name;
		}
		else
		{
			// Attach record number to file name
			$name 	= \Components\Projects\Helpers\Html::fixFileName(basename($path), '-' . $id);
			$fpath  = $configs->pubPath . DS . $name;
		}

		return $fpath;
	}

	/**
	 * Bundle files together
	 *
	 * @return  string
	 */
	public function bundle( $attachments, $configs = NULL, $overwrite = false )
	{
		if ($configs === NULL || count($attachments) < 2)
		{
			return false;
		}

		if ($configs->includeInPackage == false)
		{
			return false;
		}

		// Bundle path
		$path = $configs->pubBase . DS . 'bundles';

		// Bundle name
		$bundle	= $path . DS . $configs->bundleName;

		// Create pub version path
		if (!is_dir( $path ))
		{
			if (!\JFolder::create( $path ))
			{
				$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_UNABLE_TO_CREATE_PATH') );
				return false;
			}
		}

		// Serve existing bundle
		if (is_file($bundle) && $overwrite == false)
		{
			return $bundle;
		}

		$zip = new ZipArchive;
		if ($zip->open($bundle, ZipArchive::OVERWRITE) === TRUE)
		{
			$i = 0;
			foreach ($attachments as $attach)
			{
				$fpath = $this->getFilePath($attach->path, $attach->id, $configs, $attach->params);
				$fname = trim(str_replace($configs->pubPath . DS, '', $fpath), DS);

				if (is_file($fpath))
				{
					$zip->addFile($fpath, $fname);
					$i++;
				}
			}
			$zip->close();
		}

		return $bundle;
	}

	/**
	 * Save incoming file selection
	 *
	 * @return  boolean
	 */
	public function save( $element, $elementId, $pub, $blockParams, $toAttach = array() )
	{
		// Incoming selections
		if (empty($toAttach))
		{
			$selections = Request::getVar( 'selecteditems', '');
			$toAttach = explode(',', $selections);
		}

		// Get configs
		$configs = $this->getConfigs($element, $elementId, $pub, $blockParams);

		// Cannot make changes
		if ($configs->freeze)
		{
			return false;
		}

		// Nothing to change
		if (empty($toAttach) && !$configs->replace)
		{
			return false;
		}

		// Get current element attachments
		if ($configs->replace)
		{
			$attachments = $pub->_attachments;
			$attachments = isset($attachments['elements'][$elementId]) ? $attachments['elements'][$elementId] : NULL;

			// Sort out attachments for this element
			$attachments = $this->_parent->getElementAttachments($elementId, $attachments, $this->_name);

			// TBD
		}

		$juser = \JFactory::getUser();
		$uid   = $juser->get('id');
		if (!$uid)
		{
			return false;
		}

		// Git helper
		include_once( PATH_CORE . DS . 'components' . DS .'com_projects'
			. DS . 'helpers' . DS . 'githelper.php' );
		$this->_git = new \Components\Projects\Helpers\Git($configs->path);

		// Counter
		$i = 0;
		$a = 0;

		// Attach/refresh each selected item
		foreach ($toAttach as $identifier)
		{
			if (!trim($identifier))
			{
				continue;
			}

			$a++;
			$ordering = $i + 1;

			if ($this->addAttachment(urldecode($identifier), $pub, $configs, $uid, $elementId, $element, $ordering))
			{
				$i++;
			}
		}

		// Success
		if ($i > 0 && $i == $a)
		{
			$message = $this->get('_message') ? $this->get('_message') : Lang::txt('Selection successfully saved');
			$this->set('_message', $message);
		}

		return true;
	}

	/**
	 * Remove attachment
	 *
	 *
	 * @return     boolean or error
	 */
	public function removeAttachment($row, $element, $elementId, $pub, $blockParams)
	{
		$juser = \JFactory::getUser();
		$uid   = $juser->get('id');

		// Get configs
		$configs = $this->getConfigs($element, $elementId, $pub, $blockParams);

		// Cannot make changes
		if ($configs->freeze)
		{
			return false;
		}

		// Remove file
		if (!$this->unpublishAttachment($row, $pub, $configs))
		{
			$this->setError(Lang::txt('There was a problem removing published file'));
		}

		// Remove file and record
		if (!$this->getError())
		{
			$row->delete();
			$this->set('_message', Lang::txt('Item removed'));

			// Reflect the update in curation record
			$this->_parent->set('_update', 1);

			return true;
		}

		return false;
	}

	/**
	 * Update file attachment properties
	 *
	 *
	 * @return     boolean or error
	 */
	public function updateAttachment($row, $element, $elementId, $pub, $blockParams)
	{
		// Incoming
		$title 	= Request::getVar( 'title', '' );
		$name 	= Request::getVar( 'filename', '' );
		$thumb 	= Request::getInt( 'makedefault', 0 );

		$juser = \JFactory::getUser();
		$uid   = $juser->get('id');

		// Get configs
		$configs = $this->getConfigs($element, $elementId, $pub, $blockParams);

		// Cannot make changes
		if ($configs->freeze)
		{
			return false;
		}

		$gone  = is_file($configs->path . DS . $row->path) ? false : true;

		// Update label
		$row->title 		= $title;
		$row->modified_by 	= $uid;
		$row->modified 		= Date::toSql();

		// Update record
		if (!$row->store())
		{
			$this->setError(Lang::txt('Error updating item record'));
		}

		$this->set('_message', Lang::txt('Update successful'));

		// Reflect the update in curation record
		$this->_parent->set('_update', 1);

		// Make image default
		if ($thumb)
		{
			// Get handler model
			$modelHandler = new \Components\Publications\Models\Handlers( $this->_parent->_db );

			// Load image handler
			$handler = $modelHandler->ini('imageviewer');

			if (!$handler)
			{
				return false;
			}

			if ($handler->makeDefault($row, $pub, $configs))
			{
				$this->set('_message', Lang::txt('Updated default publication thumbnail'));
				return true;
			}
		}

		return true;
	}

	/**
	 * Add/edit file attachment
	 *
	 *
	 * @return     boolean or error
	 */
	public function addAttachment($filePath, $pub, $configs, $uid, $elementId, $element, $ordering = 1)
	{
		// Need to check against allowed types
		if ($configs->check)
		{
			if (!$this->checkAllowed(array($filePath), $element->typeParams->allowed_ext))
			{
				return false;
			}
		}

		// Get latest Git hash
		$vcs_hash = $this->_git->gitLog($filePath, '', 'hash');

		$new = 0;
		$update = 0;

		$objPA = new \Components\Publications\Tables\Attachment( $this->_parent->_db );
		if ($objPA->loadElementAttachment($pub->version_id, array( 'path' => $filePath),
			$elementId, $this->_name, $element->role))
		{
			// Update if new hash
			if ($vcs_hash && $vcs_hash != $objPA->vcs_hash)
			{
				$objPA->vcs_hash 				= $vcs_hash;
				$objPA->modified_by 			= $uid;
				$objPA->modified 				= Date::toSql();
				$update = 1; // Copy file again (new version)

				// Reflect the update in curation record
				$this->_parent->set('_update', 1);
			}
		}
		else
		{
			$new = 1;
			$objPA->publication_id 			= $pub->id;
			$objPA->publication_version_id 	= $pub->version_id;
			$objPA->path 					= $filePath;
			$objPA->type 					= $this->_name;
			$objPA->vcs_hash 				= $vcs_hash;
			$objPA->created_by 				= $uid;
			$objPA->created 				= Date::toSql();
			$objPA->role 					= $element->role;

			// Reflect the update in curation record
			$this->_parent->set('_update', 1);
		}

		$objPA->element_id 	= $elementId;
		$objPA->ordering 	= $ordering;

		// Copy file from project repo into publication directory
		if ($objPA->store())
		{
			// Check for conflict in file name
			if ($new == 1)
			{
				$suffix = $this->checkForDuplicate($configs->path . DS . $filePath, $objPA, $configs);
				if ($suffix)
				{
					$pa = new \Components\Publications\Tables\Attachment( $this->_parent->_db );
					$pa->saveParam($objPA, 'suffix', $suffix);
				}
			}

			// Copy file over to where to belongs
			if (!$this->publishAttachment($objPA, $pub, $configs, $update))
			{
				return false;
			}

			// Make default image (if applicable)
			if ($configs->handler  && $configs->handler->getName() == 'imageviewer')
			{
				$currentDefault = new \Components\Publications\Tables\Attachment( $this->_parent->_db );

				if (!$currentDefault->getDefault($pub->version_id))
				{
					$configs->handler->makeDefault($objPA, $pub, $configs);
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Check if file with same name exists in directory
	 *
	 * @param      object  		$objPA
	 * @param      object  		$pub
	 * @param      object  		$configs
	 *
	 * @return     boolean or error
	 */
	public function checkForDuplicate($copyFrom, $objPA, $configs, $suffix = 0)
	{
		// Get final path
		$copyTo = $this->getFilePath($objPA->path, $objPA->id, $configs, $objPA->params, $suffix);

		// check for name conflict
		if (file_exists($copyTo))
		{
			$suffix = $suffix + 1;
			return $this->checkForDuplicate($copyFrom, $objPA, $configs, $suffix );
		}
		else
		{
			return $suffix;
		}
	}

	/**
	 * Publish file attachment
	 *
	 * @param      object  		$objPA
	 * @param      object  		$pub
	 * @param      object  		$configs
	 * @param      boolean  	$update   force update of file
	 *
	 * @return     boolean or error
	 */
	public function publishAttachment($objPA, $pub, $configs, $update = 0)
	{
		// Create pub version path
		if (!is_dir( $configs->pubPath ))
		{
			if (!\JFolder::create( $configs->pubPath ))
			{
				$this->setError( Lang::txt('PLG_PROJECTS_PUBLICATIONS_PUBLICATION_UNABLE_TO_CREATE_PATH') );
				return false;
			}
		}

		$file 		= $objPA->path;
		$copyFrom 	= isset($configs->copyFrom) ? $configs->copyFrom : $configs->path . DS . $file;
		$copyTo 	= $this->getFilePath($file, $objPA->id, $configs, $objPA->params);

		// Copy
		if (is_file($copyFrom))
		{
			// If parent dir does not exist, we must create it
			if ($configs->dirHierarchy && !file_exists(dirname($copyTo)))
			{
				\JFolder::create(dirname($copyTo));
			}
			if (!is_file($copyTo) || $update)
			{
				\JFile::copy($copyFrom, $copyTo);
			}
		}

		// Store content hash
		if (is_file($copyTo))
		{
			$md5hash = hash_file('sha256', $copyTo);
			$objPA->content_hash = $md5hash;

			// Create hash file
			$hfile =  $copyTo . '.hash';
			if (!is_file($hfile))
			{
				$handle = fopen($hfile, 'w');
				fwrite($handle, $md5hash);
				fclose($handle);
				chmod($hfile, 0644);
			}
			$objPA->store();

			// Scan attachment and record scan status
			if ($configs->scanScript)
			{
				self::scanFile($objPA, $copyTo, $pub, $configs);
			}

			// Produce thumbnail (if applicable)
			if ($configs->handler && $configs->handler->getName() == 'imageviewer')
			{
				$configs->handler->makeThumbnail($objPA, $pub, $configs);
			}
		}
		else
		{
			return false;
		}

		return true;
	}

	/**
	 * Unpublish file attachment
	 *
	 * @param      object  		$objPA
	 * @param      object  		$pub
	 * @param      object  		$configs
	 *
	 * @return     boolean or error
	 */
	public function unpublishAttachment($row, $pub, $configs)
	{
		// Get file path
		$deletePath = $this->getFilePath($row->path, $row->id, $configs, $row->params);

		// Hash file
		$hfile =  $deletePath . '.hash';

		// Delete file
		if (is_file( $deletePath ))
		{
			if (\JFile::delete($deletePath))
			{
				// Also delete hash file
				if (is_file($hfile))
				{
					\JFile::delete($hfile);
				}

				// Remove any related files managed by handler
				if ($configs->handler)
				{
					$configs->handler->cleanUp($deletePath, $configs);
				}
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Check completion status
	 *
	 * @return  object
	 */
	public function getStatus( $element, $attachments )
	{
		$status = new \Components\Publications\Models\Status();

		// Get requirements to check against
		$max 		= $element->max;
		$min 		= $element->min;
		$role 		= $element->role;
		$params		= $element->typeParams;
		$required	= $element->required;
		$counter 	= count($attachments);

		// Check for correct number of files
		if ($min > 0 && $counter < $min)
		{
			if ($counter)
			{
				$status->setError( Lang::txt('Need at least ' . $min . ' file(s)') );
			}
			else
			{
				// No files
				$status->status = 0;

				if ($required)
				{
					return $status;
				}
			}
		}
		elseif ($max > 0 && $counter > $max)
		{
			$status->setError( Lang::txt('Maximum ' . $max . ' files allowed') );
		}
		// Check allowed formats
		elseif (!self::checkAllowed($attachments, $params->allowed_ext))
		{
			if ($counter && !empty($params->allowed_ext))
			{
				$error = Lang::txt('Error: wrong file type. Allowed file type(s): ');
				foreach ($params->allowed_ext as $ext)
				{
					$error .= ' ' . $ext .',';
				}
				$error = substr($error, 0, strlen($error) - 1);
				$status->setError( $error );
			}
			else
			{
				$status->setError( Lang::txt('File format not allowed') );
			}
		}
		// Check required formats
		elseif (!self::checkRequired($attachments, $params->required_ext))
		{
			$status->setError( Lang::txt('Missing a file of required format') );
		}

		if (!$required)
		{
			$status->status = $counter ? 1 : 2;
			return $status;
		}

		$status->status = $status->getError() ? 0 : 1;

		return $status;
	}

	/**
	 * Run script to check for required file content
	 *
	 * @return  object
	 */
	public function scanFile ( $objPA, $filePath, $pub, $configs )
	{
		if (is_file($filePath))
		{
			// perform scan
			// TBD
			// Record scan status
		}

		return true;
	}

	/**
	 * Check for allowed formats
	 *
	 * @return  object
	 */
	public function checkAllowed( $attachments, $formats = array() )
	{
		if (empty($attachments))
		{
			return true;
		}

		if (empty($formats))
		{
			return true;
		}

		foreach ($attachments as $attach)
		{
			$file = isset($attach->path) ? $attach->path : $attach;
			$ext = \Components\Projects\Helpers\Html::getFileExtension($file);

			if ($ext && !in_array(strtolower($ext), $formats))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Check for required formats
	 *
	 * @return  object
	 */
	public function checkRequired( $attachments, $formats = array() )
	{
		if (empty($attachments))
		{
			return true;
		}

		if (empty($formats))
		{
			return true;
		}

		$i = 0;
		foreach ($attachments as $attach)
		{
			$file = isset($attach->path) ? $attach->path : $attach;
			$ext = \Components\Projects\Helpers\Html::getFileExtension($file);

			if ($ext && in_array(strtolower($ext), $formats))
			{
				$i++;
			}
		}

		if ($i < count($formats))
		{
			return false;
		}

		return true;
	}

	/**
	 * Build Data object
	 *
	 * @return  HTML string
	 */
	public function buildDataObject($att, $view, $i = 1)
	{
		// Get configs
		$configs  = $this->getConfigs($view->manifest->params, $view->elementId, $view->pub, $view->master->params);

		$data 		= new stdClass;
		$data->path = str_replace($configs->path . DS, '', $att->path);
		$parts 		= explode('.', $data->path);
		$data->ext 	= strtolower(end($parts));

		// Customize title
		$defaultTitle	= $view->manifest->params->title
						? str_replace('{pubtitle}', $view->pub->title,
						$view->manifest->params->title) : NULL;
		$defaultTitle	= $view->manifest->params->title
						? str_replace('{pubversion}', $view->pub->version_label,
						$defaultTitle) : NULL;
		// Allow rename?
		$allowRename = isset($view->manifest->params->typeParams->allowRename)
					 ? $view->manifest->params->typeParams->allowRename
					 : false;

		// Set default title
		$incNum				= $view->manifest->params->max > 1 ? ' (' . $i . ')' : '';
		$dTitle				= $defaultTitle ? $defaultTitle . $incNum : basename($data->path);
		$data->title 		= $att->title && $att->title != $defaultTitle
							? $att->title : $dTitle;

		$data->ordering 	= $i;
		$data->editUrl  	= $view->editUrl;
		$data->id			= $att->id;
		$data->props		= $view->master->block . '-' . $view->master->sequence . '-' . $view->elementId;
		;
		$data->pid			= $view->pub->id;
		$data->vid			= $view->pub->version_id;
		$data->version		= $view->pub->version_number;
		$data->projectPath  = $configs->path;
		$data->git		    = $view->git;
		$data->pubPath	    = $configs->pubPath;
		$data->md5		    = $att->content_hash;
		$data->viewer	    = $view->viewer;
		$data->allowRename  = $allowRename;
		$data->downloadUrl  = Route::url('index.php?option=com_publications&task=serve&id='
							. $view->pub->id . '&v=' . $view->pub->version_number )
							. '?el=' . $view->elementId . '&amp;a=' . $att->id . '&amp;download=1';

		// Is attachment (image) also publication thumbnail
		$params = new \JParameter( $att->params );
		$data->pubThumb = $params->get('pubThumb', NULL);
		$data->suffix = $params->get('suffix', NULL);

		$data->hash	  	= $att->vcs_hash;
		$data->gone 	= is_file($configs->path . DS . $att->path) ? false : true;

		// Get file size
		$data->size		= $att->vcs_hash
						? $view->git->gitLog($configs->path, $att->path, $att->vcs_hash, 'size') : NULL;
		$data->gitStatus= $data->gone
					? Lang::txt('PLG_PROJECTS_PUBLICATIONS_MISSING_FILE')
					: NULL;

		return $data;
	}

	/**
	 * Draw attachment
	 *
	 * @return  HTML string
	 */
	public function drawAttachment($data, $params, $handler = NULL)
	{
		// Check if we have an alternative view of attachments for the handler
		$html = is_object($handler) ? $handler->drawAttachment($data, $params) : NULL;
		if ($html)
		{
			return $html;
		}

		// Output HTML
		$view = new \Hubzero\Plugin\View(
			array(
				'folder'	=>'projects',
				'element'	=>'publications',
				'name'		=>'attachments',
				'layout'	=> $this->_name
			)
		);

		$view->data 	= $data;
		$view->params   = $params;

		if ($this->getError())
		{
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}
}