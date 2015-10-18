<?php
namespace Famelo\Archi\Tests\Functional\Typo3;

use Famelo\Archi\Typo3\PluginFacade;
use org\bovigo\vfs\vfsStream;

/**
 * Class ClassBuilderTest
 */
class PluginFacadeTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return void
	 */
	public static function setUpBeforeClass() {
		vfsStream::setup('root');
	}

	public function mockPlugin() {
		file_put_contents(vfsStream::url('root/ext_localconf.php'), '
			<?php
			if (!defined(\'TYPO3_MODE\')) {
				die(\'Access denied.\');
			}

			\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
				\'SomeCompany.\' . $_EXTKEY,
				\'SomePlugin\',
				array (
					\'SomeController\' => \'foo,bar\'
				),
				// non-cacheable actions
				array (
					\'SomeOtherController\' => \'foo,bar\'
				)
			);
		');

		file_put_contents(vfsStream::url('root/ext_tables.php'), '
			<?php
			if (!defined(\'TYPO3_MODE\')) {
				die(\'Access denied.\');
			}

			\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
				$_EXTKEY,
				\'SomePlugin\',
				\'SomeTitle\'
			);
		');
	}

	/**
	 * @test
	 */
	public function readPlugin() {
		$path = vfsStream::url('root');
		$this->mockPlugin();

		$facade = new PluginFacade('SomePlugin', $path);
		$this->assertEquals('SomePlugin', $facade->name);
		$this->assertEquals('SomeCompany', $facade->company);
		$this->assertEquals('SomeTitle', $facade->title);
		$this->assertEquals(array(
			'SomeController' => 'foo,bar'
		), $facade->cachedControllers);
		$this->assertEquals(array(
			'SomeOtherController' => 'foo,bar'
		), $facade->uncachedControllers);
	}

	/**
	 * @test
	 */
	public function addPlugin() {
		$path = vfsStream::url('root');

		$facade = new PluginFacade(NULL, $path);
		$facade->name = 'SomePlugin';
		$facade->company = 'SomeCompany';
		$facade->title = 'SomeTitle';
		$facade->cachedControllers = array(
			'SomeController' => 'foo,bar'
		);
		$facade->uncachedControllers = array(
			'SomeOtherController' => 'foo,bar'
		);
		$facade->save();

		$generatedFacade = new PluginFacade('SomePlugin', $path);
		$this->assertEquals('SomePlugin', $generatedFacade->name);
		$this->assertEquals('SomeCompany', $generatedFacade->company);
		$this->assertEquals('SomeTitle', $generatedFacade->title);
		$this->assertEquals(array(
			'SomeController' => 'foo,bar'
		), $generatedFacade->cachedControllers);
		$this->assertEquals(array(
			'SomeOtherController' => 'foo,bar'
		), $generatedFacade->uncachedControllers);
	}

	/**
	 * @test
	 */
	public function removePlugin() {
		$path = vfsStream::url('root');
		$this->mockPlugin();

		$facade = new PluginFacade('SomePlugin', $path);
		$facade->remove();

		$this->assertNotContains('SomePlugin', file_get_contents(vfsStream::url('root/ext_localconf.php')));
		$this->assertNotContains('SomePlugin', file_get_contents(vfsStream::url('root/ext_tables.php')));
	}



	/**
	 * @test
	 */
	public function changePlugin() {
		$path = vfsStream::url('root');
		$this->mockPlugin();

		$facade = new PluginFacade('SomePlugin', $path);
		$facade->name = 'NewPlugin';
		$facade->company = 'NewCompany';
		$facade->title = 'NewTitle';
		$facade->cachedControllers = array(
			'NewController' => 'foo,bar'
		);
		$facade->uncachedControllers = array(
			'NewOtherController' => 'foo,bar'
		);
		$facade->save();

		$generatedFacade = new PluginFacade('NewPlugin', $path);
		$this->assertEquals('NewPlugin', $generatedFacade->name);
		$this->assertEquals('NewCompany', $generatedFacade->company);
		$this->assertEquals('NewTitle', $generatedFacade->title);
		$this->assertEquals(array(
			'NewController' => 'foo,bar'
		), $generatedFacade->cachedControllers);
		$this->assertEquals(array(
			'NewOtherController' => 'foo,bar'
		), $generatedFacade->uncachedControllers);

		$this->assertNotContains('SomePlugin', file_get_contents(vfsStream::url('root/ext_localconf.php')));
		$this->assertNotContains('SomePlugin', file_get_contents(vfsStream::url('root/ext_tables.php')));
	}
}