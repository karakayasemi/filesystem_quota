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
use \OC\HintException;
use \OCP\ISession;
class LdapService
{
	/** @var ISession */
	protected $session;
	private $ldapHost;
	private $ldapPort;
	private $ldapRdn;
	private $ldapPass;
	private $dn;
	private $ldapConn;
	private $search;
	public function __construct(ISession $session){
		$this->session = $session;
		$ocConfig = \OC::$server->getConfig();
		$this->ldapHost = $ocConfig->getAppValue('user_ldap', 'ldap_host');
		$this->ldapPort = $ocConfig->getAppValue('user_ldap', 'ldap_port');
		$this->ldapRdn = $ocConfig->getAppValue('user_ldap', 'ldap_dn');
		$this->ldapPass = base64_decode($ocConfig->getAppValue('user_ldap', 'ldap_agent_password'));
		$this->dn = $ocConfig->getAppValue('user_ldap', 'ldap_base');
		//connect to server
		if(($this->ldapConn = ldap_connect($this->ldapHost, $this->ldapPort)) === false) {
			throw new HintException('Could not connect to ldap');
		}
		//bind with ldapuser
		if ($this->ldapConn)
		{
			if((ldap_bind($this->ldapConn, $this->ldapRdn, $this->ldapPass)) === false) {
				throw new HintException('Could not bind to ldap');
			}
		}
	}
	//userKey to uidNumber converter. This function can use for search other user attributes by changing result parameter.
	//userKey must be either sAMAccountName or MS AD-GUID
	function searchUidNumber($userKey) {
		if($this->session->get('filesystem_quota'.$userKey)) {
			return $this->session->get('filesystem_quota'.$userKey);
		}
		//if userKey is AD GUID
		if ( preg_match('/^[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-4[0-9A-Fa-f]{3}-[89ABab][0-9A-Fa-f]{3}-[0-9A-Fa-f]{12}$/i', $userKey) )
		{
			$userKey = str_replace('-', '', $userKey);
			$adguid = '';
			for ($i=7;$i>=0;$i--)
				if (($i % 2) == 1)
					$adguid .= "\\" . $userKey[$i-1] . $userKey[$i];
			for ($i=11;$i>=8;$i--)
				if (($i % 2) == 1)
					$adguid .= "\\" . $userKey[$i-1] . $userKey[$i];
			for ($i=15;$i>=12;$i--)
				if (($i % 2) == 1)
					$adguid .= "\\" . $userKey[$i-1] . $userKey[$i];
			for ($i=16;$i<32;$i++)
				if (($i % 2) == 0)
					$adguid .= "\\" . $userKey[$i];
				else
					$adguid .= $userKey[$i];
			$filter='(&(objectClass=user)(objectGuid=' . $adguid . '))';
		}
		//if userKey is sAMAccountName
		else
			$filter='(&(objectClass=user)(sAMAccountName='.$userKey.'))';
		$result = array('uidNumber');
		//search with filter get desired result
		$this->search = ldap_search($this->ldapConn, $this->dn, $filter, $result);
		$info = ldap_get_entries($this->ldapConn, $this->search);
		if($info['count']>0 && isset($info[0]['uidnumber'][0])){
			$this->session->set('filesystem_quota'.$userKey,$info[0]['uidnumber'][0]);
			return $info[0]['uidnumber'][0];
		}
		throw new HintException('You are not a ldap user');
	}
}