<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for MoneyTransactionConverter.
 */
final class MoneyTransactionConverterTest extends TestCaseBase {
	public function testToHtmlReturnsValueUnchanged(): void {
		$c = new MoneyTransactionConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('100', $c->toHtml('100'));
	}

	public function testToTextReturnsValueUnchanged(): void {
		$c = new MoneyTransactionConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame('100', $c->toText('100'));
	}

	public function testToDbValueReturnsIntAmountWithoutPostVereinId(): void {
		$_POST = [];
		$c = new MoneyTransactionConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame(500, $c->toDbValue('500'));
	}

	public function testToDbValueCastsStringToInt(): void {
		$_POST = [];
		$c = new MoneyTransactionConverter($this->mockI18n(), $this->mockWebsoccer());
		$this->assertSame(500, $c->toDbValue('500abc'));
	}

	public function testToDbValueUpdatesBudgetWithPostVereinId(): void {
		$_POST = ['verein_id' => '3'];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['finanz_budget' => 1000]])
		);
		$db->expects($this->once())->method('queryUpdate')
			->with(
				$this->callback(function ($cols) {
					return $cols['finanz_budget'] === 1500;
				}),
				'ws_verein',
				'id = %d',
				'3'
			);
		\DbConnection::setInstanceForTesting($db);
		$c = new MoneyTransactionConverter($this->mockI18n(), $this->mockWebsoccer(['db_prefix' => 'ws']));
		$this->assertSame(500, $c->toDbValue('500'));
	}

	public function testToDbValueHandlesNegativeAmount(): void {
		$_POST = ['verein_id' => '3'];
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn(
			$this->dbResult([['finanz_budget' => 1000]])
		);
		$db->expects($this->once())->method('queryUpdate')
			->with(
				$this->callback(function ($cols) {
					return $cols['finanz_budget'] === 700;
				}),
				'ws_verein',
				'id = %d',
				'3'
			);
		\DbConnection::setInstanceForTesting($db);
		$c = new MoneyTransactionConverter($this->mockI18n(), $this->mockWebsoccer(['db_prefix' => 'ws']));
		$this->assertSame(-300, $c->toDbValue('-300'));
	}
}
