<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for UsersDataService.
 */
final class UsersDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		unset($_SERVER['HTTPS']);
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'gravatar_enable' => 0,
			'context_root' => '/soccer',
			'supported_languages' => 'en',
			'premium_initial_credit' => 0,
		]);
	}

	public function testCreateLocalUserThrowsWhenBothNickAndEmailBlank(): void {
		$db = $this->mockDb();
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Either user name or e-mail must be provided');
		UsersDataService::createLocalUser($this->ws, $db, '   ', '');
	}

	public function testCreateLocalUserThrowsWhenNickAlreadyInUse(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['id' => 5]]));
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Nick name is already in use.');
		UsersDataService::createLocalUser($this->ws, $db, 'existing', '');
	}

	public function testCreateLocalUserThrowsWhenEmailAlreadyInUse(): void {
		$db = $this->createMock(\DbConnection::class);
		// nick is blank so getUserIdByNick is never called; only getUserIdByEmail.
		$db->method('querySelect')->willReturn($this->dbResult([['id' => 5]]));
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('E-Mail address is already in use.');
		UsersDataService::createLocalUser($this->ws, $db, '', 'existing@e.com');
	}

	public function testCreateLocalUserCreatesAndReturnsNewId(): void {
		$i18n = $this->mockI18n([]);
		\I18n::setInstanceForTesting($i18n);
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls(
			$this->dbResult([]),            // nick lookup -> -1
			$this->dbResult([]),            // email lookup -> -1
			$this->dbResult([['id' => 42]]) // nick lookup after insert -> 42
		);
		$inserted = false;
		$db->method('queryInsert')->willReturnCallback(function () use (&$inserted) {
			$inserted = true;
		});
		$userId = UsersDataService::createLocalUser($this->ws, $db, 'newuser', 'new@e.com');
		$this->assertTrue($inserted);
		$this->assertSame(42, $userId);
	}

	public function testCountActiveUsersWithHighscoresReturnsNumberOfRows(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['hits' => 1], ['hits' => 1], ['hits' => 1],
		]));
		$this->assertSame(3, UsersDataService::countActiveUsersWithHighscore($this->ws, $db));
	}

	public function testCountActiveUsersWithHighscoresReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, UsersDataService::countActiveUsersWithHighscore($this->ws, $db));
	}

	public function testGetActiveUsersWithHighscoresReturnsListWithPicture(): void {
		$rows = [
			['id' => 1, 'nick' => 'a', 'email' => '', 'picture' => 'p1.jpg', 'highscore' => 10, 'registration_date' => 1, 'team_id' => null, 'team_name' => null, 'team_picture' => null],
			['id' => 2, 'nick' => 'b', 'email' => '', 'picture' => '', 'highscore' => 5, 'registration_date' => 2, 'team_id' => null, 'team_name' => null, 'team_picture' => null],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$users = UsersDataService::getActiveUsersWithHighscore($this->ws, $db, 0, 10);
		$this->assertCount(2, $users);
		$this->assertSame('/soccer/uploads/users/p1.jpg', $users[0]['picture']);
		// empty picture + gravatar disabled -> null
		$this->assertNull($users[1]['picture']);
	}

	public function testGetUserByIdReturnsRowWithPictureUploadfileAndPicture(): void {
		$row = ['id' => 5, 'nick' => 'u', 'email' => '', 'highscore' => 10, 'popularity' => 0,
			'registration_date' => 1, 'lastonline' => 2, 'picture' => 'me.jpg', 'history' => '',
			'name' => '', 'place' => '', 'country' => '', 'birthday' => '', 'occupation' => '',
			'interests' => '', 'favorite_club' => '', 'homepage' => '', 'premium_balance' => 0];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$row]));
		$user = UsersDataService::getUserById($this->ws, $db, 5);
		$this->assertSame(5, $user['id']);
		$this->assertSame('me.jpg', $user['picture_uploadfile']);
		$this->assertSame('/soccer/uploads/users/me.jpg', $user['picture']);
	}

	public function testGetUserByIdReturnsFalseWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertFalse(UsersDataService::getUserById($this->ws, $db, 999));
	}

	public function testGetUserIdByNickReturnsId(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['id' => 7]]));
		$this->assertSame(7, UsersDataService::getUserIdByNick($this->ws, $db, 'nick'));
	}

	public function testGetUserIdByNickReturnsMinusOneWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(-1, UsersDataService::getUserIdByNick($this->ws, $db, 'nick'));
	}

	public function testGetUserIdByEmailReturnsId(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['id' => 8]]));
		$this->assertSame(8, UsersDataService::getUserIdByEmail($this->ws, $db, 'a@b.com'));
	}

	public function testGetUserIdByEmailReturnsMinusOneWhenNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(-1, UsersDataService::getUserIdByEmail($this->ws, $db, 'a@b.com'));
	}

	public function testFindUsernamesReturnsListOfNicks(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['nick' => 'alice'], ['nick' => 'alfred'],
		]));
		$this->assertSame(['alice', 'alfred'], UsersDataService::findUsernames($this->ws, $db, 'al'));
	}

	public function testFindUsernamesReturnsEmptyWhenNoMatch(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], UsersDataService::findUsernames($this->ws, $db, 'zz'));
	}

	public function testGetUserProfilePictureReturnsUploadUrlForFileName(): void {
		$url = UsersDataService::getUserProfilePicture($this->ws, 'pic.jpg', 'a@b.com');
		$this->assertSame('/soccer/uploads/users/pic.jpg', $url);
	}

	public function testGetUserProfilePictureReturnsNullForGravatarDisabled(): void {
		$this->assertNull(UsersDataService::getUserProfilePicture($this->ws, '', 'a@b.com'));
	}

	public function testGetGravatarUserProfilePictureReturnsUrlWhenEnabled(): void {
		$ws = $this->mockWebsoccer(['gravatar_enable' => 1]);
		$url = UsersDataService::getGravatarUserProfilePicture($ws, 'A@B.com', 40);
		$this->assertSame('http://www.gravatar.com/avatar/' . md5('a@b.com') . '?s=40&d=mm', $url);
	}

	public function testGetGravatarUserProfilePictureReturnsNullWhenDisabled(): void {
		$this->assertNull(UsersDataService::getGravatarUserProfilePicture($this->ws, 'a@b.com', 40));
	}

	public function testGetGravatarUserProfilePictureReturnsNullForEmptyEmail(): void {
		$ws = $this->mockWebsoccer(['gravatar_enable' => 1]);
		$this->assertNull(UsersDataService::getGravatarUserProfilePicture($ws, '', 40));
	}

	public function testCountOnlineUsersReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 4]]));
		$this->assertSame(4, UsersDataService::countOnlineUsers($this->ws, $db));
	}

	public function testCountOnlineUsersReturnsZeroWhenNoHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['other' => 0]]));
		$this->assertSame(0, UsersDataService::countOnlineUsers($this->ws, $db));
	}

	public function testGetOnlineUsersReturnsList(): void {
		$rows = [
			['id' => 1, 'nick' => 'a', 'email' => '', 'picture' => 'p.jpg', 'lastonline' => 1, 'lastaction' => '', 'c_hideinonlinelist' => 0, 'team_id' => null, 'team_name' => null, 'team_picture' => null],
		];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$users = UsersDataService::getOnlineUsers($this->ws, $db, 0, 10);
		$this->assertCount(1, $users);
		$this->assertSame('/soccer/uploads/users/p.jpg', $users[0]['picture']);
	}

	public function testCountTotalUsersReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 99]]));
		$this->assertSame(99, UsersDataService::countTotalUsers($this->ws, $db));
	}

	public function testCountTotalUsersReturnsZeroWhenNoHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['foo' => 'bar']]));
		$this->assertSame(0, UsersDataService::countTotalUsers($this->ws, $db));
	}
}
