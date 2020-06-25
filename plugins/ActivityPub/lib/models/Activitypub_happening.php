<?php
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

/**
 * ActivityPub implementation for GNU social
 *
 * @package   GNUsocial
 * @author    Susanna Di Vita <susanna.divita.2@gmail.com>
 * @copyright 2018-2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 * @link      http://www.gnu.org/software/social/
 */

defined('GNUSOCIAL') || die();

/**
 * ActivityPub happenings representation
 *
 * @category  Plugin
 * @package   GNUsocial
 * @author    Susanna Di Vita <susanna.divita.2@gmail.com>
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Activitypub_happenings
{
    /**
     * Generates a pretty array from an Happening object
     *
     * @author Susanna Di Vita <susanna.divita.2@gmail.com>
     * @param  Happening $happening
     * @return array pretty array to be used in a response
     */
    public static function happening_to_array($happening)
    {
        $res = [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'type'      => 'Event',
            'name'      => $happening->getTitle(),
	     'startTime' => $happening->start_time,
            'endTime'   => $happening->end_time
        ];
        return $res;
    }
}
