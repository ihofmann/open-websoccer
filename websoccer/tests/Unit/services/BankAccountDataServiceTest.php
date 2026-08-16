<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for BankAccountDataService.
 */
final class BankAccountDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(array $config = []): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(array_merge([
			'db_prefix' => 'ws',
			'no_transactions_for_teams_without_user' => '0',
		], $config));
	}

	private function teamSummaryRow(array $overrides = []): array {
		return array_merge([
			'team_id' => '5',
			'team_name' => 'FC Test',
			'team_budget' => '1000000',
			'user_id' => '7',
		], $overrides);
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testCountAccountStatementsOfTeamReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '12']]));
		$this->assertSame('12', BankAccountDataService::countAccountStatementsOfTeam($ws, $db, 5));
	}

	public function testCountAccountStatementsOfTeamReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, BankAccountDataService::countAccountStatementsOfTeam($ws, $db, 5));
	}

	public function testGetAccountStatementsOfTeamReturnsRows(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['sender' => 'Sponsor', 'amount' => '500', 'date' => '100', 'subject' => 'win'],
			['sender' => 'Bank', 'amount' => '-200', 'date' => '200', 'subject' => 'fee'],
		]));
		$statements = BankAccountDataService::getAccountStatementsOfTeam($ws, $db, 5, 0, 10);
		$this->assertCount(2, $statements);
		$this->assertSame('Sponsor', $statements[0]['sender']);
	}

	public function testGetAccountStatementsOfTeamReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], BankAccountDataService::getAccountStatementsOfTeam($ws, $db, 5, 0, 10));
	}

	public function testCreditAmountDoesNothingWhenAmountZero(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryCachedSelect');
		$db->expects($this->never())->method('queryInsert');
		BankAccountDataService::creditAmount($ws, $db, 5, 0, 'subject', 'sender');
	}

	public function testCreditAmountThrowsWhenTeamNotFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->expectException(\Exception::class);
		BankAccountDataService::creditAmount($ws, $db, 999, 100, 'subject', 'sender');
	}

	public function testCreditAmountThrowsWhenAmountNegative(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([$this->teamSummaryRow()]);
		$this->expectException(\Exception::class);
		BankAccountDataService::creditAmount($ws, $db, 5, -50, 'subject', 'sender');
	}

	public function testCreditAmountCreatesTransactionAndUpdatesBudget(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([$this->teamSummaryRow()]);
		$db->expects($this->once())->method('queryInsert');
		$db->expects($this->once())->method('queryUpdate');
		BankAccountDataService::creditAmount($ws, $db, 5, 500, 'subject', 'sender');
	}

	public function testCreditAmountSkipsTransactionForTeamWithoutUserWhenConfigured(): void {
		$ws = $this->makeWebsoccer(['no_transactions_for_teams_without_user' => '1']);
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([$this->teamSummaryRow(['user_id' => ''])]);
		$db->expects($this->never())->method('queryInsert');
		$db->expects($this->never())->method('queryUpdate');
		BankAccountDataService::creditAmount($ws, $db, 5, 500, 'subject', 'sender');
	}

	public function testDebitAmountDoesNothingWhenAmountZero(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->expects($this->never())->method('queryCachedSelect');
		BankAccountDataService::debitAmount($ws, $db, 5, 0, 'subject', 'sender');
	}

	public function testDebitAmountThrowsWhenTeamNotFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$this->expectException(\Exception::class);
		BankAccountDataService::debitAmount($ws, $db, 999, 100, 'subject', 'sender');
	}

	public function testDebitAmountThrowsWhenAmountNegative(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([$this->teamSummaryRow()]);
		$this->expectException(\Exception::class);
		BankAccountDataService::debitAmount($ws, $db, 5, -50, 'subject', 'sender');
	}

	public function testDebitAmountCreatesNegativeTransaction(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([$this->teamSummaryRow()]);
		$db->expects($this->once())->method('queryInsert');
		$db->expects($this->once())->method('queryUpdate');
		BankAccountDataService::debitAmount($ws, $db, 5, 300, 'subject', 'sender');
	}
}
