<?php
use OpenWebSoccer\Tests\TestCaseBase;

/**
 * Unit tests for DefaultBootstrapSkin.
 */
final class DefaultBootstrapSkinTest extends TestCaseBase {
	public function testGetTemplatesSubDirectoryReturnsDefault(): void {
		$skin = new DefaultBootstrapSkin($this->mockWebsoccer(['context_root' => '/ws']));
		$this->assertSame('default', $skin->getTemplatesSubDirectory());
	}

	public function testGetCssSourcesReturnsArrayWithContextRoot(): void {
		$skin = new DefaultBootstrapSkin($this->mockWebsoccer(['context_root' => '/ws']));
		$css = $skin->getCssSources();
		$this->assertIsArray($css);
		$this->assertContains('/ws/assets/default.css', $css);
	}

	public function testGetJavaScriptSourcesReturnsArrayWithContextRoot(): void {
		$skin = new DefaultBootstrapSkin($this->mockWebsoccer(['context_root' => '/ws']));
		$js = $skin->getJavaScriptSources();
		$this->assertIsArray($js);
		$this->assertContains('/ws/assets/default.js', $js);
	}

	public function testGetTemplateAppendsTwigExtension(): void {
		$skin = new DefaultBootstrapSkin($this->mockWebsoccer(['context_root' => '/ws']));
		$this->assertSame('mytemplate.twig', $skin->getTemplate('mytemplate'));
	}

	public function testGetImageReturnsPathWhenFileExists(): void {
		$fileName = 'test_img_' . uniqid() . '.txt';
		$imgPath = BASE_FOLDER . '/img/' . $fileName;
		@mkdir(BASE_FOLDER . '/img', 0777, true);
		file_put_contents($imgPath, 'test');
		try {
			$skin = new DefaultBootstrapSkin($this->mockWebsoccer(['context_root' => '/ws']));
			$this->assertSame('/ws/img/' . $fileName, $skin->getImage($fileName));
		} finally {
			if (file_exists($imgPath)) {
				unlink($imgPath);
			}
		}
	}

	public function testGetImageReturnsFalseWhenFileDoesNotExist(): void {
		$skin = new DefaultBootstrapSkin($this->mockWebsoccer(['context_root' => '/ws']));
		$this->assertFalse($skin->getImage('nonexistent_file_xyz.png'));
	}

	public function testToStringReturnsSkinName(): void {
		$skin = new DefaultBootstrapSkin($this->mockWebsoccer(['context_root' => '/ws']));
		$this->assertSame('DefaultBootstrapSkin', (string) $skin);
	}

	public function testImplementsISkin(): void {
		$this->assertTrue(
			is_subclass_of(DefaultBootstrapSkin::class, ISkin::class)
		);
	}
}
