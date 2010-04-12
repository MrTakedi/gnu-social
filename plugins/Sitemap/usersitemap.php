<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Show list of user pages
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Sitemap
 * @package   StatusNet
 * @author    Evan Prodromou <evan@status.net>
 * @copyright 2010 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET')) {
    exit(1);
}

/**
 * sitemap for users
 *
 * @category Sitemap
 * @package  StatusNet
 * @author   Evan Prodromou <evan@status.net>
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link     http://status.net/
 */

class UsersitemapAction extends SitemapAction
{
    var $user = null;

    function prepare($args)
    {
        parent::prepare($args);

        $y = $this->trimmed('year');

        $m = $this->trimmed('month');
        $d = $this->trimmed('day');

        $i = $this->trimmed('index');

        $y += 0;
        $m += 0;
        $d += 0;
        $i += 0;

        $offset = ($i-1) * SitemapPlugin::USERS_PER_MAP;
        $limit  = SitemapPlugin::USERS_PER_MAP;

        $this->user = new User();

        $begindt = sprintf('%04d-%02d-%02d 00:00:00', $y, $m, $d);

        // XXX: estimates 1d == 24h, which screws up days
        // with leap seconds (1d == 24h + 1s). Thankfully they're
        // few and far between.

        $enddt   = common_sql_date(strtotime($begindt) + (24 * 60 * 60));

        $this->user->whereAdd("created >= '$begindt'");
        $this->user->whereAdd("created <  '$enddt'");

        $this->user->orderBy('created');

        $this->user->limit($offset, $limit);

        $this->user->find();

        return true;
    }

    function nextUrl()
    {
        if ($this->user->fetch()) {
            return array(common_profile_url($this->user->nickname), null, null, null);
        } else {
            return null;
        }
    }
}
