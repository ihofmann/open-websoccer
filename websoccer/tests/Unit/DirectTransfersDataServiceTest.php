<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for DirectTransfersDataService.
 */
if (!defined('NOTIFICATION_TYPE')) {
	define('NOTIFICATION_TYPE', 'transferoffer');
}
if (!defined('NOTIFICATION_TARGETPAGE')) {
	define('NOTIFICATION_TARGETPAGE', 'transferoffers');
}

final class DirectTransfersDataServiceTest extends TestCaseBase {
	private function makeWebsoccer(array $config = []): \WebSoccer&\PHPUnit\Framework\MockObject\MockObject {
		return $this->mockWebsoccer(array_merge([
			'db_prefix' => 'ws',
			'context_root' => '/ws',
			'gravatar_enable' => '0',
			'transfermarket_computed_marketvalue' => '0',
		], $config));
	}

	private function dbSelect(MockDbResult $result): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($result);
		return $db;
	}

	public function testCountReceivedOffersReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '3']]));
		$this->assertSame('3', DirectTransfersDataService::countReceivedOffers($ws, $db, 5));
	}

	public function testCountReceivedOffersReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, DirectTransfersDataService::countReceivedOffers($ws, $db, 5));
	}

	public function testCountSentOffersReturnsCount(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([['hits' => '2']]));
		$this->assertSame('2', DirectTransfersDataService::countSentOffers($ws, $db, 5, 7));
	}

	public function testCountSentOffersReturnsZeroWhenNoRow(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame(0, DirectTransfersDataService::countSentOffers($ws, $db, 5, 7));
	}

	public function testGetReceivedOffersReturnsOffersWithComputedMarketValue(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([
			['offer_id' => '1', 'offer_submitted_date' => '100', 'offer_amount' => '5000', 'offer_message' => 'hi',
			 'offer_rejected_date' => '0', 'offer_rejected_message' => '', 'offer_rejected_allow_alternative' => '0',
			 'offer_admin_approval_pending' => '0', 'player_id' => '10', 'player_firstname' => 'Joe', 'player_lastname' => 'X',
			 'player_pseudonym' => '', 'player_salary' => '1000', 'player_marketvalue' => '9000', 'player_strength' => '80',
			 'player_strength_technique' => '70', 'player_strength_stamina' => '60', 'player_strength_freshness' => '50',
			 'player_strength_satisfaction' => '40', 'player_position_main' => 'T', 'sender_user_id' => '7',
			 'sender_user_name' => 'amy', 'sender_club_id' => '8', 'sender_club_name' => 'Club A', 'receiver_user_id' => '9',
			 'receiver_user_name' => 'bob', 'receiver_club_id' => '5', 'receiver_club_name' => 'Club B',
			 'explayer1_id' => null, 'explayer1_firstname' => null, 'explayer1_lastname' => null, 'explayer1_pseudonym' => null,
			 'explayer2_id' => null, 'explayer2_firstname' => null, 'explayer2_lastname' => null, 'explayer2_pseudonym' => null],
		]));
		$offers = DirectTransfersDataService::getReceivedOffers($ws, $db, 0, 10, 5);
		$this->assertCount(1, $offers);
		$this->assertSame('5000', $offers[0]['offer_amount']);
		// computed market value disabled -> returns DB marketvalue.
		$this->assertSame('9000', $offers[0]['player_marketvalue']);
	}

	public function testGetSentOffersReturnsEmptyWhenNone(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->dbSelect($this->dbResult([]));
		$this->assertSame([], DirectTransfersDataService::getSentOffers($ws, $db, 0, 10, 5, 7));
	}

	public function testCreateTransferOfferInsertsOfferAndNotification(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		// createTransferOffer: queryInsert(offer), then getUserById(querySelect), then createNotification(queryInsert).
		$db->method('querySelect')->willReturn($this->dbResult([
			['nick' => 'amy', 'picture' => 'pic.jpg', 'email' => 'a@a.com'],
		]));
		// one insert for offer, one insert for notification.
		$db->expects($this->exactly(2))->method('queryInsert');

		DirectTransfersDataService::createTransferOffer($ws, $db, 10, 7, 8, 9, 5, 5000, 'hi');
	}

	public function testExecuteTransferFromOfferDoesNothingWhenOfferNotFound(): void {
		$ws = $this->makeWebsoccer();
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturn($this->dbResult([]));
		$db->expects($this->never())->method('queryUpdate');
		$db->expects($this->never())->method('queryInsert');
		$db->expects($this->never())->method('queryDelete');

		DirectTransfersDataService::executeTransferFromOffer($ws, $db, 999);
	}
}
