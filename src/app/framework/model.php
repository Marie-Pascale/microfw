<?php
/**
 * Very basic data model
 *
 * @author     Marie-Pascale Girard <marie.p.girard@gmail.com>
 * @package    framework
 * @filesource
 */
namespace framework;

/**
 *
 * This class is the basis class implementing table query models
 *
 */
class model
{
	/**
	 * Table name
	 *
	 * @string
	 */
	public $_tbl_name = '';

	/**
	 * Last number of record found
	 *
	 * @int
	 */
	private $_last_found = -1;

	/**
	 * ID field name
	 *
	 * @string
	 */
	public $_id_field = "id";

	/**
	 * Storage pointer
	 *
	 * @PDO object
	 */
	private $_storage = null;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		if (method_exists($this, "initialize")) {
			$this->initialize();
		}
		// Extract the table name from the classname if it is not already set
		if (!$this->_tbl_name) {
			$myClassname = get_class($this);
			// Extract the real classname if it is using namespaces
			$regs = array();
			if (preg_match("/\\\\([^\\\\]+)$/",$myClassname,$regs)) {
				$myClassname = $regs[1];
			}
			$this->_tbl_name = $myClassname;
		}
	}

	/**
	 * Returns the storage connection for this model
	 *
	 * @return PDO object
	 */
	public function getStorage($options=array()) {
		if ($this->_storage) {
			return $this->_storage;
		}

		// Instanciate the dependency injector to gt the storage
		$di = \framework\injector::getInstance();

		// Try to instanciate the given storage
		if (isset($options['storage']) && $options['storage']) {
			$this->_storage = $di->getRessource($options['storage']);
		}
		$this->_storage = $di->getRessource("db");

		if ($this->_storage->getAttribute(\PDO::ATTR_DRIVER_NAME) != 'mysql') {
			throw new \Exception("Sadly only the mysql driver is supported by this implementation.");
		}
		return $this->_storage;
	}

	/**
	 * Returns a list of records
	 *
	 * @return array
	 */
	public function liste($options=array()) {
		$qry = $this->select($options);

		// Query is built, now we execute and return the result.
		$res = $this->query($qry);

		$res->setFetchMode(\PDO::FETCH_ASSOC);
		$records = $res->fetchAll();

		$this->_last_found = $res->rowCount();
		return $records;
	}

	/**
	 * Returns the last number of found records
	 *
	 * @return array
	 */
	public function lastCount() {
		return $this->_last_found;
	}

	/**
	 * Returns a list of records
	 *
	 * @return array
	 */
	public function record($id, $options=array()) {
		$id_field = $this->_id_field;
		if (isset($options['id_field'])) {
			$id_field = $options['id_field'];
		}
		$options['filters'][$id_field] = $id;
		$qry = $this->select($options);

		// Query is built, now we execute and return the result.
		$res = $this->query($qry);

		return $res->fetch(\PDO::FETCH_ASSOC);
	}

	/**
	 * Executes a save (insert record on duplicate key update)
	 *
	 * @return last_insert_id
	 */
	public function save($record, $options=array()) {
		$db = $this->getStorage($options);

		$id_field = $this->_id_field;
		if (isset($options['id_field'])) {
			$id_field = $options['id_field'];
		}
		$qry = "INSERT INTO ".(isset($options['table'])?$options['table']:$this->_tbl_name)." (";
		$values = "";
		$updates = "";
		$first = true;
		foreach ($record as $field=>$value) {
			$qry .= ($first?"":", ")."`".$field."`";
			$values .= ($first?"":", ").($field!=$id_field || $value?$db->quote($value):'null');
			$updates .= ($first?"":", ")."`".$field."`=".($field!=$id_field || $value?$db->quote($value):'null');
			$first = false;
		}
		$qry .= ") VALUES (".$values.") ON DUPLICATE KEY UPDATE ".$updates;

		// Query is built, now we execute and return the result.
		$this->query($qry);

		$lid = $db->lastInsertId();

		if ($lid) {
			return $lid;
		}
		return false;
	}

	/**
	 * Delete a record
	 *
	 * @return void
	 */
	public function delete($options=array()) {
		$db = $this->getStorage($options);

		$qry = "DELETE FROM ".(isset($options['table'])?$options['table']:$this->_tbl_name);

		if (isset($options['filters'])) {
			$qry .= " WHERE";
			if (is_array($options['filters']) && count($options['filters'])) {
				$first = true;
				foreach ($options['filters'] as $field=>$val) {
					$qry .= (!$first?" AND ":" ")."`".$field."`"." LIKE '".$db->quote($val)."'";
					$first = false;
				}
			} else {
				$qry .= $options['filters'];
			}
		}

		// Query is built, now we execute and return the result.
		$this->query($qry);
	}

	/**
	 * Builds a basic select query
	 *
	 * @return array
	 */
	public function select($options=array()) {
		$db = $this->getStorage($options);

		$qry = "SELECT ";
		$qry .= (isset($options['no_calc_rows']) && $options['no_calc_rows'])?'':"SQL_CALC_FOUND_ROWS ";
		$qry .= (isset($options['distinct']) && $options['distinct'])?"DISTINCT ":'';
		if (isset($options['fields'])) {
			if (is_array($options['fields']) && count($options['fields'])) {
				$first = true;
				foreach ($options['fields'] as $field=>$as) {
					if (is_array($as) && $as['is_raw']) {
						$qry .= ($first?"":", ").(!is_numeric($field)?$field:$as['field'].($as['as']?" AS `".$as['as']."`":''));
					} elseif (is_array($as)) {
						$qry .= ($first?"":", ").(!is_numeric($field)?"`".$field."`":"`".$as['field']."` AS `".$as['field']."`");
					} else {
						$qry .= ($first?"":", ").(!is_numeric($field)?"`".$field."`":"`".$field."` AS `".$as."`");
					}
					$first = false;
				}
			} else {
				$qry .= $options['fields'];
			}
		} else {
			$qry .= "*";
		}
		$qry .= " FROM ".(isset($options['table'])?$options['table']:$this->_tbl_name);
		if (isset($options['join'])) {
			$qry .= " ".$options['join'];
		}
		if (isset($options['filters'])) {
			$qry .= " WHERE";
			if (is_array($options['filters']) && count($options['filters'])) {
				$first = true;
				foreach ($options['filters'] as $field=>$val) {
					if (is_numeric($field)) {
						$qry .= (!$first?" AND ":" ").$val;
					} else {
						$qry .= (!$first?" AND ":" ")."`".$field."`"." LIKE ".$db->quote($val);
					}
					$first = false;
				}
			} else {
				$qry .= $options['filters'];
			}
		}
		if (is_array($options['filters_ne']) && count($options['filters_ne'])) {
			if (!isset($options['filters'])) {
				$qry .= " WHERE";
			} else {
				$qry .= " AND";
			}
			$first = true;
			foreach ($options['filters_ne'] as $field=>$val) {
				if (is_numeric($field)) {
					$qry .= (!$first?" AND ":" ").$val;
				} else {
					$qry .= (!$first?" AND ":" ")."`".$field."`"." NOT LIKE ".$db->quote($val);
				}
				$first = false;
			}
		}
		if (isset($options['group_by'])) {
			$qry .= " GROUP BY ".$options['group_by'];
		}
		if (isset($options['sort'])) {
			$qry .= " ORDER BY";
			if (is_array($options['sort']) && count($options['sort'])) {
				$first = true;
				foreach($options['sort'] as $field=>$direction) {
					if (is_array($direction)) {
						if (!$direction['is_raw']) {
							$field = "`".$field."`";
						}
					} elseif (is_numeric($field)) {
						$field = "`".$direction."`";
						$direction = "asc";
					}

					if (!is_string($direction) || (is_string($direction) && !preg_match("/^(asc|desc)$/i",$direction))) {
						$direction = "asc";
					}
					$qry .= (!$first?", ":" ").$field." ".$direction;
					$first = false;
				}
			} else {
				$qry .= " `".$options['sort']."` asc";
			}
		}
		if (isset($options['page'])) {
			$page = 1;
			$first = 0;
			$per_page = 10;
			if (is_array($options['page']) && isset($options['page']['per_page'])) {
				$page = isset($options['page']['show'])?$options['page']['show']*1:1;
				$per_page = $options['page']['per_page']*1;
			} elseif (!is_array($options['page'])) {
				$page = $options['page']*1;
			}
			if ($page < 1) $page = 1;

			$qry .= " LIMIT ".(($page-1)*$per_page).", $per_page";
		}

		return $qry;
	}

	/**
	 * Magic method to catch all the find calls and all
	 *
	 * @param string Function name
	 * @param array Arguments sent to the call
	 * @return void
	 */
	public function __call($name, $arguments)
	{
		$regs = array();
		// Take care of all the findBy calls
		if (preg_match("/^find(One)?By([A-Z][a-zA-Z]*)$/",$name, $regs)) {
			$one = ($regs[1]!="");
			$fieldStr = $regs[2];
			$fields = array();
			$regs = array();
			while (preg_match("/^([A-Z][a-zA-Z]*)And([A-Z][a-zA-Z]*)*$/",$fieldStr, $regs)) {
				$fieldStr = $regs[1];
				$fields[] = $regs[2];
			}
			$fields[] = $fieldStr;
			$fields = array_reverse(array_map(function ($string) {return strtolower(preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $string));}, $fields));

			// Validate the number of arguments
			if (count($arguments)!=count($fields)) {
				throw new \Exception("Invalid parameter count (needed ".count($fields).")");
			}

			// Build the list options
			if ($one) {
				$qry = $this->select(array("filters"=>array_combine($fields, $arguments)));

				// Query is built, now we execute and return the result.
				$res = $this->query($qry);

				return $res->fetch(\PDO::FETCH_ASSOC);
			} else {
				return $this->liste(array("filters"=>array_combine($fields, $arguments)));
			}
		}

		// If we get to here, it's because nothing else above worked
		throw new \Exception("Invalid method call ".$name);
	}

	/**
	 * Runs a query and do the error checking
	 *
	 * @param string Query to run
	 * @return query result
	 */
	public function query($qry) {
		$db = $this->getStorage($options);

		$res = $db->query($qry);
		if ($db->errorCode()!='00000') {
			throw new \Exception(implode(" :: ",$db->errorInfo())."\n".$qry);
		}

		return $res;
	}


	/**
	 * Create the table if it does not exist in the database
	 *
	 * @return void
	 */
	public function createTable() {
	}

}