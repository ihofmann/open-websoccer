<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for User.
 */
final class UserTest extends TestCaseBase {
	/**
	 * Creates a DbConnection mock whose querySelect returns the given rows.
	 * (Built from scratch, not via mockDb(), to avoid the stub-shadowing issue.)
	 */
	private function mockDbWithRows(array $rows = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		return $db;
	}

	/**
	 * Creates a DbConnection mock with a callback for querySelect.
	 */
	private function mockDbWithCallback(callable $callback): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback($callback);
		return $db;
	}

	public function testConstructorSetsPremiumBalanceToZero(): void {
		$user = new User();
		$this->assertSame(0, $user->premiumBalance);
	}

	public function testConstructorSetsIsAdminToNull(): void {
		$user = new User();
		$ref = new \ReflectionProperty(User::class, '_isAdmin');
		$this->assertNull($ref->getValue($user));
	}

	public function testGetRoleReturnsGuestWhenIdIsNull(): void {
		$user = new User();
		$this->assertSame(ROLE_GUEST, $user->getRole());
	}

	public function testGetRoleReturnsGuestWhenIdIsZero(): void {
		$user = new User();
		$user->id = 0;
		// 0 == null in PHP loose comparison
		$this->assertSame(ROLE_GUEST, $user->getRole());
	}

	public function testGetRoleReturnsUserWhenIdIsSet(): void {
		$user = new User();
		$user->id = 42;
		$this->assertSame(ROLE_USER, $user->getRole());
	}

	public function testSetClubIdStoresValueInSessionAndProperty(): void {
		$user = new User();
		$user->setClubId(7);
		$this->assertSame(7, $_SESSION['clubid']);
		// getClubId with no arguments should return the stored value.
		$this->assertSame(7, $user->getClubId());
	}

	public function testGetClubIdReturnsNullForGuestUser(): void {
		$user = new User();
		$this->assertNull($user->getClubId());
	}

	public function testGetClubIdReturnsNullForGuestEvenWithSessionClubId(): void {
		// Guest (id == null) should not read from session.
		$_SESSION['clubid'] = 99;
		$user = new User();
		$this->assertNull($user->getClubId());
	}

	public function testGetClubIdReadsFromSessionForLoggedInUser(): void {
		$_SESSION['clubid'] = 5;
		$user = new User();
		$user->id = 1;
		$this->assertSame(5, $user->getClubId());
	}

	public function testGetClubIdQueriesDatabaseWhenNotInSession(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->mockDbWithRows([['id' => 3]]);
		$user = new User();
		$user->id = 1;
		$this->assertSame(3, $user->getClubId($ws, $db));
		// Should also store in session.
		$this->assertSame(3, $_SESSION['clubid']);
	}

	public function testGetClubIdReturnsNullWhenDatabaseHasNoClub(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->mockDbWithRows([]);
		$user = new User();
		$user->id = 1;
		$this->assertNull($user->getClubId($ws, $db));
	}

	public function testGetClubIdUsesCachedValueAfterFirstCall(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$callCount = 0;
		$db = $this->mockDbWithCallback(function () use (&$callCount) {
			$callCount++;
			return $this->dbResult([['id' => 8]]);
		});
		$user = new User();
		$user->id = 1;
		$this->assertSame(8, $user->getClubId($ws, $db));
		$this->assertSame(8, $user->getClubId($ws, $db));
		// DB should have been queried only once (cached on second call).
		$this->assertSame(1, $callCount);
	}

	public function testIsAdminReturnsTrueWhenAdminRecordExists(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->mockDbWithRows([['id' => 1]]);
		\WebSoccer::setInstanceForTesting($ws);
		\DbConnection::setInstanceForTesting($db);
		$user = new User();
		$user->email = 'admin@test.local';
		$this->assertTrue($user->isAdmin());
	}

	public function testIsAdminReturnsFalseWhenNoAdminRecord(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->mockDbWithRows([]);
		\WebSoccer::setInstanceForTesting($ws);
		\DbConnection::setInstanceForTesting($db);
		$user = new User();
		$user->email = 'user@test.local';
		$this->assertFalse($user->isAdmin());
	}

	public function testIsAdminCachesResultAfterFirstCall(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$callCount = 0;
		$db = $this->mockDbWithCallback(function () use (&$callCount) {
			$callCount++;
			return $this->dbResult([['id' => 1]]);
		});
		\WebSoccer::setInstanceForTesting($ws);
		\DbConnection::setInstanceForTesting($db);
		$user = new User();
		$user->email = 'admin@test.local';
		$this->assertTrue($user->isAdmin());
		$this->assertTrue($user->isAdmin());
		$this->assertSame(1, $callCount);
	}

	public function testIsAdminReturnsFalseWhenDbResultHasZeroRows(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		$db = $this->mockDbWithRows([]);
		\WebSoccer::setInstanceForTesting($ws);
		\DbConnection::setInstanceForTesting($db);
		$user = new User();
		$user->email = 'noadmin@test.local';
		$this->assertFalse($user->isAdmin());
	}
}
