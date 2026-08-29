<?php
use OpenWebSoccer\Tests\TestCaseBase;
use OpenWebSoccer\Tests\MockDbResult;

/**
 * Unit tests for ProfileModel.
 */
final class ProfileModelTest extends TestCaseBase {
	private function dbWithRows(array $rows): \DbConnection {
		$db = $this->createMock(\DbConnection::class);
		$db->method('queryCachedSelect')->willReturn([]);
		$db->method('querySelect')->willReturnCallback(function($columns, $fromTable, $where = null, $params = null, $limit = null) use ($rows) {
			return $this->dbResult($rows);
		});
		return $db;
	}

	public function testRenderViewReturnsTrue(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'date_format' => 'Y-m-d']);
		$model = new ProfileModel($this->dbWithRows([]), $this->mockI18n(), $ws);
		$this->assertTrue($model->renderView());
	}

	public function testGetTemplateParametersClearsBirthdayWhenZeroDate(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'date_format' => 'Y-m-d']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$row = ['realname' => 'John', 'place' => 'NY', 'country' => 'US', 'birthday' => '0000-00-00',
			'occupation' => '', 'interests' => '', 'favorite_club' => '', 'homepage' => '', 'c_hideinonlinelist' => '0'];
		$model = new ProfileModel($this->dbWithRows([$row]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame('John', $params['user']['realname']);
		$this->assertSame('', $params['user']['birthday']);
	}

	public function testGetTemplateParametersClearsBirthdayWhenNull(): void {
		$ws = $this->mockWebsoccer(['db_prefix' => 'ws', 'date_format' => 'Y-m-d']);
		$ws->method('getUser')->willReturn($this->makeUser(['id' => 1]));
		$row = ['realname' => 'John', 'place' => 'NY', 'country' => 'US', 'birthday' => null,
			'occupation' => '', 'interests' => '', 'favorite_club' => '', 'homepage' => '', 'c_hideinonlinelist' => '0'];
		$model = new ProfileModel($this->dbWithRows([$row]), $this->mockI18n(), $ws);
		$params = $model->getTemplateParameters();
		$this->assertSame('', $params['user']['birthday']);
	}
}
