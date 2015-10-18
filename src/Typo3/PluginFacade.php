<?php

namespace Famelo\Archi\Typo3;

use Famelo\Archi\Core\FacadeInterface;
use Famelo\Archi\Typo3\ExtLocalconfFacade;
use Famelo\Archi\Typo3\ExtTablesFacade;
use Famelo\Archi\Utility\Path;
use Famelo\Archi\Utility\String;


/**
 */
class PluginFacade implements FacadeInterface {

	const TEMPLATE_CONFIGURE_PLUGIN = '
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
	\'--company--.\' . $_EXTKEY,
	\'--name--\',
	--cachedControllers--,
	// non-cacheable actions
	--uncachedControllers--
);
	';

	const TEMPLATE_REGISTER_PLUGIN = '
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
	$_EXTKEY,
	\'--name--\',
	\'--title--\'
);
	';

	/**
	 * @var string
	 */
	public $company;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var array
	 */
	public $cachedControllers = array();

	/**
	 * @var string
	 */
	public $uncachedControllers = array();

	/**
	 * @var string
	 */
	protected $configurationCode;

	/**
	 * @var string
	 */
	protected $registrationCode;

	/**
	 * @var string
	 */
	protected $oldName;

	/**
	 * @var string
	 */
	protected $basepath;

	public function __construct($name, $basepath = NULL) {
		$extLocalconfFacade = new ExtLocalconfFacade($basepath);
		$pluginConfiguration = $extLocalconfFacade->getPlugin($name);
		if ($pluginConfiguration !== NULL) {
			$this->company = $pluginConfiguration['company'];
			$this->name = $pluginConfiguration['name'];
			$this->oldName = $pluginConfiguration['name'];
			$this->cachedControllers = $pluginConfiguration['cachedControllers'];
			$this->uncachedControllers = $pluginConfiguration['uncachedControllers'];
			$this->configurationCode = $pluginConfiguration['code'];
		}

		$extTablesFacade = new ExtTablesFacade($basepath);
		if ($extTablesFacade !== NULL) {
			$pluginRegistration = $extTablesFacade->getPlugin($name);
			$this->title = $pluginRegistration['title'];
			$this->registrationCode = $pluginRegistration['code'];
		}

		$this->basepath = $basepath;
	}

	/**
	 */
	public function save() {
		$cachedControllers = array();
		foreach ($this->cachedControllers as $controllerName => $actions) {
			$cachedControllers[String::addSuffix($controllerName, 'Controller')] = $actions;
		}

		$uncachedControllers = array();
		foreach ($this->uncachedControllers as $controllerName => $actions) {
			$uncachedControllers[String::addSuffix($controllerName, 'Controller')] = $actions;
		}

		$arguments = array(
			'company' => $this->company,
			'name' => $this->name,
			'title' => $this->title,
			'cachedControllers' => trim(String::prefixLinesWith(var_export($cachedControllers, TRUE), "\t"), "\t"),
			'uncachedControllers' => trim(String::prefixLinesWith(var_export($uncachedControllers, TRUE), "\t"), "\t")
		);

		$extLocalconfFacade = new ExtLocalconfFacade($this->basepath);
		if ($this->oldName !== NULL) {
			$extLocalconfFacade->updateCode($this->configurationCode, $this->renderCode($arguments, self::TEMPLATE_CONFIGURE_PLUGIN));
		} else {
			$extLocalconfFacade->addCode($this->renderCode($arguments, self::TEMPLATE_CONFIGURE_PLUGIN));
		}
		$extLocalconfFacade->save();

		$extTablesFacade = new ExtTablesFacade($this->basepath);
		if ($this->oldName !== NULL) {
			$extTablesFacade->updateCode($this->registrationCode, $this->renderCode($arguments, self::TEMPLATE_REGISTER_PLUGIN));
		} else {
			$extTablesFacade->addCode($this->renderCode($arguments, self::TEMPLATE_REGISTER_PLUGIN));
		}
		$extTablesFacade->save();
	}

	public function renderCode($arguments, $template) {
		$code = trim($template, chr(10));
		foreach ($arguments as $key => $value) {
			$code = str_replace('--' . $key . '--', $value, $code);
		}
		return $code;
	}

	public function remove() {
		$extLocalconfFacade = new ExtLocalconfFacade($this->basepath);
		$extLocalconfFacade->removeCode($this->configurationCode);
		$extLocalconfFacade->save();

		$extTablesFacade = new ExtTablesFacade($this->basepath);
		$extTablesFacade->removeCode($this->registrationCode);
		$extTablesFacade->save();
	}
}

?>