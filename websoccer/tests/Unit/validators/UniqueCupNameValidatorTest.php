<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for UniqueCupNameValidator.
 */
final class UniqueCupNameValidatorTest extends TestCaseBase {
	private function makeValidator(string $value, \DbConnection $db): UniqueCupNameValidator {
		$i18n = $this->mockI18n(['validation_error_uniquecupname' => 'Cup name already exists.']);
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws']);
		\DbConnection::setInstanceForTesting($db);
		return new UniqueCupNameValidator($i18n, $ws, $value);
	}

	private function makeDb(MockDbResult $cupResult, MockDbResult $matchResult): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnOnConsecutiveCalls($cupResult, $matchResult);
		return $db;
	}

	public function testIsValidReturnsTrueWhenNoCupAndNoMatches(): void {
		$db = $this->makeDb($this->dbResult([]), $this->dbResult([['hits' => 0]]));
		$v = $this->makeValidator('NewCup', $db);
		$this->assertTrue($v->isValid());
	}

	public function testIsValidReturnsFalseWhenCupExistsWithoutPostId(): void {
		$_POST = [];
		$db = $this->makeDb($this->dbResult([['id' => '5']]), $this->dbResult([['hits' => 0]]));
		$v = $this->makeValidator('ExistingCup', $db);
		$this->assertFalse($v->isValid());
	}

	public function testIsValidReturnsFalseWhenCupExistsWithDifferentPostId(): void {
		$_POST = ['id' => '99'];
		$db = $this->makeDb($this->dbResult([['id' => '5']]), $this->dbResult([['hits' => 0]]));
		$v = $this->makeValidator('ExistingCup', $db);
		$this->assertFalse($v->isValid());
	}

	public function testIsValidReturnsTrueWhenCupExistsWithSamePostId(): void {
		$_POST = ['id' => '5'];
		$db = $this->makeDb($this->dbResult([['id' => '5']]), $this->dbResult([['hits' => 0]]));
		$v = $this->makeValidator('ExistingCup', $db);
		$this->assertTrue($v->isValid());
	}

	public function testIsValidReturnsFalseWhenMatchesExistWithoutPostId(): void {
		$_POST = [];
		$db = $this->makeDb($this->dbResult([]), $this->dbResult([['hits' => '3']]));
		$v = $this->makeValidator('CupInMatches', $db);
		$this->assertFalse($v->isValid());
	}

	public function testIsValidReturnsTrueWhenMatchesExistWithPostId(): void {
		$_POST = ['id' => '5'];
		$db = $this->makeDb($this->dbResult([['id' => '5']]), $this->dbResult([['hits' => '3']]));
		$v = $this->makeValidator('CupInMatches', $db);
		$this->assertTrue($v->isValid());
	}

	public function testGetMessageReturnsI18nMessage(): void {
		$db = $this->makeDb($this->dbResult([]), $this->dbResult([['hits' => 0]]));
		$v = $this->makeValidator('NewCup', $db);
		$this->assertSame('Cup name already exists.', $v->getMessage());
	}
}
