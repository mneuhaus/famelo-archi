<?php
namespace Famelo\Archi\Tests\Functional\Php;

use Famelo\Archi\Php\ClassFacade;
use org\bovigo\vfs\vfsStream;

/**
 * Class ClassBuilderTest
 */
class ClassBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return void
	 */
	public static function setUpBeforeClass() {
		vfsStream::setup('src/');
	}

	/**
	 * @test
	 */
	public function builderCreatesANewClass() {
		$filepath = vfsStream::url('src/Foo.php');

		$builder = new ClassFacade($filepath);
		$builder->setNamespace('Hello\World');
		$builder->setClassName('Foo');
		$builder->addMethod('test');
		$builder->save();

		$generatedCode = file_get_contents($filepath);
		$this->assertContains('namespace Hello\World;', $generatedCode);
		$this->assertContains('class Foo {', $generatedCode);
		$this->assertContains('public function test() {', $generatedCode);
	}

}