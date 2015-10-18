<?php

namespace Famelo\Archi;

use Famelo\Archi\Core\FacadeInterface;

/**
 */
class ComposerFacade implements FacadeInterface {

	/**
	 * @var string
	 */
	protected $filepath;

	/**
	 * @var array
	 */
	protected $data;

	public function __construct($filepath) {
		$this->filepath = $filepath;
		if (file_exists($filepath)) {
			$this->data = json_decode(file_get_contents($filepath), TRUE);
		}
	}

	public function save() {
		file_put_contents('composer.json', json_encode($this->data, JSON_PRETTY_PRINT));
	}

	public function setNamespace($namespace, $path) {
		$this->data['autoload']['psr-4'] = array(
			$namespace => trim($path, '\\') . '\\'
		);
	}

	public function getNamespace() {
		return isset($this->data['autoload']['psr-4']) ? key($this->data['autoload']['psr-4']) : NULL;
	}
}

?>