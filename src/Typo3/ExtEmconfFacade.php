<?php

namespace Famelo\Archi\Typo3;

use Famelo\Archi\Core\FacadeInterface;
use Famelo\Archi\Utility\Path;
use Famelo\Archi\Utility\String;


/**
 */
class ExtEmconfFacade implements FacadeInterface {

	const TEMPLATE_FILE = '
<?php

	/***************************************************************
	 * Extension Manager/Repository config file for ext: "%s"
	 *
	 * Auto generated by famelo/soup %s
	 *
	 * Manual updates:
	 * Only the data in the array - anything else is removed by next write.
	 * "version" and "dependencies" must not be touched!
	 ***************************************************************/

	$EM_CONF[$_EXTKEY] = %s;
	';

	/**
	 * @var string
	 */
	protected $filepath;

	/**
	 * @var string
	 */
	protected $data = array(
		'constraints' => array(
			'depends' => array()
		)
	);

	public function __construct($basePath = NULL) {
		$this->filepath = Path::joinPaths($basePath, 'ext_emconf.php');

		if (file_exists($this->filepath)) {
			$_EXTKEY = 'foo';
			$EM_CONF = array();
			require($this->filepath);
			$this->data = $EM_CONF[$_EXTKEY];
		}
	}

	public function addDependency($extension, $version = '') {
		$this->data['constraints']['depends'][$extension] = $version;
	}

	public function removeDependency($extension) {
		unset($this->data['constraints']['depends'][$extension]);
	}

	/**
	 */
	public function save() {
		$output = sprintf(self::TEMPLATE_FILE, basename(WORKING_DIRECTORY), date('Y-m-d'), var_export($this->data, TRUE) );
		file_put_contents($this->filepath, trim($output));
	}
}

?>