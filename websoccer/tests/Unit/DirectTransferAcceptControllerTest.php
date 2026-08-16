<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DirectTransferAcceptController.
 */
final class DirectTransferAcceptControllerTest extends TestCaseBase {
	private function makeDb(array $selectRowsByTable = [], array $cachedRowsByTable = []): \DbConnection&\PHPUnit\Framework\MockObject\MockObject {
		$db = $this->createMock(\DbConnection::class);
		$db->method('querySelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($selectRowsByTable) {
				foreach ($selectRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $this->dbResult($rows);
					}
				}
				return $this->dbResult([]);
			}
		);
		$db->method('queryCachedSelect')->willReturnCallback(
			function ($columns, $fromTable, $whereCondition, $parameters = null, $limit = null) use ($cachedRowsByTable) {
				foreach ($cachedRowsByTable as $needle => $rows) {
					if (strpos($fromTable, $needle) !== false) {
						return $rows;
					}
				}
				return [];
			}
		);
		return $db;
	}

	private function makeUserWithClub(int $clubId): \User {
		$user = $this->makeUser(['id' => 1]);
		$user->setClubId($clubId);
		return $user;
	}

	private function baseConfig(): array {
		return ['transferoffers_enabled' => true, 'db_prefix' => 'ws',
			'transfermarket_min_teamsize' => 11, 'transferoffers_adminapproval_required' => true,
			'players_aging' => 'age', 'transfermarket_computed_marketvalue' => false];
	}

	private function playerRow(array $override = []): array {
		return array_merge([
			'player_id' => 9, 'team_id' => 2, 'team_name' => 'Other', 'team_user_id' => 2,
			'player_transfermarket' => 0, 'player_position' => 'Torwart',
			'player_nationality' => 'Deutschland', 'matches_info' => '0;0', 'player_marketvalue' => 1000,
		], $override);
	}

	public function testExecuteActionReturnsNullWhenFeatureDisabled(): void {
		$ws = $this->mockWebsoccer(['transferoffers_enabled' => false, 'db_prefix' => 'ws']);
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferAcceptController($this->mockI18n(), $ws, $this->mockDb());
		$this->assertNull($controller->executeAction(['id' => 5]));
	}

	public function testExecuteActionThrowsWhenPlayerIsOnTransfermarket(): void {
		$db = $this->makeDb(
			['_transfer_offer' => [['id' => 5, 'player_id' => 9, 'offer_player1' => 0, 'offer_player2' => 0, 'sender_user_id' => 2]]],
			['_spieler AS P' => [$this->playerRow(['player_transfermarket' => 1])]]
		);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferAcceptController(
			$this->mockI18n(['transferoffer_err_unsellable' => 'unsellable']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('unsellable');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionThrowsWhenTeamSizeTooSmall(): void {
		$db = $this->makeDb(
			[
				'_transfer_offer' => [['id' => 5, 'player_id' => 9, 'offer_player1' => 0, 'offer_player2' => 0, 'sender_user_id' => 2]],
				'_spieler' => [['number' => 5]],
			],
			['_spieler AS P' => [$this->playerRow()]]
		);
		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferAcceptController(
			$this->mockI18n(['sell_player_teamsize_too_small' => 'teamsize %s']), $ws, $db);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('teamsize 5');
		$controller->executeAction(['id' => 5]);
	}

	public function testExecuteActionMarksOfferPendingForAdminApprovalAndReturnsNull(): void {
		$db = $this->makeDb(
			[
				'_transfer_offer' => [['id' => 5, 'player_id' => 9, 'offer_player1' => 0, 'offer_player2' => 0, 'sender_user_id' => 2]],
				'_spieler' => [['number' => 20]],
			],
			['_spieler AS P' => [$this->playerRow()]]
		);
		$updated = null;
		$db->method('queryUpdate')->willReturnCallback(function ($columns, $fromTable, $whereCondition, $parameters) use (&$updated) {
			if (strpos($fromTable, '_transfer_offer') !== false) {
				$updated = $columns;
			}
			return null;
		});

		$ws = $this->mockWebsoccer($this->baseConfig());
		$ws->method('getUser')->willReturn($this->makeUserWithClub(1));

		$controller = new DirectTransferAcceptController($this->mockI18n([
			'transferoffer_accepted_title' => 'accepted',
			'transferoffer_accepted_message_approvalpending' => 'pending',
		]), $ws, $db);

		$this->assertNull($controller->executeAction(['id' => 5]));
		$this->assertSame('1', $updated['admin_approval_pending']);
	}
}
