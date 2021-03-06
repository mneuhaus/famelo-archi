<?php

namespace Famelo\Archi\Typo3;

use Famelo\Archi\Core\FacadeInterface;
use Famelo\Archi\Utility\Path;


/**
 */
class ExtLocalconfFacade implements FacadeInterface {

	const PATTERN_PLUGIN_CONFIGURE = '/.*ExtensionUtility::configurePlugin\(([^;]*);/';

	const TEMPLATE_FILE = '
<?php
if (!defined(\'TYPO3_MODE\')) {
	die(\'Access denied.\');
}
	';

	/**
	 * @var string
	 */
	protected $filepath;

	/**
	 * @var string
	 */
	protected $filecontent;

	public function __construct($basePath = NULL) {
		$this->filepath = Path::joinPaths($basePath, 'ext_localconf.php');

		if (file_exists($this->filepath) === TRUE) {
			$this->filecontent = file_get_contents($this->filepath);
		}

		if (empty($this->filecontent)) {
			$this->filecontent = trim(self::TEMPLATE_FILE, chr(10));
		}
	}

	public function getPlugins($value='') {
		preg_match_all(ExtLocalconfFacade::PATTERN_PLUGIN_CONFIGURE, $this->filecontent, $matches);
		$plugins = array();
		foreach ($matches[1] as $key => $match) {
			$code = '
				$_EXTKEY = "";
				return array(' . trim($match, '()') . ');
			';
			$data = eval($code);
			$plugins[] = array(
				'company' => trim($data[0], '.'),
				'name' => $data[1],
				'cachedControllers' => $data[2],
				'uncachedControllers' => $data[3],
				'code' => $matches[0][$key]
			);
		}
		return $plugins;
	}

	public function getPlugin($name) {
		foreach ($this->getPlugins() as $plugin) {
			if ($plugin['name'] === $name) {
				return $plugin;
			}
		}
	}

	public function updateCode($oldCode, $newCode) {
		$this->filecontent = str_replace($oldCode, $newCode, $this->filecontent);
	}

	public function addCode($code) {
		$this->filecontent.= chr(10) . chr(10) . trim($code, chr(10)) . chr(10);
	}

	public function removeCode($code) {
		$this->filecontent = str_replace($code, '', $this->filecontent);
	}

	/**
	 */
	public function save() {
		file_put_contents($this->filepath, $this->filecontent);
	}
}

?>