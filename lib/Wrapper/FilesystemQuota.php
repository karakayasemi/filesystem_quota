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
use OCP\Files\Cache\ICacheEntry;

class FilesystemQuota extends Wrapper{

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
		$this->chownExecutablePath = \OC_App::getAppPath('filesystem_quota') . '/lib/chown';
		$this->umask = \OC::$server->getConfig()->getAppValue('filesystem_quota', 'umask', '007');
		$this->ownerGroup = \OC::$server->getConfig()->getAppValue('filesystem_quota', 'owner_gid', '1000');
	}

	/**
	 * @param string $path
	 * @param \OC\Files\Storage\Storage $storage
	 */
	protected function getSize($path, $storage = null) {
		if (is_null($storage)) {
			$cache = $this->getCache();
		} else {
			$cache = $storage->getCache();
		}
		$data = $cache->get($path);
		if ($data instanceof ICacheEntry and isset($data['size'])) {
			return $data['size'];
		} else {
			return \OCP\Files\FileInfo::SPACE_NOT_COMPUTED;
		}
	}


	public function free_space($path) {
		return $this->quotaService->freeSpace($this->storage->getOwner(''));
	}

	/** {@inheritdoc} */
	public function mkdir($path) {
		$source = $this->storage->mkdir($path);
		try {
			$this->chown(
				$this->storage->getLocalFile($path),
				$this->storage->getOwner('')
			);
		} catch (\Exception $e) {

		}
		return $source;
	}

	/**
	 * see http://php.net/manual/en/function.file_put_contents.php
	 *
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		$free = $this->free_space('');
		if (strlen($data) < $free) {
			return $this->storage->file_put_contents($path, $data);
		} else {
			return false;
		}
	}

	/**
	 * see http://php.net/manual/en/function.copy.php
	 *
	 * @param string $source
	 * @param string $target
	 * @return bool
	 */
	public function copy($source, $target) {
		$free = $this->free_space('');
		if ($this->getSize($source) < $free) {
			return $this->storage->copy($source, $target);
		} else {
			return false;
		}
	}


	/** {@inheritdoc} */
	public function fopen($path, $mode) {
		$source = $this->storage->fopen($path, $mode);
		try {
			$this->chown(
				$this->storage->getLocalFile($path),
				$this->storage->getOwner('')
			);
		} catch (\Exception $e) {

		}
		return $source;
	}

	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function copyFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$free = $this->free_space('');
		if ($this->getSize($sourceInternalPath, $sourceStorage) < $free) {
			return $this->storage->copyFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
	}

	/**
	 * @param \OCP\Files\Storage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function moveFromStorage(\OCP\Files\Storage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		$free = $this->free_space('');
		if ($this->getSize($sourceInternalPath, $sourceStorage) < $free) {
			return $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
		} else {
			return false;
		}
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