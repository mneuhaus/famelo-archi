<?php

namespace Famelo\Archi\Php;

use Famelo\Archi\Core\BuilderInterface;
use Famelo\Archi\Php\Printer\TYPO3Printer;
use PhpParser\BuilderFactory;
use PhpParser\ParserFactory;

/**
 */
class ClassFacade extends AbstractFacade {

	/**
	 * @var string
	 */
	protected $filepath;

	/**
	 * @var object
	 */
	protected $parser;

	/**
	 * @var array
	 */
	protected $statements = array();

	public function __construct($filepath) {
		$this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
		$this->factory = new BuilderFactory;
		if (file_exists($filepath)) {
			$this->statements = $this->parser->parse(file_get_contents($filepath));
		}
		$this->filepath = $filepath;
	}

	public function save() {
		$prettyPrinter = new TYPO3Printer;

		try {
			$code = $prettyPrinter->prettyPrint($this->statements);
			file_put_contents($this->filepath, $code);
		} catch (Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}
	}

	public function setNamespace($namespace) {
		$namespaceStatement = $this->getNamespaceStatement();
		if ($namespaceStatement === NULL) {
			$this->statements[] = $this->factory->namespace($namespace)->getNode();
		} else {
			// $namespaceStatement->name = $namespace;
		}
	}

	public function addMethod($name, $template = 'public function foo(){}') {
		$methodStatement = current($this->parse($template, 'method'));
		$methodStatement->name = $name;
		$this->getClassStatement()->stmts[] = $methodStatement;
	}

	public function setClassName($className) {
		$classStatement = $this->getClassStatement();
		if ($classStatement === NULL) {
			$classStatement = $this->factory->class($className)->getNode();
			$this->getNamespaceStatement()->stmts[] = $classStatement;
		}
	}

	public function getPropertyStatements() {
		$classStatement = $this->getClassStatement();
		$propertyStatements = array();
		foreach ($classStatement->stmts as $childStatement) {
			if ($childStatement instanceof \PhpParser\Node\Stmt\Property) {
				$property = new ReflectionProperty($childStatement);
				$propertyStatements[$property->getName()] = $property;
			}
		}
		return $propertyStatements;
	}

	public function getMethodStatements() {
		$classStatement = $this->getClassStatement();
		$methodStatements = array();
		foreach ($classStatement->stmts as $childStatement) {
			if ($childStatement instanceof \PhpParser\Node\Stmt\ClassMethod) {
				$methodStatements[$childStatement->name] = $childStatement;
			}
		}
		return $methodStatements;
	}

	public function getMethods() {
		$statements = $this->getMethodStatements();
		var_dump($statements);
	}

	public function getClassStatement() {
		$namespaceStatement = $this->getNamespaceStatement();
		foreach ($namespaceStatement->stmts as $classStatement) {
			if ($classStatement instanceof \PhpParser\Node\Stmt\Class_) {
				return $classStatement;
			}
		}
	}

	public function getNamespaceStatement() {
		if (empty($this->statements)) {
			return;
		}
		if ($this->statements[0] instanceof \PhpParser\Node\Stmt\Namespace_) {
			return $this->statements[0];
		}
	}

}

?>