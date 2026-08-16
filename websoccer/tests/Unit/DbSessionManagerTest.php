<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for DbSessionManager with a mocked DbConnection and WebSoccer.
 */
final class DbSessionManagerTest extends TestCaseBase {
	private \DbConnection $db;
	private \WebSoccer $ws;

	private function buildManager(?MockDbResult $selectResult = null, array $config = []): \DbSessionManager {
		$this->db = $this->createMock(\DbConnection::class);
		$this->db->method('querySelect')->willReturn($selectResult ?? new MockDbResult([]));
		$this->ws = $this->mockWebsoccer(array_merge([
			'db_prefix' => 'pref',
			'session_lifetime' => '3600',
		], $config));
		return new \DbSessionManager($this->db, $this->ws);
	}

	public function testOpenReturnsTrue(): void {
		$m = $this->buildManager();
		$this->assertTrue($m->open('/tmp', 'sess'));
	}

	public function testCloseReturnsTrue(): void {
		$m = $this->buildManager();
		$this->assertTrue($m->close());
	}

	public function testDestroyCallsQueryDelete(): void {
		$m = $this->buildManager();
		$this->db->expects($this->once())
			->method('queryDelete')
			->with('pref_session', 'session_id = \'%s\'', 'sid123');
		$this->assertTrue($m->destroy('sid123'));
	}

	public function testReadReturnsEmptyStringWhenNoSession(): void {
		$m = $this->buildManager();
		$this->assertSame('', $m->read('sid'));
	}

	public function testReadReturnsSessionDataWhenValid(): void {
		$m = $this->buildManager(new MockDbResult([
			['expires' => time() + 3600, 'session_data' => 'mydata'],
		]));
		$this->assertSame('mydata', $m->read('sid'));
	}

	public function testReadReturnsEmptyStringWhenDataIsNull(): void {
		$m = $this->buildManager(new MockDbResult([
			['expires' => time() + 3600, 'session_data' => null],
		]));
		$this->assertSame('', $m->read('sid'));
	}

	public function testReadDestroysExpiredSession(): void {
		$m = $this->buildManager(new MockDbResult([
			['expires' => time() - 3600, 'session_data' => 'old'],
		]));
		$this->db->expects($this->once())
			->method('queryDelete')
			->with('pref_session', 'session_id = \'%s\'', 'sid');
		$this->assertSame('', $m->read('sid'));
	}

	public function testValidateSidReturnsFalseWhenNoSession(): void {
		$m = $this->buildManager();
		$this->assertFalse($m->validate_sid('sid'));
	}

	public function testValidateSidReturnsTrueWhenValid(): void {
		$m = $this->buildManager(new MockDbResult([
			['expires' => time() + 3600],
		]));
		$this->assertTrue($m->validate_sid('sid'));
	}

	public function testValidateSidReturnsFalseAndDestroysWhenExpired(): void {
		$m = $this->buildManager(new MockDbResult([
			['expires' => time() - 3600],
		]));
		$this->db->expects($this->once())->method('queryDelete');
		$this->assertFalse($m->validate_sid('sid'));
	}

	public function testWriteInsertsNewSession(): void {
		// querySelect returns empty -> validate_sid false -> insert
		$m = $this->buildManager();
		$captured = null;
		$this->db->expects($this->once())
			->method('queryInsert')
			->willReturnCallback(function ($columns, $table) use (&$captured) {
				$captured = [$columns, $table];
			});
		$this->assertTrue($m->write('sid', 'somedata'));
		$this->assertSame('pref_session', $captured[1]);
		$this->assertSame('sid', $captured[0]['session_id']);
		$this->assertSame('somedata', $captured[0]['session_data']);
		$this->assertArrayHasKey('expires', $captured[0]);
	}

	public function testWriteDoesNotInsertWhenDataEmptyAndNoExistingSession(): void {
		$m = $this->buildManager();
		$this->db->expects($this->never())->method('queryInsert');
		$this->db->expects($this->never())->method('queryUpdate');
		$this->assertTrue($m->write('sid', ''));
	}

	public function testWriteUpdatesExistingSession(): void {
		$m = $this->buildManager(new MockDbResult([
			['expires' => time() + 3600],
		]));
		$captured = null;
		$this->db->expects($this->never())->method('queryInsert');
		$this->db->expects($this->once())
			->method('queryUpdate')
			->willReturnCallback(function ($columns, $table, $where, $params) use (&$captured) {
				$captured = [$columns, $table, $where, $params];
			});
		$this->assertTrue($m->write('sid', 'newdata'));
		$this->assertSame('pref_session', $captured[1]);
		$this->assertSame('newdata', $captured[0]['session_data']);
		$this->assertSame('sid', $captured[3]);
	}

	public function testGcDeletesExpiredSessionsAndReturnsTrue(): void {
		$m = $this->buildManager();
		$this->db->expects($this->once())
			->method('queryDelete')
			->with('pref_session', 'expires < %d', $this->ws->getNowAsTimestamp());
		$this->assertTrue($m->gc(1440));
	}
}
