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

	public function parse($code, $type = 'file') {
		switch ($type) {
			case 'method':
					$code = '<?php class foo {' . $code . '}';
				break;
		}

		$statements = $this->parser->parse($code);

		switch ($type) {
			case 'method':
					$statements = $statements[0]->stmts;
				break;
		}
		return $statements;
	}

}

?>