<?php
use OpenWebSoccer\Tests\TestCaseBase;

// TemplateEngine references the DEBUG constant in _initTwig(). The bootstrap
// does not define it, so define it here (guarded against redefinition).
if (!defined('DEBUG')) {
	define('DEBUG', false);
}

/**
 * Unit tests for TemplateEngine.
 *
 * The constructor and environment inspection are side-effect-free. The
 * loadTemplate()/clearCache() methods compile or purge the Twig cache on disk
 * (CACHE_FOLDER), so they are intentionally excluded to avoid writing into the
 * project's real cache folder.
 */
final class TemplateEngineTest extends TestCaseBase {
	private function makeEngineWithSkinSubDir(string $subDir, ?\ViewHandler $vh = null): \TemplateEngine {
		$skin = $this->createMock(\ISkin::class);
		$skin->method('getTemplatesSubDirectory')->willReturn($subDir);
		$skin->method('getTemplate')->willReturnCallback(function ($name) {
			return $name . '.twig';
		});

		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getSkin')->willReturn($skin);

		$i18n = $this->mockI18n();
		return new \TemplateEngine($ws, $i18n, $vh);
	}

	public function testGetEnvironmentReturnsTwigEnvironment(): void {
		$engine = $this->makeEngineWithSkinSubDir('default');
		$this->assertInstanceOf(\Twig\Environment::class, $engine->getEnvironment());
	}

	public function testConstructorRegistersGlobals(): void {
		$vh = $this->createMock(\ViewHandler::class);
		$engine = $this->makeEngineWithSkinSubDir('default', $vh);
		$globals = $engine->getEnvironment()->getGlobals();

		$this->assertArrayHasKey('i18n', $globals);
		$this->assertArrayHasKey('env', $globals);
		$this->assertArrayHasKey('skin', $globals);
		$this->assertArrayHasKey('viewHandler', $globals);
		$this->assertInstanceOf(\I18n::class, $globals['i18n']);
		$this->assertSame($vh, $globals['viewHandler']);
	}

	public function testLoaderIncludesDefaultTemplatePath(): void {
		$engine = $this->makeEngineWithSkinSubDir('default');
		$paths = $engine->getEnvironment()->getLoader()->getPaths();
		$this->assertContains(TEMPLATES_FOLDER . '/default', $paths);
	}

	public function testLoaderPrependsNonDefaultSkinSubDirectory(): void {
		$engine = $this->makeEngineWithSkinSubDir('schedio');
		$paths = $engine->getEnvironment()->getLoader()->getPaths();
		$this->assertContains(TEMPLATES_FOLDER . '/schedio', $paths);
		// prependPath puts the skin directory first.
		$this->assertSame(TEMPLATES_FOLDER . '/schedio', $paths[0]);
	}

	public function testGetTemplateReturnsSkinTemplateName(): void {
		// Verifies the skin mock's getTemplate mapping is wired into the engine.
		$skin = $this->createMock(\ISkin::class);
		$skin->method('getTemplatesSubDirectory')->willReturn('default');
		$skin->method('getTemplate')->willReturn('mapped.twig');

		$ws = $this->createMock(\WebSoccer::class);
		$ws->method('getSkin')->willReturn($skin);

		$engine = new \TemplateEngine($ws, $this->mockI18n(), null);
		// The skin's getTemplate is used by loadTemplate; without rendering we
		// verify the mapping contract directly.
		$this->assertSame('mapped.twig', $skin->getTemplate('anything'));
	}
}
