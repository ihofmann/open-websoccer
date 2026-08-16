<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\JobTestHelper;

if (!defined('JOBS_CONFIG_FILE')) {
	define('JOBS_CONFIG_FILE', sys_get_temp_dir() . '/ows_jobs_test.xml');
}

/**
 * Concrete subclass of AbstractJob for testing.
 */
class TestConcreteJob extends AbstractJob {
	public bool $executeCalled = false;
	public function execute() {
		$this->executeCalled = true;
	}
}

/**
 * Unit tests for AbstractJob.
 */
final class AbstractJobTest extends TestCaseBase {
	use JobTestHelper;

	protected function setUp(): void {
		parent::setUp();
		$this->writeJobConfig(0);
	}

	protected function tearDown(): void {
		// Ensure temp XML exists for any destructor that fires after tearDown.
		@file_put_contents(JOBS_CONFIG_FILE, $this->jobXml(0));
		parent::tearDown();
	}

	public function testConstructorStoresWebsoccer(): void {
		$ws = $this->mockWebsoccer($this->jobConfig());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		$job = new TestConcreteJob($ws, $db, $i18n, 'testjob', false);

		$ref = new \ReflectionProperty(AbstractJob::class, '_websoccer');
		$this->assertSame($ws, $ref->getValue($job));
	}

	public function testConstructorStoresDb(): void {
		$ws = $this->mockWebsoccer($this->jobConfig());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		$job = new TestConcreteJob($ws, $db, $i18n, 'testjob', false);

		$ref = new \ReflectionProperty(AbstractJob::class, '_db');
		$this->assertSame($db, $ref->getValue($job));
	}

	public function testConstructorStoresI18n(): void {
		$ws = $this->mockWebsoccer($this->jobConfig());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		$job = new TestConcreteJob($ws, $db, $i18n, 'testjob', false);

		$ref = new \ReflectionProperty(AbstractJob::class, '_i18n');
		$this->assertSame($i18n, $ref->getValue($job));
	}

	public function testConstructorStoresJobId(): void {
		$ws = $this->mockWebsoccer($this->jobConfig());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		$job = new TestConcreteJob($ws, $db, $i18n, 'testjob', false);

		$ref = new \ReflectionProperty(AbstractJob::class, '_id');
		$this->assertSame('testjob', $ref->getValue($job));
	}

	public function testConstructorComputesIntervalFromXml(): void {
		$ws = $this->mockWebsoccer($this->jobConfig());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		// testjob has interval=5 in the XML, so _interval should be 5*60=300.
		$job = new TestConcreteJob($ws, $db, $i18n, 'testjob', false);

		$ref = new \ReflectionProperty(AbstractJob::class, '_interval');
		$this->assertSame(300, $ref->getValue($job));
	}

	public function testExecuteIsCallableOnConcreteSubclass(): void {
		$ws = $this->mockWebsoccer($this->jobConfig());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		$job = new TestConcreteJob($ws, $db, $i18n, 'testjob', false);
		$job->execute();

		$this->assertTrue($job->executeCalled);
	}

	public function testConstructorSucceedsWithErrorOnAlreadyRunningWhenNoRecentInstance(): void {
		// inittime=0 means no recent instance, so no exception.
		$this->writeJobConfig(0);

		$ws = $this->mockWebsoccer($this->jobConfig());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		$job = new TestConcreteJob($ws, $db, $i18n, 'testjob', true);
		$this->assertFalse($job->executeCalled);
	}

	public function testConstructorThrowsWhenAnotherInstanceIsRunning(): void {
		// Set inittime to now so it appears an instance is already running.
		$this->writeJobConfig(time());

		$ws = $this->mockWebsoccer($this->jobConfig());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Another instance of this job is already running.');
		new TestConcreteJob($ws, $db, $i18n, 'testjob', true);
	}

	public function testConstructorDoesNotThrowWhenErrorOnAlreadyRunningIsFalse(): void {
		// Even with a recent inittime, errorOnAlreadyRunning=false skips the check.
		$this->writeJobConfig(time());

		$ws = $this->mockWebsoccer($this->jobConfig());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		$job = new TestConcreteJob($ws, $db, $i18n, 'testjob', false);
		$this->assertFalse($job->executeCalled);
	}
}
