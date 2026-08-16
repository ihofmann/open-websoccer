<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for TransferOffersSentModel.
 */
final class TransferOffersSentModelTest extends TestCaseBase {
	private function dbMock(int $count = 0, array $offers = []): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function ($columns, $fromTable, $where = null, $params = null, $limit = null) use ($count, $offers) {
			if (is_string($columns) && stripos($columns, 'COUNT') !== false) {
				return $this->dbResult([['hits' => $count]]);
			}
			return $this->dbResult($offers);
		});
		return $db;
	}

	private function ws(array $config, \User $user): \WebSoccer {
		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getConfig')->willReturnCallback(function ($name) use ($config) {
			if (!array_key_exists($name, $config)) throw new \Exception('Missing configuration: ' . $name);
			return $config[$name];
		});
		$ws->method('getRequestParameter')->willReturn(null);
		$ws->method('getNowAsTimestamp')->willReturn(time());
		$ws->method('getUser')->willReturn($user);
		return $ws;
	}

	public function testRenderViewReturnsTrueWhenEnabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->ws(['transferoffers_enabled' => 1, 'db_prefix' => 'ws', 'entries_per_page' => 10], $user);
		$model = new TransferOffersSentModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testRenderViewReturnsFalseWhenDisabled(): void {
		$user = $this->makeUser(['id' => 1]);
		$ws = $this->ws(['transferoffers_enabled' => 0, 'db_prefix' => 'ws', 'entries_per_page' => 10], $user);
		$model = new TransferOffersSentModel($this->mockDb(), $this->mockI18n(), $ws);
		$this->assertFalse($model->renderView());
	}

	public function testGetTemplateParametersReturnsEmptyOffersWhenCountZero(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$ws = $this->ws(['transferoffers_enabled' => 1, 'db_prefix' => 'ws', 'entries_per_page' => 10], $user);
		$model = new TransferOffersSentModel($this->dbMock(0), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertArrayHasKey('offers', $params);
		$this->assertSame([], $params['offers']);
		$this->assertInstanceOf(\Paginator::class, $params['paginator']);
	}

	public function testGetTemplateParametersReturnsOffersWhenCountGreaterThanZero(): void {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId(10);
		$offer = ['offer_id' => 1, 'player_id' => 5, 'player_firstname' => 'John',
			'player_lastname' => 'Doe', 'player_marketvalue' => 1000];
		$ws = $this->ws([
			'transferoffers_enabled' => 1, 'db_prefix' => 'ws', 'entries_per_page' => 10,
			'transfermarket_computed_marketvalue' => 0,
		], $user);
		$model = new TransferOffersSentModel($this->dbMock(1, [$offer]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertCount(1, $params['offers']);
		$this->assertSame(1000, $params['offers'][0]['player_marketvalue']);
	}
}
