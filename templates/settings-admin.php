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
/** @var array $_ */
/** @var \OCP\IL10N $l */
script('filesystem_quota', 'settings-admin');
style('filesystem_quota', 'settings-admin')
?>
<div id="filesystem-quota-settings" class="section">
    <h2 class="inlineblock"><?php p($l->t('Filesystem Quota')); ?></h2>
    <span id="filesystem-quota-save-settings-message" class="msg"></span>
    <div>
        <label for="filesystem-quota-quota-uri"><?php p($l->t('URI')) ?></label><br>
        <input type="text" id="filesystem-quota-service-uri"  value="<?php p($_['quota_service_uri']) ?>"><br>
        <label for="filesystem-quota-umask"><?php p($l->t('UMASK')) ?></label><br>
        <input type="text" id="filesystem-quota-umask"  value="<?php p($_['umask']) ?>"><br>
        <label for="filesystem-quota-owner-gid"><?php p($l->t('GID')) ?></label><br>
        <input type="text" id="filesystem-quota-owner-gid" value="<?php p($_['owner_gid']) ?>"><br>
        <button id="save-filesystem-quota-settings" class="save"><?php p($l->t('Save settings'));?></button>
    </div>
</div>