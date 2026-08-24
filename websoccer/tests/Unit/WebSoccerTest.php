<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for WebSoccer context singleton.
 */
final class WebSoccerTest extends TestCaseBase {
	protected function setUp(): void {
		parent::setUp();
		// Reset the config / action globals used by getConfig() and getAction().
		$GLOBALS['conf'] = [];
		$GLOBALS['action'] = [];
	}

	protected function tearDown(): void {
		$GLOBALS['conf'] = [];
		$GLOBALS['action'] = [];
		parent::tearDown();
	}

	public function testGetInstanceReturnsSingleton(): void {
		$a = \WebSoccer::getInstance();
		$b = \WebSoccer::getInstance();
		$this->assertSame($a, $b);
	}

	public function testSetInstanceForTestingReplacesInstance(): void {
		$mock = $this->createMock(\WebSoccer::class);
		\WebSoccer::setInstanceForTesting($mock);
		$this->assertSame($mock, \WebSoccer::getInstance());
	}

	public function testGetConfigReturnsValueFromGlobalConf(): void {
		$GLOBALS['conf']['foo'] = 'bar';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('bar', $ws->getConfig('foo'));
	}

	public function testGetConfigThrowsOnMissing(): void {
		$ws = \WebSoccer::getInstance();
		$this->expectException(\Exception::class);
		$ws->getConfig('definitely_not_a_real_setting_xyz_123');
	}

	public function testGetActionReturnsValueFromGlobalAction(): void {
		$GLOBALS['action']['myact'] = '{"role":"guest"}';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('{"role":"guest"}', $ws->getAction('myact'));
	}

	public function testGetActionThrowsOnMissing(): void {
		$ws = \WebSoccer::getInstance();
		$this->expectException(\Exception::class);
		$ws->getAction('no_such_action');
	}

	public function testGetRequestParameterReturnsTrimmedValue(): void {
		$_REQUEST['x'] = '  hi  ';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('hi', $ws->getRequestParameter('x'));
	}

	public function testGetRequestParameterReturnsNullWhenEmpty(): void {
		$_REQUEST['x'] = '   ';
		$ws = \WebSoccer::getInstance();
		$this->assertNull($ws->getRequestParameter('x'));
	}

	public function testGetRequestParameterReturnsNullWhenAbsent(): void {
		$ws = \WebSoccer::getInstance();
		$this->assertNull($ws->getRequestParameter('nope'));
	}

	public function testGetInternalUrl(): void {
		$GLOBALS['conf']['context_root'] = '/ws';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('/ws/?page=home', $ws->getInternalUrl('home'));
	}

	public function testGetInternalUrlAcceptsNullQueryString(): void {
		$GLOBALS['conf']['context_root'] = '/ws';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('/ws/?page=home', $ws->getInternalUrl('home', null));
	}

	public function testGetInternalUrlWithQueryString(): void {
		$GLOBALS['conf']['context_root'] = '/ws';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('/ws/?page=home&x=1', $ws->getInternalUrl('home', 'x=1'));
	}

	public function testGetInternalUrlFullUrlForHomeWithoutQuery(): void {
		$GLOBALS['conf']['homepage'] = 'http://example.com';
		$GLOBALS['conf']['context_root'] = '/ws';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('http://example.com/ws', $ws->getInternalUrl('home', '', true));
	}

	public function testGetInternalUrlFullUrlForHomeWithQuery(): void {
		$GLOBALS['conf']['homepage'] = 'http://example.com';
		$GLOBALS['conf']['context_root'] = '/ws';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('http://example.com/ws/?page=home&x=1', $ws->getInternalUrl('home', 'x=1', true));
	}

	public function testGetInternalUrlFullUrlForNonHome(): void {
		$GLOBALS['conf']['homepage'] = 'http://example.com';
		$GLOBALS['conf']['context_root'] = '/ws';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('http://example.com/ws/?page=profile', $ws->getInternalUrl('profile', '', true));
	}

	public function testGetInternalActionUrl(): void {
		$GLOBALS['conf']['context_root'] = '/ws';
		$_REQUEST['page'] = 'home';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('/ws/?page=home&action=doit', $ws->getInternalActionUrl('doit'));
	}

	public function testGetInternalActionUrlWithQueryAndPage(): void {
		$GLOBALS['conf']['context_root'] = '/ws';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('/ws/?page=home&x=1&action=doit', $ws->getInternalActionUrl('doit', 'x=1', 'home'));
	}

	public function testGetInternalActionUrlFullUrl(): void {
		$GLOBALS['conf']['homepage'] = 'http://example.com';
		$GLOBALS['conf']['context_root'] = '/ws';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('http://example.com/ws/?page=home&action=doit', $ws->getInternalActionUrl('doit', '', 'home', true));
	}

	public function testGetNowAsTimestampAppliesTimeOffset(): void {
		$GLOBALS['conf']['time_offset'] = 3600;
		$ws = \WebSoccer::getInstance();
		$this->assertEqualsWithDelta(time() + 3600, $ws->getNowAsTimestamp(), 2);
	}

	public function testGetNowAsTimestampDefaultsToZeroOffset(): void {
		$GLOBALS['conf']['time_offset'] = 0;
		$ws = \WebSoccer::getInstance();
		$this->assertEqualsWithDelta(time(), $ws->getNowAsTimestamp(), 2);
	}

	public function testGetFormattedDate(): void {
		$GLOBALS['conf']['date_format'] = 'Y-m-d';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('2024-01-15', $ws->getFormattedDate(strtotime('2024-01-15 10:30:00')));
	}

	public function testGetFormattedDateDefaultsToNow(): void {
		$GLOBALS['conf']['date_format'] = 'Y';
		$GLOBALS['conf']['time_offset'] = 0;
		$ws = \WebSoccer::getInstance();
		$this->assertSame(date('Y'), $ws->getFormattedDate());
	}

	public function testGetFormattedDatetime(): void {
		$GLOBALS['conf']['datetime_format'] = 'Y-m-d H:i';
		$ws = \WebSoccer::getInstance();
		$this->assertSame('2024-01-15 10:30', $ws->getFormattedDatetime(strtotime('2024-01-15 10:30:00')));
	}

	public function testGetFormattedDatetimeWithI18nReturnsTodayWord(): void {
		$GLOBALS['conf']['time_offset'] = 0;
		$GLOBALS['conf']['time_format'] = 'H:i';
		$i18n = $this->mockI18n(['date_today' => 'Today']);
		$ws = \WebSoccer::getInstance();
		$ts = time();
		$this->assertSame('Today, ' . date('H:i', $ts), $ws->getFormattedDatetime($ts, $i18n));
	}

	public function testAddFrontMessageAndGetFrontMessages(): void {
		$ws = \WebSoccer::getInstance();
		$msg = new \FrontMessage(MESSAGE_TYPE_INFO, 'Title', 'Body');
		$ws->addFrontMessage($msg);
		$messages = $ws->getFrontMessages();
		$this->assertCount(1, $messages);
		$this->assertSame($msg, $messages[0]);
	}

	public function testGetFrontMessagesEmptyByDefault(): void {
		$ws = \WebSoccer::getInstance();
		$this->assertSame([], $ws->getFrontMessages());
	}

	public function testSetAjaxRequestAndIsAjaxRequest(): void {
		$ws = \WebSoccer::getInstance();
		$this->assertFalse($ws->isAjaxRequest());
		$ws->setAjaxRequest(true);
		$this->assertTrue($ws->isAjaxRequest());
		$ws->setAjaxRequest(false);
		$this->assertFalse($ws->isAjaxRequest());
	}

	public function testSetPageIdAndGetPageId(): void {
		$ws = \WebSoccer::getInstance();
		$this->assertNull($ws->getPageId());
		$ws->setPageId('mypage');
		$this->assertSame('mypage', $ws->getPageId());
	}

	public function testAddContextParameterAndGetContextParameters(): void {
		$ws = \WebSoccer::getInstance();
		$this->assertSame([], $ws->getContextParameters());
		$ws->addContextParameter('k', 'v');
		$this->assertSame(['k' => 'v'], $ws->getContextParameters());
	}

	public function testGetUserReturnsUserInstance(): void {
		$ws = \WebSoccer::getInstance();
		$user = $ws->getUser();
		$this->assertInstanceOf(\User::class, $user);
		$this->assertSame(ROLE_GUEST, $user->getRole());
	}

	public function testGetUserReturnsSameInstance(): void {
		$ws = \WebSoccer::getInstance();
		$this->assertSame($ws->getUser(), $ws->getUser());
	}
}
