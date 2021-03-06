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
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

namespace Components\Resources\Tables;

/**
 * Resource table for DOI
 */
class Doi extends \JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  JDatabase
	 * @return  void
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__doi_mapping', 'rid', $db);
	}

	/**
	 * Validate data
	 *
	 * @return     boolean True if data is valid
	 */
	public function check()
	{
		if (trim($this->rid) == '')
		{
			$this->setError(\Lang::txt('Your entry must have a resource ID.'));
			return false;
		}
		return true;
	}

	/**
	 * Get the DOI for a resource revision
	 *
	 * @param   integer  $id            Resource ID
	 * @param   integer  $revision      Resource revision
	 * @param   integer  $versionid     Resource version
	 * @param   integer  $get_full_doi  Get the full DOI label?
	 * @return  mixed    False if error, string on success
	 */
	public function getDoi($id = NULL, $revision = NULL, $versionid = 0, $get_full_doi = 0)
	{
		if ($id == NULL)
		{
			$id = $this->rid;
		}
		if ($id == NULL)
		{
			return false;
		}
		if ($revision == NULL)
		{
			$revision = $this->local_revision;
		}
		if ($revision == NULL && !$versionid)
		{
			return false;
		}

		$query  = $get_full_doi ? "SELECT doi " : "SELECT d.doi_label as doi ";
		$query .= "FROM $this->_tbl as d WHERE d.rid=" . $this->_db->Quote($id) . " ";
		$query .= $revision
				? "AND d.local_revision=" . $this->_db->Quote($revision) . " LIMIT 1"
				: "AND d.versionid=" . $this->_db->Quote($versionid) . " LIMIT 1" ;

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Get the latest DOI for a resource
	 *
	 * @param   integer  $id            Resource ID
	 * @param   integer  $get_full_doi  Get the full FOI label?
	 * @return  mixed    False if error, string on success
	 */
	public function getLatestDoi($id = NULL, $get_full_doi = 0)
	{
		if ($id == NULL)
		{
			$id = $this->rid;
		}
		if ($id == NULL)
		{
			return false;
		}

		$query  = $get_full_doi ? "SELECT doi " : "SELECT d.doi_label as doi ";
		$query .= "FROM $this->_tbl as d ";
		$query .= "WHERE d.rid=" . $this->_db->Quote($id) . " ORDER BY d.doi_label DESC LIMIT 1";

		$this->_db->setQuery($query);
		return $this->_db->loadResult();
	}

	/**
	 * Load a record and bind to $this
	 *
	 * @param   integer  $rid       Resource ID
	 * @param   mixed    $revision  Resource revision
	 * @return  boolean  True on success
	 */
	public function loadDoi($rid = NULL, $revision = 0)
	{
		if ($rid === NULL || !$revision)
		{
			return false;
		}

		$this->_db->setQuery("SELECT * FROM $this->_tbl WHERE rid=" . $this->_db->Quote($rid) . " AND local_revision=" . $this->_db->Quote($revision) . " LIMIT 1");
		if ($result = $this->_db->loadAssoc())
		{
			return $this->bind($result);
		}
		else
		{
			$this->setError($this->_db->getErrorMsg());
			return false;
		}
	}

	/**
	 * Create a new DOI record
	 *
	 * @param   integer  $revision   Resource revision
	 * @param   mixed    $newlabel   New label
	 * @param   integer  $rid        Resource ID
	 * @param   string   $alias      Resource alias
	 * @param   integer  $versionid  Resource version
	 * @param   string   $doi        DOI
	 * @return  boolean  True on success
	 */
	public function saveDOI($revision = 0, $newlabel = 1, $rid = NULL, $alias='', $versionid = 0, $doi = '')
	{
		if ($rid == NULL)
		{
			return false;
		}

		$query = "INSERT INTO $this->_tbl (local_revision, doi_label, rid, alias, versionid, doi)
				VALUES (" . $this->_db->Quote($revision) . "," . $this->_db->Quote($newlabel) . "," . $this->_db->Quote($rid) . "," . $this->_db->Quote($alias) . ", " . $this->_db->Quote($versionid) . ", " . $this->_db->Quote($doi) . ")";
		$this->_db->setQuery($query);
		if (!$this->_db->query())
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/**
	 * Register a DOI
	 *
	 * @param   array   $authors   Authors of a resource
	 * @param   object  $config    JParameter
	 * @param   array   $metadata  Metadata
	 * @param   string  &$doierr   Container for error messages
	 * @return  mixed   False if error, string on success
	 */
	public function registerDOI($authors, $config, $metadata = array(), &$doierr='')
	{
		if (empty($metadata))
		{
			return false;
		}

		// Get configs
		$shoulder   = $config->get('doi_shoulder');
		$service    = $config->get('doi_newservice');
		$prefix     = $config->get('doi_newprefix');
		$userpw     = $config->get('doi_userpw');
		$xmlschema  = trim($config->get('doi_xmlschema', 'http://schema.datacite.org/meta/kernel-2.1/metadata.xsd' ), DS);
		$handle     = '';
		$doi        = '';

		if (!$shoulder || !$service)
		{
			$doierr .= 'Missing DOI configuration';
			return false;
		}

		// Collect metadata
		$metadata['publisher'] = htmlspecialchars($config->get('doi_publisher', \Config::get('sitename')));
		$metadata['pubYear']   = isset($metadata['pubYear']) ? $metadata['pubYear'] : date('Y');
		$metadata['language']  = 'en';

		// Clean up paths
		if (substr($service, -1, 1) == DS)
		{
			$service = substr($service, 0, (strlen($service) - 1));
		}
		if (substr($shoulder, -1, 1) == DS)
		{
			$shoulder = substr($shoulder, 0, (strlen($shoulder) - 1));
		}

		// Make service path
		$call  = $service . DS . 'shoulder' . DS . 'doi:' . $shoulder;
		$call .= $prefix ? DS . $prefix : DS;

		// Get config
		$live_site = rtrim(\Request::base(),'/');

		if (!$live_site || !isset($metadata['targetURL']) || !isset($metadata['title']))
		{
			$doierr .= 'Missing url, title or live site configuration';
			return false;
		}

		// Get first author / creator name
		if ($authors && count($authors) > 0)
		{
			$creatorName = $authors[0]->name;
		}
		else
		{
			$creatorName = \User::get('name');
		}

		// Format name
		$nameParts    = explode(" ", $creatorName);
		$metadata['creator']  = end($nameParts);
		$metadata['creator'] .= count($nameParts) > 1 ? ', ' . $nameParts[0] : '';

		// Start input
		$input  = "_target: " . $metadata['targetURL'] ."\n";
		$input .= "datacite.creator: " . $metadata['creator'] . "\n";
		$input .= "datacite.title: ". $metadata['title'] . "\n";
		$input .= "datacite.publisher: " . $metadata['publisher'] . "\n";
		$input .= "datacite.publicationyear: " . $metadata['pubYear'] . "\n";
		$input .= "datacite.resourcetype: Software" . "\n";
		$input .= "_profile: datacite";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $call);
		curl_setopt($ch, CURLOPT_USERPWD, $userpw);
		curl_setopt($ch, CURLOPT_POST, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain; charset=UTF-8', 'Content-Length: ' . strlen($input)));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($ch);

		/*returns HTTP Code for success or fail */
		$success = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($success === 201)
		{
			$out = explode('/', $output);
			$handle = trim(end($out));
		}
		else
		{
			if (empty($output))
			{
				$doierr = "$success: " . curl_error($ch);
			}
			else
			{
				$doierr = "$success: " . $output;
			}
			$doierr.= ' ' . $call;
			$handle = 0;
		}

		$handle = strtoupper($handle);
		$doi = $shoulder . DS . $handle;
		curl_close($ch);

		// Prepare XML data
		if ($handle)
		{
			$xdoc = new \DomDocument;
			$xmlfile = $this->getXml($authors, $metadata, $doi);

			//Load the xml document in the DOMDocument object
			$xdoc->loadXML($xmlfile);

			//Validate the XML file against the schema
			if ($xdoc->schemaValidate($xmlschema))
			{
				/*EZID parses text received based on new lines. */
				$input  = "_target: " . $metadata['targetURL'] ."\n";
				$input .= "datacite.creator: " . $metadata['creator'] . "\n";
				$input .= "datacite.title: ". $metadata['title'] . "\n";
				$input .= "datacite.publisher: " . $metadata['publisher'] . "\n";
				$input .= "datacite.publicationyear: " . $metadata['pubYear'] . "\n";
				$input .= "datacite.resourcetype: Software" . "\n";
				$input .= "_profile: datacite" . "\n";

				/*colons(:),percent signs(%),line terminators(\n),carriage returns(\r) are percent encoded for given input string  */
				$input  .= 'datacite: ' . strtr($xmlfile, array(":" => "%3A", "%" => "%25", "\n" => "%0A", "\r" => "%0D")) . "\n";

				// Make service path
				$call  = $service . DS . 'id' . DS . 'doi:' . $doi;

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $call);
				curl_setopt($ch, CURLOPT_USERPWD, $userpw);
				curl_setopt($ch, CURLOPT_POST, true);

				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/plain; charset=UTF-8', 'Content-Length: ' . strlen($input)));
				curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$output = curl_exec($ch);
				curl_close($ch);
			}
			else
			{
				$doierr .= "XML is invaild. DOI has been created but unable to upload XML as it is invalid. Please modify the created DOI with a valid XML .\n";
			}
		}

		return $handle ? $handle : NULL;
	}

	/**
	 * Generate the XML for creating a DOI
	 *
	 * @param   array   $authors   Authors of a resource
	 * @param   array   $metadata  Metadata to build XML from
	 * @param   string  $doi       DOI
	 * @return  string  XML
	 */
	public function getXml($authors, $metadata, $doi = 0)
	{
		$datePublished = isset($metadata['datePublished'])
					? $metadata['datePublished'] : date('Y-m-d');
		$dateAccepted  = date('Y-m-d');

		$xmlfile = '<?xml version="1.0" encoding="UTF-8"?><resource xmlns="http://datacite.org/schema/kernel-2.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://datacite.org/schema/kernel-2.1 http://schema.datacite.org/meta/kernel-2.1/metadata.xsd">
	     <identifier identifierType="DOI">' . $doi . '</identifier>';
		$xmlfile.='<creators>';
		if ($authors && count($authors) > 0)
		{
			foreach ($authors as $author)
			{
				$nameParts = explode(' ', $author->name);
				$name  = end($nameParts);
				$name .= count($nameParts) > 1 ? ', ' . $nameParts[0] : '';
				$xmlfile .= '<creator>';
				$xmlfile .= '	<creatorName>' . $name . '</creatorName>';
				$xmlfile .= '</creator>';
			}
		}
		else
		{
			$xmlfile .= '<creator>';
			$xmlfile .= '	<creatorName>' . $metadata['creator'] . '</creatorName>';
			$xmlfile .= '</creator>';
		}
		$xmlfile .= '</creators>';
		$xmlfile .= '<titles>';
		$xmlfile .= '	<title>' . $metadata['title'] . '</title>';
		$xmlfile .= '</titles>';
		$xmlfile .= '<publisher>' . $metadata['publisher'] . '</publisher>';
		$xmlfile .= '<publicationYear>' . $metadata['pubYear'] . '</publicationYear>';
		$xmlfile .= '<dates>';
		$xmlfile .= '	<date dateType="Valid">' . $datePublished . '</date>';
		$xmlfile .= '	<date dateType="Accepted">' . $dateAccepted . '</date>';
		$xmlfile .= '</dates>';
		$xmlfile .= '<language>' . $metadata['language'] . '</language>';

		$xmlfile .= '<resourceType resourceTypeGeneral="Software">Simulation Tool</resourceType>';
		if (isset($metadata['version']) && $metadata['version'] != '')
		{
			$xmlfile .= '<version>' . $metadata['version'] . '</version>';
		}
		if (isset($metadata['abstract']) && $metadata['abstract'] != '')
		{
			$xmlfile .= '<descriptions>';
			$xmlfile .= '	<description descriptionType="Other">' . $metadata['abstract'] . '</description>';
			$xmlfile .= '</descriptions>';
		}

		$xmlfile .= '</resource>';
		return $xmlfile;
	}
}

