<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for PremiumDataService.
 */
final class PremiumDataServiceTest extends TestCaseBase {
	private \WebSoccer $ws;

	protected function setUp(): void {
		parent::setUp();
		$this->ws = $this->mockWebsoccer([
			'db_prefix' => 'ws',
			'gravatar_enable' => 0,
			'context_root' => '',
			'supported_languages' => 'en',
			'premium_price_options' => '5:500,10:1000',
		]);
		// Default session user with a different ID so the profile-update branch
		// in createTransaction() is not triggered unless a test overrides this.
		$this->ws->method('getUser')->willReturn($this->makeUser(['id' => 999]));
	}

	private function makeDbWithUserRow(array $userRow, ?\DbConnection $db = null): \DbConnection {
		$db = $db ?? $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$userRow]));
		return $db;
	}

	public function testCountAccountStatementsOfUserReturnsHits(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([['hits' => 12]]));
		$this->assertSame(12, PremiumDataService::countAccountStatementsOfUser($this->ws, $db, 5));
	}

	public function testCountAccountStatementsOfUserReturnsZeroWhenEmpty(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame(0, PremiumDataService::countAccountStatementsOfUser($this->ws, $db, 5));
	}

	public function testGetAccountStatementsOfUserReturnsList(): void {
		$rows = [['id' => 1, 'amount' => 50], ['id' => 2, 'amount' => -20]];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$this->assertSame($rows, PremiumDataService::getAccountStatementsOfUser($this->ws, $db, 5, 0, 10));
	}

	public function testGetAccountStatementsOfUserReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], PremiumDataService::getAccountStatementsOfUser($this->ws, $db, 5, 0, 10));
	}

	public function testCreditAmountWithZeroDoesNothing(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryInsert');
		$db->expects($this->never())->method('queryUpdate');
		PremiumDataService::creditAmount($this->ws, $db, 5, 0, 'subject');
	}

	public function testCreditAmountThrowsWhenUserNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('user not found: 5');
		PremiumDataService::creditAmount($this->ws, $db, 5, 100, 'subject');
	}

	public function testCreditAmountThrowsForNegativeAmount(): void {
		$db = $this->makeDbWithUserRow(['id' => 5, 'premium_balance' => 100, 'picture' => '', 'email' => '']);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('amount illegal: -50');
		PremiumDataService::creditAmount($this->ws, $db, 5, -50, 'subject');
	}

	public function testCreditAmountCreatesTransactionAndUpdatesBudget(): void {
		$userRow = ['id' => 5, 'premium_balance' => 100, 'picture' => '', 'email' => ''];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$userRow]));
		$insertCalls = [];
		$updateCalls = [];
		$db->method('queryInsert')->willReturnCallback(function ($cols) use (&$insertCalls) {
			$insertCalls[] = $cols;
		});
		$db->method('queryUpdate')->willReturnCallback(function ($cols, $table, $where, $params) use (&$updateCalls) {
			$updateCalls[] = ['cols' => $cols, 'table' => $table, 'where' => $where, 'params' => $params];
		});

		PremiumDataService::creditAmount($this->ws, $db, 5, 50, 'donation', ['foo' => 'bar']);

		$this->assertCount(1, $insertCalls);
		$this->assertSame(5, $insertCalls[0]['user_id']);
		$this->assertSame(50, $insertCalls[0]['amount']);
		$this->assertSame('donation', $insertCalls[0]['action_id']);
		$this->assertSame('{"foo":"bar"}', $insertCalls[0]['subject_data']);

		$this->assertCount(1, $updateCalls);
		$this->assertSame(150, $updateCalls[0]['cols']['premium_balance']);
		$this->assertSame(5, $updateCalls[0]['params']);
	}

	public function testCreditAmountUpdatesUserProfileWhenSameUser(): void {
		$userRow = ['id' => 5, 'premium_balance' => 100, 'picture' => '', 'email' => ''];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$userRow]));
		$db->method('queryInsert');
		$db->method('queryUpdate');

		$sessionUser = $this->makeUser(['id' => 5, 'premiumBalance' => 100]);
		$ws = $this->mockWebsoccer([
			'db_prefix' => 'ws', 'gravatar_enable' => 0, 'context_root' => '',
			'supported_languages' => 'en',
		]);
		$ws->method('getUser')->willReturn($sessionUser);

		PremiumDataService::creditAmount($ws, $db, 5, 50, 'donation');
		$this->assertSame(150, $sessionUser->premiumBalance);
	}

	public function testDebitAmountWithZeroDoesNothing(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryInsert');
		$db->expects($this->never())->method('queryUpdate');
		PremiumDataService::debitAmount($this->ws, $db, 5, 0, 'subject');
	}

	public function testDebitAmountThrowsWhenUserNotFound(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('user not found: 5');
		PremiumDataService::debitAmount($this->ws, $db, 5, 50, 'subject');
	}

	public function testDebitAmountThrowsForNegativeAmount(): void {
		$db = $this->makeDbWithUserRow(['id' => 5, 'premium_balance' => 100, 'picture' => '', 'email' => '']);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('amount illegal: -50');
		PremiumDataService::debitAmount($this->ws, $db, 5, -50, 'subject');
	}

	public function testDebitAmountThrowsWhenBalanceNotEnough(): void {
		$i18n = $this->mockI18n(['premium_balance_notenough' => 'Not enough premium credit.']);
		\I18n::setInstanceForTesting($i18n);
		$db = $this->makeDbWithUserRow(['id' => 5, 'premium_balance' => 30, 'picture' => '', 'email' => '']);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Not enough premium credit.');
		PremiumDataService::debitAmount($this->ws, $db, 5, 50, 'subject');
	}

	public function testDebitAmountNegatesAmountAndUpdatesBudget(): void {
		$userRow = ['id' => 5, 'premium_balance' => 100, 'picture' => '', 'email' => ''];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$userRow]));
		$insertCalls = [];
		$updateCalls = [];
		$db->method('queryInsert')->willReturnCallback(function ($cols) use (&$insertCalls) {
			$insertCalls[] = $cols;
		});
		$db->method('queryUpdate')->willReturnCallback(function ($cols) use (&$updateCalls) {
			$updateCalls[] = $cols;
		});

		PremiumDataService::debitAmount($this->ws, $db, 5, 30, 'purchase');

		$this->assertSame(-30, $insertCalls[0]['amount']);
		$this->assertSame(70, $updateCalls[0]['premium_balance']);
	}

	public function testCreatePaymentAndCreditPremiumThrowsForNonPositiveAmount(): void {
		$db = $this->createMock(\DbConnection::class);
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Illegal amount: 0');
		PremiumDataService::createPaymentAndCreditPremium($this->ws, $db, 5, 0, 'payment');
	}

	public function testCreatePaymentAndCreditPremiumThrowsWhenNoPriceOptionMatches(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([
			['id' => 5, 'premium_balance' => 0, 'picture' => '', 'email' => ''],
		]));
		$db->method('queryInsert');
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('No price option found for amount: 7');
		PremiumDataService::createPaymentAndCreditPremium($this->ws, $db, 5, 7, 'payment');
	}

	public function testCreatePaymentAndCreditPremiumCreditsMatchingOption(): void {
		$userRow = ['id' => 5, 'premium_balance' => 0, 'picture' => '', 'email' => ''];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([$userRow]));
		$insertCount = 0;
		$db->method('queryInsert')->willReturnCallback(function () use (&$insertCount) {
			$insertCount++;
		});
		$db->method('queryUpdate');

		PremiumDataService::createPaymentAndCreditPremium($this->ws, $db, 5, 5, 'payment');
		// one insert for payment log, one insert for the credited statement
		$this->assertSame(2, $insertCount);
	}

	public function testGetPaymentsOfUserDividesAmountBy100(): void {
		$rows = [['id' => 1, 'amount' => 500, 'user_id' => 5], ['id' => 2, 'amount' => 1000, 'user_id' => 5]];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult($rows));
		$payments = PremiumDataService::getPaymentsOfUser($this->ws, $db, 5, 10);
		$this->assertCount(2, $payments);
		$this->assertEquals(5, $payments[0]['amount']);
		$this->assertEquals(10, $payments[1]['amount']);
	}

	public function testGetPaymentsOfUserReturnsEmptyWhenNone(): void {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$this->assertSame([], PremiumDataService::getPaymentsOfUser($this->ws, $db, 5, 10));
	}
}
