<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Test event class for PluginMediator tests.
 */
class TestPluginEvent extends AbstractEvent {
	public bool $handled = false;
	public ?string $listenerName = null;
}

/**
 * Test event listener that records the dispatch.
 */
class TestPluginEventListener {
	public static ?TestPluginEvent $lastEvent = null;

	public static function handleEvent(AbstractEvent $event): void {
		self::$lastEvent = $event;
		if ($event instanceof TestPluginEvent) {
			$event->handled = true;
			$event->listenerName = 'TestPluginEventListener';
		}
	}
}

/**
 * Unit tests for PluginMediator.
 */
final class PluginMediatorTest extends TestCaseBase {
	/**
	 * Resets the static event listener config to null.
	 * Uses Closure::bind for reliable cross-version access to private static.
	 */
	private function resetListenerConfig(): void {
		$setter = \Closure::bind(function () {
			static::$_eventlistenerConfigs = null;
		}, null, \PluginMediator::class);
		$setter();
	}

	/**
	 * Sets the static event listener config to the given array.
	 * Uses Closure::bind for reliable cross-version access to private static.
	 */
	private function setListenerConfig(?array $config): void {
		$setter = \Closure::bind(function ($value) {
			static::$_eventlistenerConfigs = $value;
		}, null, \PluginMediator::class);
		$setter($config);
	}

	protected function setUp(): void {
		parent::setUp();
		$this->resetListenerConfig();
		TestPluginEventListener::$lastEvent = null;
	}

	public function testDispatchEventCallsConfiguredListener(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$this->setListenerConfig([
			'TestPluginEvent' => [
				json_encode(['class' => 'TestPluginEventListener', 'method' => 'handleEvent']),
			],
		]);

		$event = new TestPluginEvent($ws, $db, $i18n);
		PluginMediator::dispatchEvent($event);

		$this->assertTrue($event->handled);
		$this->assertSame('TestPluginEventListener', $event->listenerName);
		$this->assertSame($event, TestPluginEventListener::$lastEvent);
	}

	public function testDispatchEventDoesNothingWhenNoListenersConfigured(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$this->setListenerConfig([]);

		$event = new TestPluginEvent($ws, $db, $i18n);
		// Should not throw.
		PluginMediator::dispatchEvent($event);
		$this->assertFalse($event->handled);
	}

	public function testDispatchEventDoesNothingWhenNoListenersForEvent(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$this->setListenerConfig([
			'SomeOtherEvent' => [
				json_encode(['class' => 'TestPluginEventListener', 'method' => 'handleEvent']),
			],
		]);

		$event = new TestPluginEvent($ws, $db, $i18n);
		PluginMediator::dispatchEvent($event);
		$this->assertFalse($event->handled);
	}

	public function testDispatchEventThrowsWhenListenerMethodDoesNotExist(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$this->setListenerConfig([
			'TestPluginEvent' => [
				json_encode(['class' => 'TestPluginEventListener', 'method' => 'nonExistentMethod']),
			],
		]);

		$event = new TestPluginEvent($ws, $db, $i18n);
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Configured event listener must have function: TestPluginEventListener::nonExistentMethod');
		PluginMediator::dispatchEvent($event);
	}

	public function testDispatchEventCallsMultipleListenersForSameEvent(): void {
		$ws = $this->mockWebsoccer();
		$db = $this->mockDb();
		$i18n = $this->mockI18n();

		$this->setListenerConfig([
			'TestPluginEvent' => [
				json_encode(['class' => 'TestPluginEventListener', 'method' => 'handleEvent']),
				json_encode(['class' => 'TestPluginEventListener', 'method' => 'handleEvent']),
			],
		]);

		$event = new TestPluginEvent($ws, $db, $i18n);
		PluginMediator::dispatchEvent($event);
		// Both listeners call the same method; event is handled.
		$this->assertTrue($event->handled);
	}
}
