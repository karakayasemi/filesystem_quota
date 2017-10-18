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
		$this->quotaServiceMock->expects($this->any())->method('freeSpace')
			->willReturn(10000000);
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
		$quotaServiceMock = $this->getMockBuilder('OCA\Filesystem_Quota\QuotaService')
			->disableOriginalConstructor()
			->getMock();
		$quotaServiceMock->expects($this->any())->method('freeSpace')
			->willReturn($limit);
		return new FilesystemQuota(['storage' => $storage, 'quota_service' => $quotaServiceMock]);
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

	public function testCopyNotEnoughSpace() {
		$storage = new \OC\Files\Storage\Local(['datadir' => $this->tmpDir]);
		$storage->mkdir('files');
		$storage->getScanner()->scan('');
		$quotaServiceMock = $this->getMockBuilder('OCA\Filesystem_Quota\QuotaService')
			->disableOriginalConstructor()
			->getMock();
		$quotaServiceMock->expects($this->any())->method('freeSpace')
			->willReturnOnConsecutiveCalls(9,3);
		$instance = new FilesystemQuota(['storage' => $storage, 'quota_service' => $quotaServiceMock]);
		$this->assertEquals(6, $instance->file_put_contents('files/foo', 'foobar'));
		$instance->getScanner()->scan('');
		$this->assertFalse($instance->copy('files/foo', 'files/bar'));
	}

	public function testFreeSpace() {
		$instance = $this->getLimitedStorage(9);
		$this->assertEquals(9, $instance->free_space(''));
	}

	public function testStreamCopyWithEnoughSpace() {
		$instance = $this->getLimitedStorage(16);
		$inputStream = fopen('data://text/plain,foobarqwerty', 'r');
		$outputStream = $instance->fopen('files/foo', 'w+');
		list($count, $result) = \OC_Helper::streamCopy($inputStream, $outputStream);
		$this->assertEquals(12, $count);
		$this->assertTrue($result);
		fclose($inputStream);
		fclose($outputStream);
	}

	public function testReturnFalseWhenFopenFailed() {
		/**@var $failStorage \PHPUnit_Framework_MockObject_MockObject | \OC\Files\Storage\Local*/
		$failStorage = $this->getMockBuilder('\OC\Files\Storage\Local')
			->setMethods(['fopen'])
			->setConstructorArgs([['datadir' => $this->tmpDir]])
			->getMock();

		$failStorage->expects($this->any())
			->method('fopen')
			->will($this->returnValue(false));

		$this->assertFalse($failStorage->fopen('failedfopen', 'r'));
	}

	public function testReturnRegularStreamOnRead() {
		$instance = $this->getLimitedStorage(9);

		// create test file first
		$stream = $instance->fopen('files/foo', 'w+');
		fwrite($stream, 'blablacontent');
		fclose($stream);

		$stream = $instance->fopen('files/foo', 'r');
		$meta = stream_get_meta_data($stream);
		$this->assertEquals('plainfile', $meta['wrapper_type']);
		fclose($stream);

		$stream = $instance->fopen('files/foo', 'rb');
		$meta = stream_get_meta_data($stream);
		$this->assertEquals('plainfile', $meta['wrapper_type']);
		fclose($stream);
	}

	public function testReturnRegularStreamWhenOutsideFiles() {
		$instance = $this->getLimitedStorage(9);
		$instance->mkdir('files_other');

		// create test file first
		$stream = $instance->fopen('files_other/foo', 'w+');
		$meta = stream_get_meta_data($stream);
		$this->assertEquals('plainfile', $meta['wrapper_type']);
		fclose($stream);
	}

	public function testSpaceRoot() {
		$storage = $this->getMockBuilder('\OC\Files\Storage\Local')->disableOriginalConstructor()->getMock();
		$cache = $this->getMockBuilder('\OC\Files\Cache\Cache')->disableOriginalConstructor()->getMock();
		$storage->expects($this->once())
			->method('getCache')
			->will($this->returnValue($cache));
		$storage->expects($this->once())
			->method('free_space')
			->will($this->returnValue(2048));
		$cache->expects($this->once())
			->method('get')
			->with('files')
			->will($this->returnValue(new CacheEntry(['size' => 50])));

		$instance = new \OC\Files\Storage\Wrapper\Quota(['storage' => $storage, 'quota' => 1024, 'root' => 'files']);

		$this->assertEquals(1024 - 50, $instance->free_space(''));
	}

	public function testInstanceOfStorageWrapper() {
		$this->assertTrue($this->instance->instanceOfStorage('\OC\Files\Storage\Local'));
		$this->assertTrue($this->instance->instanceOfStorage('\OC\Files\Storage\Wrapper\Wrapper'));
		$this->assertTrue($this->instance->instanceOfStorage('OCA\Filesystem_Quota\Wrapper\FilesystemQuota'));
	}

}