/**
 * @copyright Copyright (c) 2017 Semih Serhat Karakaya <karakayasemi@itu.edu.tr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$(document).ready(function(){
    $('#filesystem-quota-settings').on('click','#save-filesystem-quota-settings',
        function() {
            var quotaURI = $('#filesystem-quota-service-uri').val();
            var umask = $('#filesystem-quota-umask').val();
            var ownerGID = $('#filesystem-quota-owner-gid').val();
            OC.msg.startSaving('#filesystem-quota-save-settings-message');
            if (!isURL(quotaURI)) {
                OC.msg.finishedError('#filesystem-quota-save-settings-message', t('filesystem_quota', 'URI is not valid'));
                return;
            }
            if (!isUmask(umask)) {
                OC.msg.finishedError('#filesystem-quota-save-settings-message', t('filesystem_quota', 'umask is not valid'));
                return;
            }
            if (!isGID(ownerGID)) {
                OC.msg.finishedError('#filesystem-quota-save-settings-message', t('filesystem_quota', 'GID is not valid'));
                return;
            }
            OC.AppConfig.setValue('filesystem_quota', 'quota_service_uri', quotaURI);
            OC.AppConfig.setValue('filesystem_quota', 'umask', umask);
            OC.AppConfig.setValue('filesystem_quota', 'owner_gid', ownerGID);
            OC.msg.finishedSuccess('#filesystem-quota-save-settings-message', t('filesystem_quota', 'Preferences are saved'));
        }
    );
});

function isURL(str) {
    var pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
        '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.?)+[a-z]{2,}|'+ // domain name
        '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
        '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*'+ // port and path
        '(\\?[;&a-z\\d%_.~+=-]*)?'+ // query string
        '(\\#[-a-z\\d_]*)?$','i'); // fragment locator
    return pattern.test(str);
}

function isUmask(str) {
    return /^\d+$/.test(str) && str.length<4;
}

function isGID(str) {
    return /^\d+$/.test(str);
}