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
 * @package   GNUsocial
 * @author    Ian Denhardt <ian@zenhack.net>
 * @copyright 2011 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Data class for photos
 *
 * @copyright 2011 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class Photo extends Managed_DataObject
{
    const OBJECT_TYPE = 'http://activitystrea.ms/schema/1.0/photo';

    public $__table = 'photo'; // table name
    public $id;                // char (36) // UUID
    public $uri;               // varchar (191)  // This is the corresponding notice's uri.   not 255 because utf8mb4 takes more space
    public $photo_uri;         // varchar (191)   not 255 because utf8mb4 takes more space
    public $thumb_uri;         // varchar (191)   not 255 because utf8mb4 takes more space
    public $title;             // varchar (191)   not 255 because utf8mb4 takes more space
    public $description;       // text
    public $profile_id;        // int

    public static function getByNotice($notice)
    {
        return self::getKV('uri', $notice->uri);
    }

    public function getNotice()
    {
        return Notice::getKV('uri', $this->uri);
    }

    public static function schemaDef()
    {
        return array(
            'description' => 'A photograph',
            'fields' => array(
                'id' => array('type' => 'char',
                              'length' => 36,
                              'not null' => true,
                              'description' => 'UUID'),
                'uri' => array('type' => 'varchar',
                               'length' => 191,
                               'not null' => true),
                'photo_uri' => array('type' => 'varchar',
                               'length' => 191,
                               'not null' => true),
                'photo_uri' => array('type' => 'varchar',
                               'length' => 191,
                               'not null' => true),
                'profile_id' => array('type' => 'int', 'not null' => true),
            ),
            'primary key' => array('id'),
            'foreign keys' => array(
                'photo_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
            ),
        );
    }

    public static function saveNew(Profile $profile, $photo_uri, $thumb_uri, $title, $description, $options = [])
    {
        $photo = new Photo();

        $photo->id =  UUID::gen();
        $photo->profile_id = $profile->id;
        $photo->photo_uri = $photo_uri;
        $photo->thumb_uri = $thumb_uri;


        $options['object_type'] = Photo::OBJECT_TYPE;

        if (!array_key_exists('uri', $options)) {
            $options['uri'] = common_local_url('showphoto', array('id' => $photo->id));
        }

        if (!array_key_exists('rendered', $options)) {
            $options['rendered'] = sprintf(
                "<img src=\"%s\" alt=\"%s\"></img>",
                $photo_uri,
                $title
            );
        }

        $photo->uri = $options['uri'];
        
        $photo->insert();

        return Notice::saveNew(
            $profile->id,
            '',
            'web',
            $options
        );
    }
}
