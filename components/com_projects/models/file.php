<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2013 Purdue University. All rights reserved.
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
 * @author    Alissa Nedossekina <alisa@purdue.edu>
 * @copyright Copyright 2005-2013 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Projects\Models;

use Hubzero\Base\Object;
use stdClass;

/**
 * Project File model
 */
class File extends Object
{
	/**
	 * Container for properties
	 *
	 * @var array
	 */
	private $_data = array();

	/**
	 * Constructor
	 *
	 * @param	string	$path
	 * @return  void
	 */
	public function __construct($localPath = NULL, $repoPath = NULL)
	{
		$this->set('localPath', $localPath); // Path to item within repo

		$fullPath = trim($repoPath, DS) . DS . trim($localPath, DS);
		$this->set('fullPath', DS . trim($fullPath, DS)); // Full server path to item

		// Set defaults
		$this->defaults();
	}

	/**
	 * Check if a property is set
	 *
	 * @param      string $property Name of property to set
	 * @return     boolean True if set
	 */
	public function __isset($property)
	{
		return isset($this->_data[$property]);
	}

	/**
	 * Set a property
	 *
	 * @param      string $property Name of property to set
	 * @param      mixed  $value    Value to set property to
	 * @return     void
	 */
	public function __set($property, $value)
	{
		$this->_data[$property] = $value;
	}

	/**
	 * Unset a property
	 *
	 * @param      string $property Name of property to set
	 * @return     void
	 */
	public function clear($property)
	{
		if (isset($this->_data[$property]))
		{
			unset($this->_data[$property]);
		}
	}

	/**
	 * Get a property
	 *
	 * @param      string $property Name of property to retrieve
	 * @return     mixed
	 */
	public function __get($property)
	{
		if (isset($this->_data[$property]))
		{
			return $this->_data[$property];
		}
	}

	/**
	 * Build basic metadata object
	 *
	 * @return  mixed
	 */
	public function defaults()
	{
		$this->set('type', 'file');
		$this->set('name', basename($this->get('localPath')));

		// Directory path within repo
		if (dirname($this->get('localPath')) !== '.')
		{
			$this->set('dirname', dirname($this->get('localPath')));
		}

		$this->set('ext', \Components\Projects\Helpers\Html::getFileExtension($this->get('localPath')));
	}

	/**
	 * Get file size
	 *
	 * @return  mixed
	 */
	public function getSize($formatted = false)
	{
		if (!$this->get('size'))
		{
			$this->setSize();
		}

		return $formatted ? $this->get('formattedSize') : $this->get('size');
	}

	/**
	 * Set file size
	 *
	 * @return  mixed
	 */
	public function setSize($size = NULL)
	{
		if (intval($size) > 0)
		{
			$this->set('size', $size);
		}
		if ($this->get('size'))
		{
			// Already set
			return $this->get('size');
		}

		// Get size for local
		if (!$this->get('remote'))
		{
			$fileSystem = new \Hubzero\Filesystem\Filesystem();
			$this->set('size', $fileSystem->size($this->get('fullPath')));
		}

		// Formatted size
		if ($this->get('size'))
		{
			$this->set('formattedSize', \Hubzero\Utility\Number::formatBytes($this->get('size')));
		}

		return $this->get('size');
	}

	/**
	 * Get mime type
	 *
	 * @return  mixed
	 */
	public function getMimeType()
	{
		if (!$this->get('mimeType'))
		{
			$this->setMimeType();
		}

		return $this->get('mimeType');
	}

	/**
	 * Set mime type
	 *
	 * @return  mixed
	 */
	public function setMimeType()
	{
		if (!$this->get('mimeType') && $this->get('type') == 'file')
		{
			$helper = new \Hubzero\Content\Mimetypes();

			$mTypeParts = explode(';', $helper->getMimeType($this->get('fullPath')));
			$this->set('mimeType', $this->_fixUpMimeType($mTypeParts[0]));
		}
	}

	/**
	 * Set md5Hash
	 *
	 * @return  mixed
	 */
	public function setMd5Hash()
	{
		if (!$this->get('md5Hash') && is_file($this->get('fullPath')))
		{
			$this->set('md5Hash', hash_file('md5', $this->get('fullPath')));
		}
	}

	/**
	 * Get item parent directories
	 *
	 * @return     mixed
	 */
	public function getParents()
	{
		if ($this->get('parents'))
		{
			return $this->get('parents');
		}
		else
		{
			return $this->setParents();
		}
	}

	/**
	 * Set item parents
	 *
	 * @return     mixed
	 */
	public function setParents()
	{
		if (!$this->get('dirname'))
		{
			return false;
		}
		if ($this->get('parents'))
		{
			return $this->get('parents');
		}

		$parents = new stdClass;
		$dirParts = explode('/', $this->get('dirname'));

		$i = 1;
		$collect = '';

		foreach ($dirParts as $part)
		{
			if (!trim($part))
			{
				break;
			}
			$collect .= DS . $part;
			$parents->$i = trim($collect, DS);
			$i++;
		}

		$this->set('parents', $parents);
		return $parents;
	}

	/**
	 * Build file metadata object for a folder
	 *
	 * @return  mixed
	 */
	public function setFolder()
	{
		$fullPath = str_replace($this->get('localPath'), '', $this->get('fullPath'));

		// Folder metadata
		$this->set('type', 'folder');
		$this->set('name', basename($this->get('dirname')));
		$this->set('localPath', $this->get('dirname'));

		$this->set('fullPath', $fullPath . $this->get('localPath'));

		$dirname = dirname($this->get('dirname')) == '.'
				? NULL : dirname($this->get('dirname'));
		$this->set('dirname', $dirname);
		$this->setParents();

		$this->clear('ext');
		$this->setIcon('folder');
	}

	/**
	 * Fix up some mimetypes
	 *
	 * @param      string $mimeType
	 * @return     string
	 */
	protected function _fixUpMimeType ($mimeType = NULL)
	{
		if ($this->get('ext'))
		{
			switch (strtolower($this->get('ext')))
			{
				case 'key':
					$mimeType = 'application/x-iwork-keynote-sffkey';
					break;

				case 'ods':
					$mimeType = 'application/vnd.oasis.opendocument.spreadsheet';
					break;

				case 'wmf':
					$mimeType = 'application/x-msmetafile';
					break;

				case 'tex':
					$mimeType = 'application/x-tex';
					break;
			}
		}

		return $mimeType;
	}

	/**
	 * Get file icon image
	 *
	 * @param      boolean $basename
	 * @return     string
	 */
	public function getIcon ($basename = false)
	{
		if (!$this->get('icon'))
		{
			$this->setIcon($this->get('ext'), $basename);
		}
		return $this->get('icon');
	}

	/**
	 * Set file icon image
	 *
	 * @param      string  $ext
	 * @param      boolean $basename
	 * @param      string  $icon
	 * @return     string
	 */
	public function setIcon ($ext = NULL, $basename = false, $icon = '')
	{
		if ($this->get('icon') && $this->get('ext') == $ext)
		{
			return $this->get('icon');
		}
		if ($icon)
		{
			$this->set('icon', $icon);
			return $this->get('icon');
		}

		// Directory where images are stored
		$basePath = "/plugins/projects/files/images/";

		$ext = $ext ? $ext : $this->get('ext');
		switch (strtolower($ext))
		{
			case 'pdf':
				$icon = 'page_white_acrobat';
				break;
			case 'txt':
			case 'css':
			case 'rtf':
			case 'sty':
			case 'cls':
			case 'log':
				$icon = 'page_white_text';
				break;
			case 'sql':
				$icon = 'page_white_sql';
				break;
			case 'm':
				$icon = 'page_white_matlab';
				break;
			case 'dmg':
			case 'exe':
			case 'va':
			case 'ini':
				$icon = 'page_white_gear';
				break;
			case 'eps':
			case 'ai':
			case 'wmf':
				$icon = 'page_white_vector';
				break;
			case 'php':
				$icon = 'page_white_php';
				break;
			case 'tex':
			case 'ltx':
				$icon = 'page_white_tex';
				break;
			case 'swf':
				$icon = 'page_white_flash';
				break;
			case 'key':
				$icon = 'page_white_keynote';
				break;
			case 'numbers':
				$icon = 'page_white_numbers';
				break;
			case 'pages':
				$icon = 'page_white_pages';
				break;
			case 'html':
			case 'htm':
				$icon = 'page_white_code';
				break;
			case 'xls':
			case 'xlsx':
			case 'tsv':
			case 'csv':
			case 'ods':
				$icon = 'page_white_excel';
				break;
			case 'ppt':
			case 'pptx':
			case 'pps':
				$icon = 'page_white_powerpoint';
				break;
			case 'mov':
			case 'mp4':
			case 'm4v':
			case 'avi':
				$icon = 'page_white_film';
				break;
			case 'jpg':
			case 'jpeg':
			case 'gif':
			case 'tiff':
			case 'bmp':
			case 'png':
				$icon = 'page_white_picture';
				break;
			case 'mp3':
			case 'aiff':
			case 'm4a':
			case 'wav':
				$icon = 'page_white_sound';
				break;
			case 'zip':
			case 'rar':
			case 'gz':
			case 'sit':
			case 'sitx':
			case 'zipx':
			case 'tar':
			case '7z':
				$icon = 'page_white_compressed';
				break;
			case 'doc':
			case 'docx':
				$icon = 'page_white_word';
				break;

			case 'folder':
				$icon = 'folder';
				break;

			// Google files
			case 'gsheet':
				$icon = 'google/sheet';
				break;
			case 'gdoc':
				$icon = 'google/doc';
				break;
			case 'gslide':
				$icon = 'google/presentation';
				break;
			case 'gdraw':
				$icon = 'google/drawing';
				break;
			case 'gform':
				$icon = 'google/form';
				break;

			default:
				$icon = 'page_white';
				break;
		}

		$result = $basename ? basename($icon) :  $basePath . $icon . '.gif';
		$this->set('icon', $result);
	}

	/**
	 * Get folder structure level
	 *
	 * @param      array	$files
	 * @param      array	$params
	 *
	 * @return     integer
	 */
	public function getDirLevel ($dirPath = '')
	{
		if (!trim($dirPath))
		{
			return 0;
		}
		$dirParts = explode('/', $dirPath);
		return count($dirParts);
	}
}