<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for PremiumTransactionConverter.
 */
final class PremiumTransactionConverterTest extends TestCaseBase {
	public function testToHtmlReturnsValueUnchanged(): void {
		$c = new PremiumTransactionConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('50', $c->toHtml('50'));
	}

	public function testToTextReturnsValueUnchanged(): void {
		$c = new PremiumTransactionConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('50', $c->toText('50'));
	}

	public function testToDbValueReturnsIntAmountWithoutPostUserId(): void {
		$_POST = [];
		$c = new PremiumTransactionConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame(50, $c->toDbValue('50'));
	}

	public function testToDbValueUpdatesBalanceWithPostUserId(): void {
		$_POST = ['user_id' => '9'];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['premium_balance' => 200]])
		);
		$db->expects($this->once())->method('queryUpdate')
			->with(
				$this->callback(function ($cols) {
					return $cols['premium_balance'] === 250;
				}),
				'ws_user',
				'id = %d',
				'9'
			);
		\DbConnection::setInstanceForTesting($db);
		$c = new PremiumTransactionConverter($this->mockI18n(), $this->mockWebsoccer(['db_prefix' => 'ws']));
		$this->assertSame(50, $c->toDbValue('50'));
	}

	public function testToDbValueHandlesNegativeAmount(): void {
		$_POST = ['user_id' => '9'];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['premium_balance' => 200]])
		);
		$db->expects($this->once())->method('queryUpdate')
			->with(
				$this->callback(function ($cols) {
					return $cols['premium_balance'] === 150;
				}),
				'ws_user',
				'id = %d',
				'9'
			);
		\DbConnection::setInstanceForTesting($db);
		$c = new PremiumTransactionConverter($this->mockI18n(), $this->mockWebsoccer(['db_prefix' => 'ws']));
		$this->assertSame(-50, $c->toDbValue('-50'));
	}
}
