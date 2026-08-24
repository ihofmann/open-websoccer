<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for DbConnection singleton and SQL-building logic.
 *
 * A fake mysqli connection stub provides real_escape_string (identity) so
 * that the private SQL builders can be exercised without a real database.
 * executeQuery() is the only mocked method, so the real query* methods run
 * their actual SQL-building code, which is captured for assertions.
 */
final class DbConnectionTest extends TestCaseBase {
	private ?string $capturedSql = null;

	private function makeFakeConnection(int $insertId = 0): object {
		return new class($insertId) {
			public $insert_id;
			public function __construct(int $insertId) {
				$this->insert_id = $insertId;
			}
			public function real_escape_string(string $s): string {
				return $s;
			}
		};
	}

	private function makeDb(object $fakeConn, ?\Closure $executeHandler = null): \DbConnection {
		$db = $this->getMockBuilder(\DbConnection::class)
			->onlyMethods(['executeQuery'])
			->disableOriginalConstructor()
			->getMock();
		$db->connection = $fakeConn;
		$db->method('executeQuery')->willReturnCallback(function ($q) use ($executeHandler) {
			$this->capturedSql = $q;
			if ($executeHandler !== null) {
				return $executeHandler($q);
			}
			return new MockDbResult([]);
		});
		return $db;
	}

	public function testGetInstanceReturnsSingleton(): void {
		\DbConnection::setInstanceForTesting(null);
		$a = \DbConnection::getInstance();
		$b = \DbConnection::getInstance();
		$this->assertSame($a, $b);
		\DbConnection::setInstanceForTesting(null);
	}

	public function testSetInstanceForTestingReplacesInstance(): void {
		$mock = $this->createMock(\DbConnection::class);
		\DbConnection::setInstanceForTesting($mock);
		$this->assertSame($mock, \DbConnection::getInstance());
	}

	public function testSetInstanceForTestingNullClearsInstance(): void {
		\DbConnection::setInstanceForTesting(null);
		$this->assertNotNull(\DbConnection::getInstance());
		\DbConnection::setInstanceForTesting(null);
	}

	public function testQuerySelectBuildsSimpleSelectSql(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->querySelect('id', 'mytable', 'id = %d', 5, 10);
		$this->assertSame('SELECT id FROM mytable WHERE id = 5 LIMIT 10', $this->capturedSql);
	}

	public function testQuerySelectBuildsAliasedColumnsSql(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->querySelect(['id' => 'i', 'name' => 'n'], 'mytable', '1=1', '');
		$this->assertSame('SELECT id AS i, name AS n FROM mytable WHERE 1=1', $this->capturedSql);
	}

	public function testQuerySelectWithoutLimit(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->querySelect('*', 'mytable', 'status = \'%s\'', 'active');
		$this->assertSame('SELECT * FROM mytable WHERE status = \'active\'', $this->capturedSql);
	}

	public function testQuerySelectTrimsParameters(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->querySelect('id', 'mytable', 'name = \'%s\'', '  bob  ');
		$this->assertSame('SELECT id FROM mytable WHERE name = \'bob\'', $this->capturedSql);
	}

	public function testQuerySelectHandlesNullParameters(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->querySelect('id', 'mytable', 'name = \'%s\'', null);
		$this->assertSame('SELECT id FROM mytable WHERE name = \'\'', $this->capturedSql);
	}

	public function testQuerySelectWithMultipleParameters(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->querySelect('id', 'mytable', 'a = %d AND b = \'%s\'', [5, 'x']);
		$this->assertSame('SELECT id FROM mytable WHERE a = 5 AND b = \'x\'', $this->capturedSql);
	}

	public function testQueryUpdateBuildsSql(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->queryUpdate(['col' => 'val', 'status' => 'active'], 'mytable', 'id = %d', 5);
		$this->assertSame('UPDATE mytable SET col=\'val\', status=\'active\' WHERE id = 5', $this->capturedSql);
	}

	public function testQueryUpdateUsesDefaultForEmptyValue(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->queryUpdate(['col' => ''], 'mytable', 'id = %d', 5);
		$this->assertSame('UPDATE mytable SET col=DEFAULT WHERE id = 5', $this->capturedSql);
	}

	public function testQueryUpdateUsesDefaultForNullValue(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->queryUpdate(['col' => null], 'mytable', 'id = %d', 5);
		$this->assertSame('UPDATE mytable SET col=DEFAULT WHERE id = 5', $this->capturedSql);
	}

	public function testQueryInsertBuildsSql(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->queryInsert(['col' => 'val'], 'mytable');
		$this->assertSame('INSERT mytable SET col=\'val\'', $this->capturedSql);
	}

	public function testQueryInsertUsesDefaultForEmptyValue(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->queryInsert(['col' => ''], 'mytable');
		$this->assertSame('INSERT mytable SET col=DEFAULT', $this->capturedSql);
	}

	public function testQueryDeleteBuildsSql(): void {
		$db = $this->makeDb($this->makeFakeConnection());
		$db->queryDelete('mytable', 'id = %d', 5);
		$this->assertSame('DELETE FROM mytable WHERE id = 5', $this->capturedSql);
	}

	public function testGetLastInsertedIdReturnsConnectionInsertId(): void {
		$db = $this->makeDb($this->makeFakeConnection(42));
		$this->assertSame(42, $db->getLastInsertedId());
	}

	public function testQueryCachedSelectReturnsRowsAndCaches(): void {
		$calls = 0;
		$rows = [['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']];
		$handler = function ($q) use (&$calls, $rows) {
			$calls++;
			return new MockDbResult($rows);
		};
		$db = $this->makeDb($this->makeFakeConnection(), $handler);
		$first = $db->queryCachedSelect('id, name', 'mytable', '1=1', '');
		$this->assertSame($rows, $first);
		$second = $db->queryCachedSelect('id, name', 'mytable', '1=1', '');
		$this->assertSame($rows, $second);
		$this->assertSame(1, $calls, 'executeQuery should be called only once when caching');
	}

	public function testQueryUpdateClearsCache(): void {
		$calls = 0;
		$rows = [['id' => 1]];
		$handler = function ($q) use (&$calls, $rows) {
			$calls++;
			return new MockDbResult($rows);
		};
		$db = $this->makeDb($this->makeFakeConnection(), $handler);
		$db->queryCachedSelect('id', 'mytable', '1=1', '');
		$this->assertSame(1, $calls);
		$db->queryUpdate(['col' => 'val'], 'mytable', 'id = %d', 1);
		$db->queryCachedSelect('id', 'mytable', '1=1', '');
		// 1 (first select) + 1 (update) + 1 (select re-run because cache cleared) = 3
		$this->assertSame(3, $calls, 'cache should be cleared after update so select re-queries');
	}

	public function testQueryDeleteClearsCache(): void {
		$calls = 0;
		$handler = function ($q) use (&$calls) {
			$calls++;
			return new MockDbResult([['id' => 1]]);
		};
		$db = $this->makeDb($this->makeFakeConnection(), $handler);
		$db->queryCachedSelect('id', 'mytable', '1=1', '');
		$this->assertSame(1, $calls);
		$db->queryDelete('mytable', 'id = %d', 1);
		$db->queryCachedSelect('id', 'mytable', '1=1', '');
		// 1 (first select) + 1 (delete) + 1 (select re-run because cache cleared) = 3
		$this->assertSame(3, $calls, 'cache should be cleared after delete so select re-queries');
	}
}
