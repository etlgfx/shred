<?php

/** @class AbstractDataObject
 *
 * Provide a general interface for representing data objects
 */
abstract class AbstractDataObject {

	protected $id;
	protected $data;

	const OBJ_STATUS_ACTIVE = 1;

	/**
	 * Constructor
	 */
	public function __construct() { }

	/*
	 * consider adding standard methods for approve publish etc., CRUD
	 */

	/**
	 * Convert the internal data to arrays
	 *
	 * @param $res optional array, if it is passed in it will be used instead
	 *        of the internal data array
	 *
	 * @returns array
	 */
	public function toArray(array $res = null) {
		if (!$res) {
			if (!$this->data)
				return array();

			$res = $this->data;
		}

		foreach ($res as $k => $v) {
			if ($v instanceof AbstractDataObject)
				$res[$k] = $v->toArray();
			else if (strpos($k, '_ts') && is_string($v)) {
				if ($v == '0000-00-00 00:00:00') {
					$res[$k] = null;
				}
				else {
					$ts = strtotime($v);

					$res[$k] = array(
						'full_time' => date('H:i:s', $ts),
						'short_time' => date('g:ia', $ts),
						'rfc822' => date('D, d M Y H:i:s T', $ts),
						'long_date' => date('F jS, Y', $ts),
						'short_date' => date('M jS, `y', $ts),
						'tiny_date' => date('M jS', $ts),
						'timestamp' => $ts
					);
				}
			}
		}

		$res['id'] = $this->id;

		return $res;
	}

	/**
	 * @returns int pk id of row or null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @see deleteImpl()
	 */
	public function delete() {
		if ($this->id && $this->data) {
			$this->deleteImpl();
		}
	}

	abstract protected function deleteImpl();
}

?>
