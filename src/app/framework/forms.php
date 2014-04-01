<?php
/**
 * Data records
 *
 * @author     Marie-Pascale Girard <marie-pascale.girard@tonikgroupimage.com>
 * @package    framework
 * @filesource
 */
namespace framework;

/**
 *
 * This class is the basic implementation for form validation
 *
 */
class forms
{
	/**
	 * fields descriptions
	 *
	 * @array
	 */
	protected $fields = array();

	/**
	 * fields values
	 *
	 * @array
	 */
	public $record = array();

	/**
	 * linked model name
	 *
	 * @string
	 */
	public $modelName = "";

	/**
	 * linked model
	 *
	 * @object
	 */
	public $linkedModel = null;

	/**
	 * file uploads descriptions
	 *
	 * @array
	 */
	protected $fileUploads = array();

	/**
	 * dateFormats
	 *
	 * @array
	 */
	protected $dateFormats = array(
		'fr' =>"/^20[0-9]{2}-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])$/",
		'en' =>"/^20[0-9]{2}-(0?[1-9]|1[012])-(0?[1-9]|[12][0-9]|3[01])$/",
		);

	/**
	 * Constructor
	 *
	 */
	public function __construct() {
	}

	/**
	 * Generate and store a CSRF token for validation purposes
	 *
	 * @param string formName (optional)
	 * @return array
	 */
	public function generateCSRF($formName="") {
		if (!$formName) {
			$formName = get_class($this);
		}
		return $_SESSION['csrf'][$formName] = md5(uniqid(rand(), true));
	}

	/**
	 * Validate a stored CSRF token
	 *
	 * @param string csrf token to validate
	 * @param string formName (optional)
	 * @return array
	 */
	public function validateCSRF($token, $formName="") {
		if (!$formName) {
			$formName = get_class($this);
		}
		return ($_SESSION['csrf'][$formName] == $token);
	}

	/**
	 * validate and returns a list of errors
	 *
	 * @param array record to validate
	 * @param array special options to consider (optional)
	 * @return array
	 */
	public function validate($record, $options=array()) {
		global $lang;
		$checked_fields = array();
		$record_fields = array_keys($record);
		$errors = array();

		foreach ($this->fields as $field=>$desc) {
			// Required field validation
			if (isset($desc['required']) && $desc['required'] && !trim($record[$field])) {
				$errors[$field] = isset($desc['required_message'])?$desc['required_message']:$field." required";
				break;
			}
			// Minimum length validation
			if (isset($desc['minlength']) && $desc['minlength'] && strlen(trim($record[$field]))<$desc['minlength']) {
				$errors[$field] = isset($desc['length_message'])?$desc['length_message']:$field." not long enough (".$desc['minlength'].")";
				break;
			}
			// Date format validation
			if (isset($desc['required']) && $desc['required'] && trim($record[$field]) && $desc['type']=="date" && !preg_match($this->dateFormats[$lang],$record[$field])) {
				$errors[$field] = isset($desc['required_message'])?$desc['required_message']:$field." required";
				break;
			}
			// When the form is linked to a data model, validate that there are no duplicate entry for this field
			if (isset($desc['no_duplicates']) && $desc['no_duplicates']) {
				if (!$this->modelName) {
					throw (new \Exception("No model linked to the form"));
				}
				if (!$this->linkedModel) {
					$this->linkedModel = new $this->modelName();
				}

				$opts = array('id_field'=>$field);
				// If we're checking for a duplicate in another field than the id and we already have a record
				if ($field!=$this->linkedModel->id_field && isset($record[$this->linkedModel->id_field])) {
					$opts['filters_ne'][$this->linkedModel->id_field] = $record[$this->linkedModel->id_field];
				}
				if ($this->linkedModel->record($record[$field],$opts)) {
					$errors[$field] = isset($validation['duplicate_message'])?$validation['duplicate_message']:"field ".$field." already in our database";;
					break;
				}
			}
			// Custom validation routines
			if (($record[$field] || $desc['required']) && isset($desc['validations']) && is_array($desc['validations'])) {
				foreach ($desc['validations'] as $validation) {
					if (isset($validation['fnct']) && is_callable($validation['fnct']) && !$validation['fnct']($record[$field])) {
						$errors[$field] = isset($validation['message'])?$validation['message']:"field ".$field." does not validate";
					} elseif (isset($validation['fnct']) && !call_user_func($validation['fnct'],$record[$field])) {
						$errors[$field] = isset($validation['message'])?$validation['message']:"field ".$field." does not validate";
					} elseif (isset($validation['method']) && !call_user_func(array($this,$validation['method']),$record[$field])) {
						$errors[$field] = isset($validation['message'])?$validation['message']:"field ".$field." does not validate";
					}
				}
			}
			$this->record[$field] = $record[$field];
		}
		return $errors;
	}

	/**
	 * validate and returns a list of errors
	 *
	 * @return array
	 */
	public function doUploads($record, $options=array()) {
		$errors = array();

		foreach($this->fileUploads as $field=>$desc) {
			if (isset($desc['required']) && $desc['required'] && !$record[$field]['error']) {
				$errors[$field] = isset($desc['required_message'])?$desc['required_message']:$field." required";
				break;
			} elseif ((!isset($desc['required']) || !$desc['required']) && $record[$field]['error']) {
				break;
			}
			if (isset($desc['accepted_types']) && !in_array(strtolower(pathinfo($record[$field]['name'], PATHINFO_EXTENSION)), $desc['accepted_types'])) {
				$errors[$field] = isset($desc['type_message'])?$desc['type_message']:$field." is not an acceptable file";
				break;
			}

			// Move the file to the destination folder
			$nouvNom = time()."-".rewrite_string(pathinfo($record[$field]['name'],PATHINFO_FILENAME)).".".pathinfo($record[$field]['name'], PATHINFO_EXTENSION);
			if (!file_exists(ROOT . $desc['destination'])) {
				if (!mkdir(ROOT . $desc['destination'])) {
					$errors[$field] = isset($desc['error_message'])?$desc['error_message']:$field." upload failed";
					break;
				}
			}
			if (!move_uploaded_file($record[$field]['tmp_name'], ROOT . $desc['destination']."/".$nouvNom)) {
				$errors[$field] = isset($desc['error_message'])?$desc['error_message']:$field." upload failed";
				break;
			}
			$this->record[$field] = $desc['destination']."/".$nouvNom;
		}

		return $errors;
	}

	/**
	 * This method allows the saving of the form's content to the database if it is linked to a data model
	 *
	 * @return int (last inserted id or existing record id)
	 */
	public function save() {
		if (!$this->modelName) {
			throw (new \Exception("No model linked to the form"));
		}
		if (!$this->linkedModel) {
			$this->linkedModel = new $this->modelName();
		}
		return $this->linkedModel->save($this->record);
	}
}