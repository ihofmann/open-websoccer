<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for ModuleConfigHelper.
 */
final class ModuleConfigHelperTest extends TestCaseBase {
	public function testRemoveAliasFromDbTableNameReturnsUnaliasedName(): void {
		// "user u" -> "user"
		$this->assertSame('user', ModuleConfigHelper::removeAliasFromDbTableName('user u'));
	}

	public function testRemoveAliasFromDbTableNameReturnsNameWithoutAlias(): void {
		$this->assertSame('verein', ModuleConfigHelper::removeAliasFromDbTableName('verein'));
	}

	public function testRemoveAliasFromDbTableNameHandlesLongAlias(): void {
		$this->assertSame('table', ModuleConfigHelper::removeAliasFromDbTableName('table my_long_alias'));
	}

	public function testFindModuleConfigAsXmlObjectReturnsXmlElementForExistingModule(): void {
		// The 'core' module should always exist.
		$xml = ModuleConfigHelper::findModuleConfigAsXmlObject('core');
		$this->assertInstanceOf(\SimpleXMLElement::class, $xml);
	}

	public function testFindModuleConfigAsXmlObjectThrowsForNonExistentModule(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage("Config file for module 'nonexistent_module_xyz' not found.");
		ModuleConfigHelper::findModuleConfigAsXmlObject('nonexistent_module_xyz');
	}

	public function testFindDependentEntitiesReturnsArray(): void {
		$result = ModuleConfigHelper::findDependentEntities('user');
		$this->assertIsArray($result);
	}

	public function testFindDependentEntitiesReturnsEmptyArrayForNonExistentTable(): void {
		$result = ModuleConfigHelper::findDependentEntities('totally_nonexistent_table_xyz');
		$this->assertSame([], $result);
	}
}
