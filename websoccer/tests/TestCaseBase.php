<?php
namespace OpenWebSoccer\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Base class for all OpenWebSoccer unit tests.
 *
 * It provides ready-to-use factory methods for the three collaborators that
 * almost every class depends on (DbConnection, WebSoccer, I18n) and makes sure
 * the singleton instances are reset between tests so they never leak state.
 */
abstract class TestCaseBase extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		// Reset all singletons so a previous test cannot leak a mock instance.
		\DbConnection::setInstanceForTesting(null);
		\WebSoccer::setInstanceForTesting(null);
		\I18n::setInstanceForTesting(null);
		// Clear request superglobals.
		$_REQUEST = [];
		$_POST = [];
		$_GET = [];
		$_SESSION = [];
	}

	protected function tearDown(): void {
		\DbConnection::setInstanceForTesting(null);
		\WebSoccer::setInstanceForTesting(null);
		\I18n::setInstanceForTesting(null);
		parent::tearDown();
	}

	/**
	 * Creates a PHPUnit mock of the DbConnection singleton.
	 *
	 * The mock never opens a real connection. Tests configure the query
	 * methods (querySelect, queryUpdate, ...) as required.
	 */
	protected function mockDb(): \DbConnection&MockObject {
		$db = $this->createMock(\DbConnection::class);
		// By default, any select returns an empty result set.
		$db->method('querySelect')->willReturn(new MockDbResult([]));
		$db->method('queryCachedSelect')->willReturn([]);
		return $db;
	}

	/**
	 * Wraps a list of rows into a MockDbResult, handy for stubbing querySelect.
	 *
	 * @param array $rows list of associative arrays.
	 */
	protected function dbResult(array $rows = []): MockDbResult {
		return new MockDbResult($rows);
	}

	/**
	 * Creates a WebSoccer mock with getConfig() backed by the supplied map.
	 *
	 * @param array $config setting id => value, returned by getConfig().
	 */
	protected function mockWebsoccer(array $config = []): \WebSoccer&MockObject {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) {
				throw new \Exception('Missing configuration: ' . $name);
			}
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		return $ws;
	}

	/**
	 * Creates an I18n mock with getMessage()/hasMessage() backed by a map.
	 *
	 * @param array $messages message key => message string.
	 */
	protected function mockI18n(array $messages = []): \I18n&MockObject {
		$i18n = $this->createMock(\I18n::class);
		$i18n->method('hasMessage')->willReturnCallback(function ($key) use ($messages) {
			return array_key_exists($key, $messages);
		});
		$i18n->method('getMessage')->willReturnCallback(function ($key, $params = null) use ($messages) {
			if (!array_key_exists($key, $messages)) {
				return '???' . $key . '???';
			}
			$message = $messages[$key];
			return $params !== null ? sprintf($message, $params) : $message;
		});
		return $i18n;
	}

	/**
	 * Creates a User instance with the given fields populated.
	 */
	protected function makeUser(array $fields = []): \User {
		$user = new \User();
		foreach ($fields as $k => $v) {
			$user->$k = $v;
		}
		return $user;
	}
}
