<?php
/**
 * Translation object
 *
 * @author     Marie-Pascale Girard <marie-pascale.girard@tonikgroupimage.com>
 * @package    framework
 * @filesource
 */
namespace framework;

/**
 *
 * This class is the basic implementation for cultural translations
 *
 */
class translations implements \ArrayAccess
{
	/**
	 * translated items in the current language
	 *
	 * @array
	 */
	protected $translated = array();

	/**
	 * options for the translations
	 *
	 * @array
	 */
	protected $options = array();

	/**
	 * options for the translations
	 *
	 * @string
	 */
	protected $currentLanguage = 'fr';

	/**
	 * Constructor
	 *
	 * @return null
	 */
	public function __construct($options=array()) {
		$this->translated = array();
		$this->options = $options;
	}

	/**
	 * setup the language strategy for this site
	 *
	 * @param string Language strategy for this site (cookie or url)
	 * @param string Language if managed through the url
	 * @return void
	 */
	public function setup($strategy,$lang='fr') {
		switch ($strategy) {
		case "cookie":
			// if the language was "changed" through the url
			if(isset($_GET['_lang'])) {
				$lang = $_GET['_lang'];
			// Use the value specified in the cookie if set
			} elseif(isset($_COOKIE['_lang'])) {
				$lang = $_COOKIE['_lang'];
			// Extract the default value from the browser preferences
			} elseif (isset($this->options['accepted-languages']) && is_array($this->options['accepted-languages']) && count($this->options['accepted-languages'])>1) {
				// Use the browser's headers to determine the language
				if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {
					// Split possible languages into array
					$languages = array();
					$x = explode(",",$_SERVER["HTTP_ACCEPT_LANGUAGE"]);
					foreach ($x as $val) {
						#check for q-value and create associative array. No q-value means 1 by rule
						if(preg_match("/(.*);q=([0-1]{0,1}\.\d{0,4})/i",$val,$matches)) {
							$languages[$matches[1]] = (float)$matches[2];
						} else {
							$languages[$val] = 1.0;
						}
					}

					// Determine default language (highest q-value)
					$qval = 0.0;
					foreach ($languages as $key => $value) {
						if ($value > $qval && in_array($key,$this->options['accepted-languages'])) {
							$qval = (float)$value;
							$deflang = $key;
						}
					}
				}
				$lang = isset($deflang)?$deflang:$lang;
			}

			// register and set the language cookie
			$this->setLanguage($lang);
			setcookie('_lang', $lang, time() + (3600 * 24 * 30));
			break;
		case "url":
			$this->setLanguage($lang);
			break;
		default:
			throw new \Exception("No valid strategy selected.");
		}
	}

	/**
	 * Set the current language
	 *
	 * @param string Language
	 * @return void
	 */
	public function setLanguage($language) {
		$this->currentLanguage = $language;
	}

	/**
	 * Get the current language
	 *
	 * @param string Language
	 * @return void
	 */
	public function getLanguage() {
		return $this->currentLanguage;
	}

	/**
	 * If trying to print this object, return the current language
	 *
	 * @param string Language
	 * @return void
	 */
	public function __toString() {
		return $this->getLanguage();
	}

	/**
	 * Return the value for a specific key
	 *
	 * @param string Language to load
	 * @return various
	 */
	public function load($language) {
		$folder = isset($this->options['file_dir'])?$this->options['file_dir']:'app/i18n';
		if (!file_exists($folder.DIRECTORY_SEPARATOR.$language.'.php')) {
			throw new \Exception("Language file not found (".$language.'.php'.")");
		}
		$ret = include_once($folder.DIRECTORY_SEPARATOR.$language.'.php');
		$this->translated[$language] = isset($this->options['file_var'])?$$varName:$ret;
	}

	/**
	 * Validate if a key exists
	 *
	 * @param string Key to verify
	 * @return boolean
	 */
	public function offsetExists($offset) {
		return isset($this->translated[$this->currentLanguage][$offset]);
	}

	/**
	 * Get the list of accepted languages
	 *
	 * @return array
	 */
	public function getAcceptedLanguages() {
		return isset($this->options['accepted-languages'])?$this->options['accepted-languages']:array($this->currentLanguage);
	}

	/**
	 * Unset a key
	 *
	 * @param string Key to verify
	 * @return void
	 */
	public function offsetUnset($offset) {
		unset($this->translated[$this->currentLanguage][$offset]);
	}

	/**
	 * Return the value for a specific key
	 *
	 * @param string Key to return
	 * @return various
	 */
	public function offsetGet($offset) {
		if (!isset($this->translated[$this->currentLanguage])) {
			$this->load($this->currentLanguage);
		}
		if (isset($this->translated[$this->currentLanguage][$offset])) {
			return $this->translated[$this->currentLanguage][$offset];
		}
		if (isset($this->options['missed_logfile'])) {
			$ln = (isset($this->options['missed_logFormat'])?str_replace("%",$offset,$this->options['missed_logFormat']):$offset)."\n";
			file_put_contents($this->options['missed_logfile'],$ln,FILE_APPEND);
		}
		if (isset($this->options['debug']) && $this->options['debug']) {
			return "*".$offset."*";
		}
		if (isset($this->options['error_message']) && $this->options['error_message']) {
			throw new \Exception (sprintf($this->options['error_message'],$offset));
		}
		return null;
	}

	/**
	 * Assign a value for a key
	 *
	 * @param string Key to set
	 * @param string Value to affect to the offset
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		if (!isset($this->translated[$this->currentLanguage])) {
			$this->load($this->currentLanguage);
		}
		if (is_null($offset)) {
			$this->translated[$this->currentLanguage][] = $value;
		} else {
			$this->translated[$this->currentLanguage][$offset] = $value;
		}
	}
}
