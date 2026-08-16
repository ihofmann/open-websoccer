<?php
namespace OpenWebSoccer\Tests;

/**
 * Lightweight stand-in for a mysqli_result.
 *
 * It only implements the subset of the mysqli_result API that the
 * OpenWebSoccer code base relies on (fetch_array(), free(), num_rows),
 * so service/model/action classes can be unit tested without a real
 * database connection.
 */
final class MockDbResult {
	private array $rows;
	private int $cursor = 0;

	/**
	 * @param array $rows list of associative arrays (the "rows").
	 */
	public function __construct(array $rows = []) {
		$this->rows = array_values($rows);
	}

	/**
	 * @return array|false next row as an associative array or false at the end.
	 */
	public function fetch_array(): array|false {
		if ($this->cursor >= count($this->rows)) {
			return false;
		}
		return $this->rows[$this->cursor++];
	}

	public function free(): void {
		$this->rows = [];
		$this->cursor = 0;
	}

	/** Alias for free(), mirroring mysqli_result::free_result(). */
	public function free_result(): void {
		$this->free();
	}

	public function fetch_assoc(): array|false {
		return $this->fetch_array();
	}

	public function fetch_row(): array|false {
		$row = $this->fetch_array();
		return $row === false ? false : array_values($row);
	}

	public function __get(string $name) {
		if ($name === 'num_rows') {
			return count($this->rows);
		}
		return null;
	}
}
