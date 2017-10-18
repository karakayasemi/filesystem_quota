<?php
/**
 *
 * @author Semih Serhat Karakaya
 * @copyright Copyright (c) 2016, ITU IT HEAD OFFICE.
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
namespace OCA\Filesystem_Quota;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClient;
use OC\HintException;

class QuotaService {
	/**
	 * @var IClient $client
	 */
	protected $client;
	/**
	 * @param IClient $client
	 */
	public function __construct($client) {
		$this->client = $client;
	}

	/**
	 * @param string $user
	 * @return int
	 * @throws HintException
	 * @throws \Exception
	 */
	public function freeSpace($user) {
		return 100000000000;
		$quotServiceHost = \OC::$server->getConfig()->getAppValue('filesystem_quota', 'quota_service_uri');
		$options['headers']=array('uid'=>$ldapUidNumber);
		try {
			$response = $this->client->get($quotServiceHost, $options);
		} catch (\Exception $e) {
			throw $e;
		}
		if ($response->getStatusCode() === Http::STATUS_OK) {
			$quotaResponse = json_decode($response->getBody(), true);
			$quotaLimit = $quotaResponse['quota_limit'];
			$currentUsage = $quotaResponse['current_usage'];
			return $quotaLimit-$currentUsage;
		} else {
			throw new HintException('Quota response is not okay.');
		}
	}
}