<?php

namespace Famelo\Archi\Typo3;

use Famelo\Archi\Core\FacadeInterface;
use Famelo\Archi\Php\ClassFacade;
use Famelo\Archi\Utility\Path;
use Famelo\Archi\Utility\String;


/**
 */
class ControllerFacade extends ClassFacade {

	const TEMPLATE_CONTROLLER = '<?php
namespace Foo\Bar\Controller;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014 Marc Neuhaus <mneuhaus@famelo.com>, Famelo
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * FooController
 */
class FooController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

}
	';

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $namespace;

	/**
	 * @var string
	 */
	public $actions = array();

	public function __construct($filepath) {
		parent::__construct($filepath);
		if (!file_exists($filepath)) {
			return;
		}
		$this->name = $this->getName();
		$this->namespace = $this->getNamespace();

		foreach ($this->getMethods() as $method) {
			$this->actions[] = String::cutSuffix($method->getName(), 'Action');
		}
	}

	public function hasAction($name) {
		return in_array(String::cutSuffix($name, 'Action'), $this->actions);
	}

	public function renameAction($oldName, $newName) {
		$this->renameMethod(
			String::addSuffix($oldName, 'Action'),
			String::addSuffix($newName, 'Action')
		);
	}

	public function addAction($name) {
		$this->addMethod(String::addSuffix($name, 'Action'));
	}

	public function removeAction($name) {
		$this->removeMethod(String::addSuffix($name, 'Action'));
	}

	/**
	 */
	public function save($targetPath = 'Classes/Controller/') {
		$className = ucfirst(String::addSuffix($this->name, 'Controller'));
		$targetFileName = $targetPath . $className . '.php';
		if ($targetFileName !== $this->filepath) {
			unlink($this->filepath);
		}
		// $composer = new ComposerFacade('composer.json');
		// $namespace = $composer->getNamespace() . '\\Controller';
		parent::setNamespace($this->namespace);
		parent::setClassName($className);
		parent::save($targetFileName);
	}

	public function remove() {
		unlink($this->filepath);
	}
}

?>