<?php

namespace Famelo\Archi\Php;

use Famelo\Archi\Core\FacadeInterface;
use Famelo\Archi\Php\Printer\TYPO3Printer;
use PhpParser\BuilderFactory;
use PhpParser\ParserFactory;

/**
 */
abstract class AbstractFacade implements FacadeInterface {

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

	public function save($targetFileName = NULL) {
		$prettyPrinter = new TYPO3Printer;

		if (!file_exists(dirname($targetFileName))) {
			mkdir(dirname($targetFileName), 0775, TRUE);
		}

		try {
			$code = '<?php ' .  chr(10) . $prettyPrinter->prettyPrint($this->statements);
			file_put_contents($targetFileName, $code);
		} catch (Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}
	}

	public function parse($code, $type = 'file') {
		switch ($type) {
			case 'property':
			case 'method':
					$code = '<?php class foo {' . $code . '}';
				break;
		}

		$statements = $this->parser->parse($code);

		switch ($type) {
			case 'property':
			case 'method':
					$statements = $statements[0]->stmts;
				break;
		}
		return $statements;
	}

	public function replaceStrings($statement, $replacements, $type="file") {
		$prettyPrinter = new TYPO3Printer;
		$code = $prettyPrinter->prettyPrint(array($statement));
		$code = str_replace(array_keys($replacements), $replacements, $code);
		return current($this->parse($code, $type));
	}
}

?>