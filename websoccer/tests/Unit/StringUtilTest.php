<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for StringUtil.
 */
final class StringUtilTest extends TestCaseBase {
	public function testStartsWithReturnsTrueForMatchingPrefix(): void {
		$this->assertTrue(StringUtil::startsWith('hello world', 'hello'));
	}

	public function testStartsWithReturnsFalseForNonMatchingPrefix(): void {
		$this->assertFalse(StringUtil::startsWith('hello world', 'world'));
	}

	public function testStartsWithReturnsTrueForEmptyNeedle(): void {
		$this->assertTrue(StringUtil::startsWith('hello', ''));
	}

	public function testStartsWithReturnsTrueForExactMatch(): void {
		$this->assertTrue(StringUtil::startsWith('hello', 'hello'));
	}

	public function testEndsWithReturnsTrueForMatchingSuffix(): void {
		$this->assertTrue(StringUtil::endsWith('hello world', 'world'));
	}

	public function testEndsWithReturnsFalseForNonMatchingSuffix(): void {
		$this->assertFalse(StringUtil::endsWith('hello world', 'hello'));
	}

	public function testEndsWithReturnsTrueForEmptyNeedle(): void {
		$this->assertTrue(StringUtil::endsWith('hello', ''));
	}

	public function testEndsWithReturnsTrueForExactMatch(): void {
		$this->assertTrue(StringUtil::endsWith('hello', 'hello'));
	}

	public function testConvertTimestampToWordReturnsEmptyForFarFuture(): void {
		$i18n = $this->mockI18n(['date_today' => 'Today']);
		$now = strtotime('2024-01-15 12:00:00');
		$far = $now + 3 * 24 * 3600;
		$this->assertSame('', StringUtil::convertTimestampToWord($far, $now, $i18n));
	}

	public function testConvertTimestampToWordReturnsTomorrow(): void {
		$i18n = $this->mockI18n(['date_tomorrow' => 'Tomorrow']);
		$now = strtotime('2024-01-15 12:00:00');
		$tomorrow = strtotime('2024-01-16 12:00:00');
		$this->assertSame('Tomorrow', StringUtil::convertTimestampToWord($tomorrow, $now, $i18n));
	}

	public function testConvertTimestampToWordReturnsToday(): void {
		$i18n = $this->mockI18n(['date_today' => 'Today']);
		$now = strtotime('2024-01-15 12:00:00');
		$today = strtotime('2024-01-15 08:00:00');
		$this->assertSame('Today', StringUtil::convertTimestampToWord($today, $now, $i18n));
	}

	public function testConvertTimestampToWordReturnsYesterday(): void {
		$i18n = $this->mockI18n(['date_yesterday' => 'Yesterday']);
		$now = strtotime('2024-01-15 12:00:00');
		$yesterday = strtotime('2024-01-14 12:00:00');
		$this->assertSame('Yesterday', StringUtil::convertTimestampToWord($yesterday, $now, $i18n));
	}

	public function testConvertTimestampToWordReturnsEmptyForDistantPast(): void {
		$i18n = $this->mockI18n(['date_yesterday' => 'Yesterday']);
		$now = strtotime('2024-01-15 12:00:00');
		$past = $now - 5 * 24 * 3600;
		$this->assertSame('', StringUtil::convertTimestampToWord($past, $now, $i18n));
	}
}
