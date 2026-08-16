<?php
use OpenWebSoccer\Tests\TestCaseBase;
final class TrainingModelTest extends TestCaseBase {
	private function ws(): \WebSoccer { $ws=$this->mockWebsoccer(['db_prefix'=>'ws','entries_per_page'=>10]); $user=$this->makeUser(['id'=>1]); $user->setClubId(1); $ws->method('getUser')->willReturn($user); $ws->method('getContextParameters')->willReturn([]); return $ws; }
	public function testRenderViewIsTrue(): void { $this->assertTrue((new TrainingModel($this->mockDb(),$this->mockI18n(),$this->ws()))->renderView()); }
	public function testReturnsTrainingStructure(): void { $db=$this->createMock(\DbConnection::class); $db->method('querySelect')->willReturnOnConsecutiveCalls($this->dbResult([]),$this->dbResult([['hits'=>0]]),$this->dbResult([]),$this->dbResult([['hits'=>0]])); $p=(new TrainingModel($db,$this->mockI18n(),$this->ws()))->getTemplateParameters(); $this->assertArrayHasKey('unitsCount',$p); $this->assertArrayHasKey('training_unit',$p); $this->assertArrayHasKey('trainingEffects',$p); }
}
