<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ActionHandler.
 *
 * Exercises the dispatching, permission and double-submit logic without
 * executing a real controller. The 'core' module.xml (a real module) is used
 * as the module config source so that ModuleConfigHelper can load it.
 */
final class ActionHandlerTest extends TestCaseBase {
	protected function setUp(): void {
		parent::setUp();
		// The double-submit constants are defined at file scope in
		// ActionHandler.class.php; ensure the class is loaded before tests
		// reference those constants.
		class_exists(\ActionHandler::class, true);
	}

	public function testHandleActionReturnsNullForNullActionId(): void {
		$ws = $this->createMock(\WebSoccer::class);
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();
		$this->assertNull(\ActionHandler::handleAction($ws, $db, $i18n, null));
	}

	public function testHandleActionThrowsOnDoubleSubmit(): void {
		$_SESSION[DOUBLE_SUBMIT_CHECK_SESSIONKEY_ACTIONID] = 'myact';
		$_SESSION[DOUBLE_SUBMIT_CHECK_SESSIONKEY_TIME] = time();

		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n(['error_double_submit' => 'double submit occurred']);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('double submit occurred');
		\ActionHandler::handleAction($ws, $db, $i18n, 'myact');
	}

	public function testHandleActionThrowsAccessDeniedForAdminActionByNonAdmin(): void {
		// Force isAdmin() to return FALSE without a DB query by presetting the
		// private _isAdmin flag via reflection.
		$user = $this->makeUser(['email' => 'guest@example.com']);
		$ref = new \ReflectionProperty(\User::class, '_isAdmin');
		$ref->setValue($user, false);

		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getUser')->willReturn($user);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getAction')->willReturn(json_encode([
			'role' => 'admin',
			'module' => 'core',
			'controller' => 'SomeController',
		]));
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n(['error_access_denied' => 'access denied']);

		$this->expectException(\AccessDeniedException::class);
		$this->expectExceptionMessage('access denied');
		\ActionHandler::handleAction($ws, $db, $i18n, 'adminact');
	}

	public function testHandleActionThrowsAccessDeniedForUserRoleActionByGuest(): void {
		$user = $this->makeUser([]); // guest

		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getUser')->willReturn($user);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getAction')->willReturn(json_encode([
			'role' => 'user',
			'module' => 'core',
			'controller' => 'SomeController',
		]));
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n(['error_access_denied' => 'access denied']);

		$this->expectException(\AccessDeniedException::class);
		\ActionHandler::handleAction($ws, $db, $i18n, 'useract');
	}

	public function testHandleActionThrowsWhenControllerNotFound(): void {
		$user = $this->makeUser([]); // guest, role 'guest'

		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getUser')->willReturn($user);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getAction')->willReturn(json_encode([
			'role' => 'guest',
			'module' => 'core',
			'controller' => 'NonExistentControllerXyz',
		]));
		$db = $this->createMock(\DbConnection::class);
		$i18n = $this->mockI18n();

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Controller not found: NonExistentControllerXyz');
		\ActionHandler::handleAction($ws, $db, $i18n, 'guestact');
	}
}
