<?php
namespace Hubzero\Http;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as BaseRequest;
use Hubzero\Utility\String;

class Request extends BaseRequest
{
	/**
	 * Array of filters
	 * 
	 * @var  array
	 */
	static $filters = array(
		'int'   => '/-?[0-9]+/',
		'float' => '/-?[0-9]+(\.[0-9]+)?/',
		'cmd'   => '/[^A-Z0-9_\.-]/i',
		'word'  => '/[^A-Z_]/i'
	);

	/**
	 * Get var
	 * 
	 * @param   string   $key      Request key
	 * @param   mixed    $default  Default value
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @param   string   $type     Return type for the variable. [!] Deprecated. Joomla legacy support.
	 * @param   string   $mask     Filter mask for the variable. [!] Deprecated. Joomla legacy support.
	 * @return  itneger  Request variable
	 */
	public function getVar($key, $default = null, $hash = 'input', $type = 'none', $mask = 0)
	{
		$hash = strtolower($hash);

		switch ($hash)
		{
			case 'server':
				return $this->server($key, $default);
			break;

			case 'cookie':
				return $this->cookie($key, $default);
			break;

			case 'files':
				return $this->file($key, $default);
			break;

			case 'post':
				return $this->request($key, $default);
			break;

			case 'get':
				return $this->query($key, $default);
			break;

			default:
				return $this->input($key, $default);
			break;
		}
	}

	/**
	 * Get integer
	 * 
	 * @param   string   $key      Request key
	 * @param   mixed    $default  Default value
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @return  itneger  Request variable
	 */
	public function getInt($key, $default = 0, $hash = 'input')
	{
		return (int) preg_replace(static::$filters['int'], '', $this->getVar($key, $default, $hash));
	}

	/**
	 * Get unsigned integer
	 * 
	 * @param   string   $key      Request key
	 * @param   mixed    $default  Default value
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @return  integer  Request variable
	 */
	public function getUInt($name, $default = 0, $hash = 'input')
	{
		return abs($this->getInt($name, $default, $hash));
	}

	/**
	 * Get float
	 * 
	 * @param   string   $key      Request key
	 * @param   mixed    $default  Default value
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @return  integer  Request variable
	 */
	public function getFloat($name, $default = 0.0, $hash = 'input')
	{
		return preg_replace(static::$filters['float'], '', $this->getVar($key, $default, $hash));
	}

	/**
	 * Get boolean
	 *
	 * @param   string   $key      Request key
	 * @param   mixed    $default  Default value
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @return  boolean  Request variable
	 */
	public function getBool($key = null, $default = null, $hash = 'input')
	{
		return (bool) $this->getVar($key, $default, $hash);
	}

	/**
	 * Get word
	 * 
	 * @param   string  $key      Request key
	 * @param   mixed   $default  Default value
	 * @param   string  $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @return  string  Request variable
	 */
	public function getWord($key, $default = null, $hash = 'input')
	{
		return preg_replace(static::$filters['word'], '', $this->getVar($key, $default, $hash));
	}

	/**
	 * Get cmd
	 *
	 * @param   string  $key      Request key
	 * @param   mixed   $default  Default value
	 * @param   string  $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @return  string  Request variable
	 */
	public function getCmd($key = null, $default = null, $hash = 'input')
	{
		$result = (string) preg_replace(static::$filters['cmd'], '', $this->getVar($key, $default, $hash));
		return ltrim($result, '.');
	}

	/**
	 * Fetches and returns a given variable as an array.
	 *
	 * @param   string  $key      Request key
	 * @param   mixed   $default  Default value
	 * @param   string  $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @return  array   Request variable
	 */
	public function getArray($key = null, $default = array(), $hash = 'input')
	{
		return (array) $this->getVar($key, $default, $hash);
	}

	/**
	 * Fetches and returns a given variable as a string.
	 *
	 * @param   string  $key      Request key
	 * @param   mixed   $default  Default value
	 * @param   string  $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD)
	 * @return  string  Request variable
	 */
	public function getString($name, $default = null, $hash = 'input')
	{
		return (string) $this->getVar($name, $default, $hash);
	}

	/**
	 * Return the Request instance.
	 *
	 * @return  object  \Hubzero\Http\Request
	 */
	public function instance()
	{
		return $this;
	}

	/**
	 * Get the request method.
	 *
	 * @return string
	 */
	public function method()
	{
		return $this->getMethod();
	}

	/**
	 * Get the root URL for the application.
	 *
	 * @return  string
	 */
	public function root()
	{
		$root = rtrim($this->getSchemeAndHttpHost() . $this->getBasePath(), '/');
		$root = explode('/', $root);
		if (in_array(end($root), array('administrator', 'api')))
		{
			array_pop($root);
		}

		return implode('/', $root) . '/';
	}

	/**
	 * Get the URL (no query string) for the request.
	 *
	 * @return  string
	 */
	public function url()
	{
		return rtrim(preg_replace('/\?.*/', '', $this->getUri()), '/');
	}

	/**
	 * Get the full URL for the request.
	 *
	 * @return  string
	 */
	public function fullUrl()
	{
		$query = $this->getQueryString();

		return $query ? $this->url() . '?' . $query : $this->url();
	}

	/**
	 * Get the current path info for the request.
	 *
	 * @return  string
	 */
	public function path()
	{
		$pattern = trim($this->getPathInfo(), '/');

		return $pattern == '' ? '/' : $pattern;
	}

	/**
	 * Get the current path info for the request.
	 *
	 * @return  string
	 */
	public function base($pathonly = false)
	{
		$path = trim($this->getBasePath(), '/');
		return ($pathonly ? '' : trim($this->root(), '/')) . '/' . $path . ($path ? '/' : '');
	}

	/**
	 * Get a segment from the URI (1 based index).
	 *
	 * @param   string  $index
	 * @param   mixed   $default
	 * @return  string
	 */
	public function segment($index, $default = null)
	{
		$segments = $this->segments();

		return isset($segments[$index - 1]) ? $segments[$index - 1] : $default;
	}

	/**
	 * Get all of the segments for the request path.
	 *
	 * @return array
	 */
	public function segments()
	{
		$segments = explode('/', $this->path());

		return array_values(array_filter($segments, function($v) { return $v != ''; }));
	}

	/**
	 * Determine if the request is the result of an AJAX call.
	 *
	 * @return  bool
	 */
	public function ajax()
	{
		return $this->isXmlHttpRequest();
	}

	/**
	 * Determine if the request is over HTTPS.
	 *
	 * @return  bool
	 */
	public function secure()
	{
		return $this->isSecure();
	}

	/**
	 * Get the IP address of the client.
	 *
	 * @return  string
	 */
	public function ip()
	{
		return $this->getClientIp();
	}

	/**
	 * Determine if the request contains a given input item.
	 *
	 * @param   mixed  $key  string|array
	 * @return  bool
	 */
	public function has($key)
	{
		if (count(func_get_args()) > 1)
		{
			foreach (func_get_args() as $value)
			{
				if ( ! $this->has($value)) return false;
			}

			return true;
		}

		if (is_bool($this->input($key)) || is_array($this->input($key)))
		{
			return true;
		}

		return trim((string) $this->input($key)) !== '';
	}

	/**
	 * Retrieve an input item from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function input($key = null, $default = null)
	{
		$input = $this->getInputSource()->all() + $this->query->all();

		return isset($input[$key]) ? $input[$key] : $default;
	}

	/**
	 * Retrieve a post item from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function request($key = null, $default = null)
	{
		return $this->retrieveItem('request', $key, $default);
	}

	/**
	 * Retrieve a query string item from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function query($key = null, $default = null)
	{
		return $this->retrieveItem('query', $key, $default);
	}

	/**
	 * Retrieve a cookie from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function cookie($key = null, $default = null)
	{
		return $this->retrieveItem('cookies', $key, $default);
	}

	/**
	 * Retrieve a header from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function header($key = null, $default = null)
	{
		return $this->retrieveItem('headers', $key, $default);
	}

	/**
	 * Retrieve a server variable from the request.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return string
	 */
	public function server($key = null, $default = null)
	{
		return $this->retrieveItem('server', $key, $default);
	}
}
