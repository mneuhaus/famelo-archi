<?php

namespace Famelo\Archi\Php;

use Famelo\Archi\Core\BuilderInterface;
use Famelo\Archi\Php\Printer\TYPO3Printer;
use Famelo\Archi\Php\Reflection\ReflectionMethod;
use Famelo\Archi\Php\Reflection\ReflectionProperty;
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

	public function setNamespace($namespace) {
		$namespaceStatement = $this->getNamespaceStatement();
		if ($namespaceStatement === NULL) {
			$this->statements[] = $this->factory->namespace($namespace)->getNode();
		} else {
			$namespaceStatement->name = new \PhpParser\Node\Name(explode('\\', $namespace));
		}
	}

	public function addMethod($name, $template = '
		/**
		 * @return void
		 */
		public function foo(){}') {
		$methodStatement = current($this->parse($template, 'method'));
		$methodStatement->name = $name;
		$this->getClassStatement()->stmts[] = $methodStatement;
	}

	public function removeMethod($name) {
		$classStatement = $this->getClassStatement();
		foreach ($classStatement->stmts as $key => $childStatement) {
			if ($childStatement instanceof \PhpParser\Node\Stmt\ClassMethod) {
				if ($childStatement->name == $name) {
					unset($classStatement->stmts[$key]);
					break;
				}
			}
		}
	}

	public function renameMethod($oldName, $newName) {
		$classStatement = $this->getClassStatement();
		foreach ($classStatement->stmts as $key => $childStatement) {
			if ($childStatement instanceof \PhpParser\Node\Stmt\ClassMethod) {
				if ($childStatement->name == $oldName) {
					$childStatement->name = $newName;
					break;
				}
			}
		}
	}

	public function setClassName($className) {
		$classStatement = $this->getClassStatement();
		if ($classStatement === NULL) {
			$classStatement = $this->factory->class($className)->getNode();
			$this->getNamespaceStatement()->stmts[] = $classStatement;
		} else {
			$classStatement->name = $className;
		}
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
		$methods = array();
		foreach ($statements as $statement) {
			$methods[] = new ReflectionMethod($statement, $this->getClassName());
		}
		return $methods;
	}

	public function getPropertyStatements() {
		$classStatement = $this->getClassStatement();
		$propertyStatements = array();
		foreach ($classStatement->stmts as $childStatement) {
			if ($childStatement instanceof \PhpParser\Node\Stmt\Property) {
				$propertyStatements[] = $childStatement;
			}
		}
		return $propertyStatements;
	}

	public function getProperties() {
		$statements = $this->getPropertyStatements();
		$methods = array();
		foreach ($statements as $statement) {
			$methods[] = new ReflectionProperty($statement, $this->getClassName());
		}
		return $methods;
	}

	public function getClassStatement() {
		$namespaceStatement = $this->getNamespaceStatement();
		foreach ($namespaceStatement->stmts as $classStatement) {
			if ($classStatement instanceof \PhpParser\Node\Stmt\Class_) {
				return $classStatement;
			}
		}
	}

	public function getClassName() {
		return $this->getNamespace() . '\\' . $this->getName();
	}

	public function getName() {
		return $this->getClassStatement()->name;
	}

	public function getNamespace() {
		return $this->getNamespaceStatement()->name->toString();
	}

	public function getNamespaceStatement() {
		if (empty($this->statements)) {
			return;
		}
		if ($this->statements[0] instanceof \PhpParser\Node\Stmt\Namespace_) {
			return $this->statements[0];
		}
	}

	public function addProperty($name, $template = NULL, $codeReplacements = array()) {
		if ($template === NULL) {
			$template = '
				/**
				 * @var propertyType
				 */
				protected $foo;

				public function getFoo() {
					return $this->foo;
				}

				public function setFoo($foo) {
					$this->foo = $foo;
				}
			';

			$codeReplacements = array_replace(array(
				'foo' => lcfirst($name),
				'Foo' => ucfirst($name)
			), $codeReplacements);
		}

		$template = str_replace(array_keys($codeReplacements), $codeReplacements, $template);

		foreach ($this->parse($template, 'property') as $statement) {
			$this->getClassStatement()->stmts[] = $statement;
		}
	}

	public function removeProperty($name) {
		$relatedMethods = array(
			'get' . ucfirst($name),
			'set' . ucfirst($name)
		);
		$classStatement = $this->getClassStatement();
		foreach ($classStatement->stmts as $key => $childStatement) {
			if ($childStatement instanceof \PhpParser\Node\Stmt\Property) {
				if ($childStatement->props[0]->name == $name) {
					unset($classStatement->stmts[$key]);
				}
			}
			if ($childStatement instanceof \PhpParser\Node\Stmt\ClassMethod) {
				if (in_array($childStatement->name, $relatedMethods)) {
					unset($classStatement->stmts[$key]);
				}
			}
		}
	}

	public function renameProperty($oldName, $newName) {
		$relatedMethods = array(
			'get' . ucfirst($oldName),
			'set' . ucfirst($oldName)
		);
		$classStatement = $this->getClassStatement();
		foreach ($classStatement->stmts as $key => $childStatement) {
			if ($childStatement instanceof \PhpParser\Node\Stmt\Property) {
				if ($childStatement->props[0]->name == $oldName) {
					$childStatement->props[0]->name = $newName;
				}
			}
			if ($childStatement instanceof \PhpParser\Node\Stmt\ClassMethod) {
				if (in_array($childStatement->name, $relatedMethods)) {
					$classStatement->stmts[$key] = $this->replaceStrings($childStatement, array(
						$oldName => $newName,
						ucfirst($oldName) => ucfirst($newName)
					), 'method');
				}
			}
		}
	}
}

?>