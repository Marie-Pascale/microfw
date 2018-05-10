<?php
/**
 * Very basic cache system
 *
 * @author     Marie-Pascale Girard <marie.p.girard@gmail.com>
 * @package    framework
 * @filesource
 */
namespace framework;

/**
 *
 * This class implements a file-system level cache system
 *
 */
class cache
{
	/**
	 * Cache base directory
	 *
	 * @string
	 */
	public $_basepath = 'app/cache/';

	/**
	 * Default number of seconds for the lifetime of cached objects
	 *
	 * @int
	 */
	public $_lifetime = 3600;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct($basepath="app/cache/", $defaultLifetime=3600) {
		$this->_basepath = $basepath;
		$this->_lifetime = $defaultLifetime;
		if (method_exists($this, "initialize")) {
			$this->initialize();
		}
	}

	/**
	 * Returns the a cached version of the requested key.
	 * Key names containing / will result in creation of sub-directories in the cache structure
	 *
	 * @param string Key name for the fetched item
	 * @param callback Function or method to call if the cache content is invalidated or absent
	 * @param array Caching options
	 * @return Cached content or the result of the newly cached callback or false if no callback
	 */
	public function load($key, $callback=null, $options=array()) {
		$basepath = isset($options['basepath'])?$options['basepath']:$this->_basepath;
		$cache_file = $basepath.DIRECTORY_SEPARATOR.$key.".cache.php";
		if (file_exists($cache_file)) {
			$cacheContentCall = function () use ($cache_file) {return include $cache_file;};
			$cacheContent = $cacheContentCall();
			if ((isset($options['lifetime'])?$options['lifetime']:$this->_lifetime) == "eternal") {
				return $cacheContent['content'];
			}
			$invalidatedTime = time()-(isset($options['lifetime'])?$options['lifetime']:$this->_lifetime);

			// If the cache's content is too old, invalidate it
			if ($cacheContent['timestamp']<$invalidatedTime) {
				$this->invalidate($key, $options);
			} else {
				return $cacheContent['content'];
			}
		}
		// No cache content was found or it was invalidated, regenerate it from callback or return null
		if (is_callable($callback)) {
			return $this->save($key, $callback, $options);
		}
		return null;
	}

	/**
	 * Invalidates a cached element (in this case, delete it's file)
	 * Key names containing / will result in creation of sub-directories in the cache structure
	 *
	 * @param string Key name for the fetched item
	 * @param array Caching options
	 * @return void
	 */
	public function invalidate($key, $options=array()) {
		$basepath = isset($options['basepath'])?$options['basepath']:$this->_basepath;
		$cache_dir = $basepath.DIRECTORY_SEPARATOR.$key;
		$cache_file = $basepath.DIRECTORY_SEPARATOR.$key.".cache.php";

		// If we're trying to invalidate a whole directory, traverse it recursively
		if (is_dir($cache_dir)) {
			foreach(scandir($cache_dir) as $itm) {
				if (preg_match("/\.cache\.php$/",$itm)) {
					unlink($cache_dir.DIRECTORY_SEPARATOR.$itm);
				} elseif ($itm!='.' && $itm!='..' && is_dir($cache_dir.DIRECTORY_SEPARATOR.$itm)) {
					$this->invalidate($key.DIRECTORY_SEPARATOR.$itm, $options);
				}
			}
		}
		if (file_exists($cache_file)) {
			unlink($cache_file);
		}
	}

	/**
	 * Save contents into a cache file
	 * Key names containing / will result in creation of sub-directories in the cache structure
	 *
	 * @param string Key name for the fetched item
	 * @param content Content or callback function that should return the content to be cached
	 * @param array Caching options
	 * @return void
	 */
	public function save($key, $content, $options=array()) {
		$basepath = isset($options['basepath'])?$options['basepath']:$this->_basepath;
		$cache_file = $basepath.DIRECTORY_SEPARATOR.$key.".cache.php";
		// If the cache file already exists and is a directory throw an error
		if (is_dir($cache_file)) {
			throw new \Exception ("Cound not create cache file because it is a directory: ".$cache_file);
		}

		// Create parent directories if they don't exist
		$regs = array();
		if (preg_match("#^(.*)/([^/]+)$#",$key,$regs)) {
			if (!file_exists($basepath.DIRECTORY_SEPARATOR.$regs[1])) {
				if (!mkdir($basepath.DIRECTORY_SEPARATOR.$regs[1],0777,true)) {
					throw new \Exception ("Cound not create cache directory: ".$cache_file);
				}
			}
		}

		// Delete cache file if already there
		if (file_exists($cache_file)) {
			unlink($cache_file);
		}

		// Build the content
		$cacheContent = array("timestamp"=>time());
		if (is_callable($content)) {
			$cacheContent['content'] = $content();
		} else {
			$cacheContent['content'] = $content;
		}

		// Save the contents
		file_put_contents($cache_file,"<?php\n/* THIS IS A CACHE FILE MODIFY AT YOUR OWN PERILS */\nreturn ".var_export($cacheContent,true).";\n");

		return $cacheContent['content'];
	}
}
