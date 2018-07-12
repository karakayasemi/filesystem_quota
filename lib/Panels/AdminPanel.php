<?php
/**
 * @author Semih Serhat Karakaya <karakayasemi@itu.edu.tr>
 * @copyright Copyright (c) 2017, ITU/BIDB.
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
 */
namespace OCA\Filesystem_Quota\Panels;
use OCP\Settings\ISettings;
use OCP\Template;
class AdminPanel implements ISettings {

	public function getPanel() {
		$params = [
			'quota_service_uri' => \OC::$server->getConfig()->getAppValue(
				'filesystem_quota',
				'quota_service_uri',
				'http://localhost/quota.php'
			),
			'umask' => \OC::$server->getConfig()->getAppValue(
				'filesystem_quota',
				'umask',
				'007'
			),
			'owner_gid' => \OC::$server->getConfig()->getAppValue(
				'filesystem_quota',
				'owner_gid',
				'300'
			),
		];
		$tmpl = new Template('filesystem_quota', 'settings-admin');
		foreach ($params as $key => $value) {
			$tmpl->assign($key, $value);
		}
		return $tmpl;
	}
	public function getSectionID() {
		return 'storage';
	}
	public function getPriority() {
		return 100;
	}
}
