<?php
/**
 * @author Semih Serhat Karakaya <karakayasemi@itu.edu.tr>
 *
 * @copyright Copyright (c) 2017, ITU/BIDB
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Filesystem_Quota\Wrapper;

use OC\Files\Storage\Storage;
use OCA\Filesystem_Quota\QuotaService;
use OC\Files\Storage\Wrapper\Wrapper;

class FilesystemQuotaWrapper extends Wrapper{

	/**
	 * @var Storage $storage
	 */
	protected $storage;

	/**
	 * @var QuotaService $quotaService
	 */
	protected $quotaService;

	/**
	 * @var string $chownExecutablePath
	 */
	private $chownExecutablePath;

	/**
	 * @var string $umask
	 */
	private $umask;

	/**
	 * @var string $ownerGroup
	 */
	private $ownerGroup;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		$this->storage = $parameters['storage'];
		$this->quotaService = $parameters['quota_service'];
		$this->chownExecutablePath = \OC_App::getAppPath('filesystem_quota taslak') . '/lib/chown';
		$this->umask = \OC::$server->getConfig()->getAppValue('filesystem_quota taslak', 'umask', '007');
		$this->ownerGroup = \OC::$server->getConfig()->getAppValue('filesystem_quota taslak', 'group', '1000');
	}

	public function free_space($path) {
		return $this->quotaService->freeSpace($this->storage->getUser()->getUID());
	}

	/** {@inheritdoc} */
	public function mkdir($path) {
		$source = $this->storage->mkdir($path);
		try {
			$this->chown(
				$this->storage->getLocalFile($path),
				$this->storage->getUser()->getUID()
			);
		} catch (\Exception $e) {

		}
		return $source;
	}

	/** {@inheritdoc} */
	public function fopen($path, $mode) {
		$source = $this->storage->fopen($path, $mode);
		try {
			$this->chown(
				$this->storage->getLocalFile($path),
				$this->storage->getUser()->getUID()
			);
		} catch (\Exception $e) {

		}
		return $source;
	}

	/** {@inheritdoc} */
	public function touch($path, $mtime = null) {
		return false;
	}

	/**
	 * see http://php.net/manual/en/function.chown.php
	 *
	 * @param string $path
	 * @param string $user
	 * @return boolean
	 */
	public function chown($path, $user) {
		$chownScript = $this->chownExecutablePath . ' "' . $user . '" ' . $this->ownerGroup . " " . $this->umask . ' "' . $path . '" ';
		$output = array();
		exec($chownScript, $output, $returnValue);
		return $returnValue;
	}
}