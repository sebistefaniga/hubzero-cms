<?php

/**
 * Short description for 'file'
 * 
 * Long description (if any) ...
 * 
 * PHP version 5
 * 
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 * + Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 * + Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 * + Neither the name of the <ORGANIZATION> nor the names of its contributors
 * may be used to endorse or promote products derived
 * from this software without specific prior written permission.
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @category  CategoryName
 * @package   MathRenderer
 * @author    Author's name <author@mail.com>
 * @copyright 2011 Author's name
 * @license   http://www.opensource.org/licenses/bsd-license.php The BSD License
 * @version   CVS: $Id:$
 * @link      http://pear.php.net/package/MathRenderer
 * @see       References to other sections (if any)...
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

//-------------------------------------------------------------
// Contain everything related to <math> </math> parsing
// 
// Takes LaTeX fragments, sends them to a helper program (texvc) for rendering
// to rasterized PNG and HTML and MathML approximations. An appropriate
// rendering form is picked and returned.
//
// by Tomasz Wegrzanowski, with additions by Brion Vibber (2003, 2004)
//-------------------------------------------------------------

// Maths constants

/**
 * Description for ''MW_MATH_PNG''
 */
define( 'MW_MATH_PNG',    0 );

/**
 * Description for ''MW_MATH_SIMPLE''
 */
define( 'MW_MATH_SIMPLE', 1 );

/**
 * Description for ''MW_MATH_HTML''
 */
define( 'MW_MATH_HTML',   2 );

/**
 * Description for ''MW_MATH_SOURCE''
 */
define( 'MW_MATH_SOURCE', 3 );

/**
 * Description for ''MW_MATH_MODERN''
 */
define( 'MW_MATH_MODERN', 4 );

/**
 * Description for ''MW_MATH_MATHML''
 */
define( 'MW_MATH_MATHML', 5 );

/**
 * Short description for 'MathRenderer'
 * 
 * Long description (if any) ...
 */
class MathRenderer
{

	/**
	 * Description for 'mode'
	 * 
	 * @var integer
	 */
	var $mode = MW_MATH_MODERN;

	/**
	 * Description for 'tex'
	 * 
	 * @var string
	 */
	var $tex = '';

	/**
	 * Description for 'inputhash'
	 * 
	 * @var string
	 */
	var $inputhash = '';

	/**
	 * Description for 'hash'
	 * 
	 * @var string
	 */
	var $hash = '';

	/**
	 * Description for 'html'
	 * 
	 * @var string
	 */
	var $html = '';

	/**
	 * Description for 'mathml'
	 * 
	 * @var string
	 */
	var $mathml = '';

	/**
	 * Description for 'conservativeness'
	 * 
	 * @var integer
	 */
	var $conservativeness = 0;

	/**
	 * Short description for '__construct'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $tex Parameter description (if any) ...
	 * @param      array $params Parameter description (if any) ...
	 * @return     void
	 */
	public function __construct( $tex, $params=array() )
	{
		$this->tex = $tex;
		$this->params = $params;

		$this->config = JComponentHelper::getParams( 'com_wiki' );
 	}

	/**
	 * Short description for 'setOutputMode'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $mode Parameter description (if any) ...
	 * @return     void
	 */
	public function setOutputMode( $mode )
	{
		$this->mode = $mode;
	}

	/**
	 * Short description for '_makePath'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      string $path Parameter description (if any) ...
	 * @param      integer $mode Parameter description (if any) ...
	 * @return     mixed Return description (if any) ...
	 */
	private function _makePath( $path, $mode=0777 )
	{
		if (file_exists( $path )) {
		    return true;
		}
		$path = str_replace( '\\', '/', $path );
		$path = str_replace( '//', '/', $path );
		$parts = explode( '/', $path );

		$n = count( $parts );
		if ($n < 1) {
		    return mkdir( $path, $mode );
		} else {
			$path = '';
			for ($i = 0; $i < $n; $i++)
			{
				$path .= $parts[$i] . '/';
				if (!file_exists( $path )) {
					if (!mkdir( $path, $mode )) {
						return false;
					}
				}
			}
			return true;
		}
	}

	/**
	 * Short description for 'render'
	 * 
	 * Long description (if any) ...
	 * 
	 * @return     mixed Return description (if any) ...
	 */
	public function render()
	{
		$tmpDirectory = JPATH_ROOT.$this->config->get('tmppath');
		$inputEncoding = 'UTF-8';

		// To use inline TeX, you need to compile 'texvc' (in the 'math' subdirectory of
		// the MediaWiki package and have latex, dvips, gs (ghostscript), andconvert
		// (ImageMagick) installed and available in the PATH.
		// Please see math/README for more information.

		// Location of the texvc binary
		$b = '/usr/lib/hubzero/bin'; // dirname( __FILE__ );
		$texvc = $b.DS.'texvc';

		if ($this->mode == MW_MATH_SOURCE) {
			// No need to render or parse anything more!
			return ('$ '.htmlspecialchars( $this->tex ).' $');
		}
		if ($this->tex == '') {
			return;
		}

		if (!$this->_recall()) {
			// Ensure that the temp and output directories are available before continuing...
			if (!file_exists( $tmpDirectory )) {
				if (!$this->_makePath( $tmpDirectory )) {
					return $this->_error( 'math_bad_tmpdir' );
				}
			} elseif (!is_dir( $tmpDirectory ) || !is_writable( $tmpDirectory )) {
				return $this->_error( 'math_bad_tmpdir' );
			}
			// Ensure we have the texvc executable
			if (function_exists( 'is_executable' ) && !is_executable( $texvc )) {
				return $this->_error( 'math_notexvc' );
			}
			$cmd = $texvc . ' ' .
					escapeshellarg( $tmpDirectory ).' '.
					escapeshellarg( $tmpDirectory ).' '.
					escapeshellarg( $this->tex ).' '.
					escapeshellarg( $inputEncoding );

			//echo( "TeX: $cmd\n" );
			$contents = `$cmd`;
			//echo( "TeX output:\n $contents\n---\n" );

			/*
				Status codes and HTML/MathML transformations are returned on stdout.
				A rasterized PNG file will be written to the output directory, named
				for the MD5 hash code.

				texvc output format is like this:
				    +%5		ok, but not html or mathml
				    c%5%h	ok, conservative html, no mathml
				    m%5%h	ok, moderate html, no mathml
				    l%5%h	ok, liberal html, no mathml
				    C%5%h\0%m	ok, conservative html, with mathml
				    M%5%h\0%m	ok, moderate html, with mathml
				    L%5%h\0%m	ok, liberal html, with mathml
				    X%5%m	ok, no html, with mathml
				    S		syntax error
				    E		lexing error
				    F%s		unknown function %s
				    -		other error

				 \0 - null character
				 %5 - md5, 32 hex characters
				 %h - html code, without \0 characters
				 %m - mathml code, without \0 characters
			*/

			if (strlen($contents) == 0) {
				return $this->_error( 'math_unknown_error1' );
			}

			$retval = substr($contents, 0, 1);
			$errmsg = '';
			if (($retval == 'C') || ($retval == 'M') || ($retval == 'L')) {
				if ($retval == 'C') {
					$this->conservativeness = 2;
				} else if ($retval == 'M') {
					$this->conservativeness = 1;
				} else {
					$this->conservativeness = 0;
				}
				$outdata = substr($contents, 33);

				$i = strpos($outdata, "\000");

				$this->html = substr($outdata, 0, $i);
				$this->mathml = substr($outdata, $i+1);
			} else if (($retval == 'c') || ($retval == 'm') || ($retval == 'l'))  {
				$this->html = substr($contents, 33);
				if ($retval == 'c') {
					$this->conservativeness = 2;
				} else if ($retval == 'm') {
					$this->conservativeness = 1;
				} else {
					$this->conservativeness = 0;
				}
				$this->mathml = NULL;
			} else if ($retval == 'X') {
				$this->html = NULL;
				$this->mathml = substr ($contents, 33);
				$this->conservativeness = 0;
			} else if ($retval == '+') {
				$this->html = NULL;
				$this->mathml = NULL;
				$this->conservativeness = 0;
			} else {
				$errbit = htmlspecialchars( substr($contents, 1) );
				switch ($retval)
				{
					case 'E': $errmsg = $this->_error( 'math_lexing_error', $errbit );
					case 'S': $errmsg = $this->_error( 'math_syntax_error', $errbit );
					case 'F': $errmsg = $this->_error( 'math_unknown_function', $errbit );
					default:  $errmsg = $this->_error( 'math_unknown_error2', $errbit );
				}
			}

			if (!$errmsg) {
				 $this->hash = substr($contents, 1, 32);
			}

			if ($errmsg) {
				return $errmsg;
			}

			if (!preg_match("/^[a-f0-9]{32}$/", $this->hash)) {
				return $this->_error( 'math_unknown_error3' );
			}

			if (!file_exists( "$tmpDirectory/{$this->hash}.png" )) {
				return $this->_error( 'math_image_error' );
			}

			$hashpath = $this->_getHashPath();

			if (!file_exists( $hashpath )) {
				//if (!@wfMkdirParents( $hashpath, 0755 )) {
				if (!$this->_makePath( $hashpath )) {
					return $this->_error( 'math_bad_output' );
				}
			} elseif (!is_dir( $hashpath ) || !is_writable( $hashpath )) {
				return $this->_error( 'math_bad_output' );
			}

			if (!rename( "$tmpDirectory/{$this->hash}.png", "$hashpath/{$this->hash}.png" )) {
				return $this->_error( 'math_output_error' );
			}

			// Now save it back to the DB:
			$database =& JFactory::getDBO();
			$outmd5_sql = pack('H32', $this->hash);
			$md5_sql = pack('H32', $this->md5); // Binary packed, not hex

			$wm = new WikiPageMath( $database );
			$wm->loadByInput( $md5_sql );
			if (!$wm->id) {
				$wm->inputhash = $this->_encodeBlob($md5_sql);
				$wm->outputhash = $this->_encodeBlob($outmd5_sql);
				$wm->conservativeness = $this->conservativeness;
				$wm->html = $this->html;
				$wm->mathml = $this->mathml;
				if (!$wm->store()) {
					return $wm->getError();
				}
			}
		}

		return $this->_doRender();
	}

	/**
	 * Short description for '_error'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $msg Parameter description (if any) ...
	 * @param      string $append Parameter description (if any) ...
	 * @return     string Return description (if any) ...
	 */
	private function _error( $msg, $append = '' )
	{
		$mf = htmlspecialchars( 'math_failure' );
		$errmsg = htmlspecialchars( $msg );
		$source = htmlspecialchars( str_replace( "\n", ' ', $this->tex ) );
		return '<p class="error">'.$mf.' ('.$errmsg.$append.'): '.$source.'</p>'."\n";
	}

	/**
	 * Short description for '_recall'
	 * 
	 * Long description (if any) ...
	 * 
	 * @return     boolean Return description (if any) ...
	 */
	private function _recall()
	{
		$this->md5 = md5( $this->tex );

		$database =& JFactory::getDBO();
		$wm = new WikiPageMath( $database );
		$wm->loadByInput( $this->_encodeBlob(pack("H32", $this->md5)) );

		if ($wm->id) {
			// Tailing 0x20s can get dropped by the database, add it back on if necessary:
			$xhash = unpack( 'H32md5', $this->_decodeBlob($wm->outputhash) . "                " );
			$this->hash = $xhash['md5'];

			$this->conservativeness = $wm->conservativeness;
			$this->html = $wm->html;
			$this->mathml = $wm->mathml;

			if (file_exists( $this->_getHashPath() .DS. "{$this->hash}.png" )) {
				return true;
			}

			/*if (file_exists( $this->mathpath . "/{$this->hash}.png" )) {
				$hashpath = $this->_getHashPath();

				if (!file_exists( $hashpath )) {
					if (!@wfMkdirParents( $hashpath, 0755 )) {
						return false;
					}
				} elseif (!is_dir( $hashpath ) || !is_writable( $hashpath )) {
					return false;
				}
				if (function_exists( "link" )) {
					return link( $this->mathpath . "/{$this->hash}.png", $hashpath . "/{$this->hash}.png" );
				} else {
					return rename( $this->mathpath . "/{$this->hash}.png", $hashpath . "/{$this->hash}.png" );
				}
			}*/
		}

		// Missing from the database and/or the render cache
		return false;
	}

	// Select among PNG, HTML, or MathML output depending on

	/**
	 * Short description for '_doRender'
	 * 
	 * Long description (if any) ...
	 * 
	 * @return     string Return description (if any) ...
	 */
	private function _doRender()
	{
		if ($this->mode == MW_MATH_MATHML && $this->mathml != '') {
			return '<math xmlns="http://www.w3.org/1998/Math/MathML">'.$this->mathml.'</math>';
		}
		if (($this->mode == MW_MATH_PNG) || ($this->html == '') ||
		    (($this->mode == MW_MATH_SIMPLE) && ($this->conservativeness != 2)) ||
		    (($this->mode == MW_MATH_MODERN || $this->mode == MW_MATH_MATHML) && ($this->conservativeness == 0))
		) {
			return $this->_linkToMathImage();
		} else {
			return '<span class="texhtml">'.$this->html.'</span>';
		}
	}

	/**
	 * Short description for '_linkToMathImage'
	 * 
	 * Long description (if any) ...
	 * 
	 * @return     string Return description (if any) ...
	 */
	private function _linkToMathImage()
	{
		$url = $this->config->get('mathpath') .DS. substr($this->hash, 0, 1) .DS. substr($this->hash, 1, 1) .DS. substr($this->hash, 2, 1) .DS. "{$this->hash}.png";

		return '<img src="'.$url.'" class="tex" alt="'.$this->tex.'" />';
	}

	/**
	 * Short description for '_getHashPath'
	 * 
	 * Long description (if any) ...
	 * 
	 * @return     unknown Return description (if any) ...
	 */
	private function _getHashPath()
	{
		$path = JPATH_ROOT.$this->config->get('mathpath') .DS. substr($this->hash, 0, 1) .DS. substr($this->hash, 1, 1) .DS. substr($this->hash, 2, 1);
		return $path;
	}

	/**
	 * Short description for '_encodeBlob'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $b Parameter description (if any) ...
	 * @return     unknown Return description (if any) ...
	 */
	private function _encodeBlob($b)
	{
		return $b;
	}

	/**
	 * Short description for '_decodeBlob'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $b Parameter description (if any) ...
	 * @return     unknown Return description (if any) ...
	 */
	private function _decodeBlob($b)
	{
		return $b;
	}

	/**
	 * Short description for 'renderMath'
	 * 
	 * Long description (if any) ...
	 * 
	 * @param      unknown $tex Parameter description (if any) ...
	 * @param      array $params Parameter description (if any) ...
	 * @return     object Return description (if any) ...
	 */
	public static function renderMath( $tex, $params=array() )
	{
		$math = new MathRenderer( $tex, $params );
		return $math->render();
	}
}
