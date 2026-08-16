<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for EmailHelper.
 *
 * The mail() function returns false in a test/CLI environment without a
 * configured mail server, so sendSystemEmail() should throw.
 */
final class EmailHelperTest extends TestCaseBase {
	public function testSendSystemEmailThrowsWhenMailFails(): void {
		$ws = $this->mockWebsoccer([
			'projectname' => 'TestProject',
			'systememail' => 'system@test.local',
		]);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('e-mail not sent.');
		EmailHelper::sendSystemEmail($ws, 'recipient@test.local', 'Subject', 'Body content');
	}

	public function testSendSystemEmailReadsConfigForFromNameAndEmail(): void {
		$ws = $this->mockWebsoccer([
			'projectname' => 'MyProject',
			'systememail' => 'noreply@myproject.local',
		]);
		try {
			EmailHelper::sendSystemEmail($ws, 'r@t.local', 'S', 'B');
			$this->fail('Expected exception was not thrown.');
		} catch (\Exception $e) {
			$this->assertSame('e-mail not sent.', $e->getMessage());
		}
	}

	public function testSendSystemEmailThrowsOnMissingProjectNameConfig(): void {
		$ws = $this->mockWebsoccer([]);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Missing configuration: projectname');
		EmailHelper::sendSystemEmail($ws, 'r@t.local', 'S', 'B');
	}

	public function testSendSystemEmailThrowsOnMissingSystemEmailConfig(): void {
		$ws = $this->mockWebsoccer([
			'projectname' => 'TestProject',
		]);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Missing configuration: systememail');
		EmailHelper::sendSystemEmail($ws, 'r@t.local', 'S', 'B');
	}
}
