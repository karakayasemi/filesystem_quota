<?php
/**
 *
 * @author Semih Serhat Karakaya <karakayasemi@itu.edu.tr>
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

namespace OCA\Filesystem_Quota\Tests\Wrapper;

use OC\Files\Cache\CacheEntry;
use OCA\Filesystem_Quota\Wrapper\FilesystemQuota;
use \OCA\Filesystem_Quota\QuotaService;

\OC::$loader->load('\OC\Files\Filesystem');

/**
 * Class FilesystemQuotaTest
 *
 * @group DB
 *
 * @package OCA\Filesystem_Quota\Tests\Wrapper
 */
class FilesystemQuotaTest extends \Test\Files\Storage\Storage {

	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject | QuotaService
	 */
	private $quotaServiceMock;

	protected function setUp() {
		parent::setUp();
		$this->quotaServiceMock = $this->getMockBuilder('OCA\Filesystem_Quota\QuotaService')
			->disableOriginalConstructor()
			->getMock();
		$this->tmpDir = \OC::$server->getTempManager()->getTemporaryFolder();
		$storage = new \OC\Files\Storage\Local(['datadir' => $this->tmpDir]);
		$this->instance = new FilesystemQuota(['storage' => $storage, 'quota_service' => $this->quotaServiceMock]);
	}

	protected function tearDown() {
		\OC_Helper::rmdirr($this->tmpDir);
		parent::tearDown();
	}

	/**
	 * @param integer $limit
	 */
	protected function getLimitedStorage($limit) {
		$storage = new \OC\Files\Storage\Local(['datadir' => $this->tmpDir]);
		$storage->mkdir('files');
		$storage->getScanner()->scan('');
		$this->quotaServiceMock->expects($this->any())->method('freeSpace')
			->willReturn($limit);
		return new FilesystemQuota(['storage' => $storage, 'quota_service' => $this->quotaServiceMock]);
	}

	public function testTouchCreateFile() {
		$this->assertFalse($this->instance->file_exists('touch'));
		// returns true on success
		$this->assertFalse($this->instance->touch('touch'));
		$this->assertFalse($this->instance->file_exists('touch'));
	}

	public function testFilePutContentsNotEnoughSpace() {
		$instance = $this->getLimitedStorage(3);
		$this->assertFalse($instance->file_put_contents('files/foo', 'foobar'));
	}

}