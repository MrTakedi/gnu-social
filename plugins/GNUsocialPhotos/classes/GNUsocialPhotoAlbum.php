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
 *
 * @category  Widget
 * @package   GNUsocial
 * @author    Ian Denhardt <ian@zenhack.net>
 * @author    Sean Corbett <sean@gnu.org>
 * @copyright 2010 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

require_once INSTALLDIR . '/classes/Memcached_DataObject.php';

class GNUsocialPhotoAlbum extends Managed_DataObject
{
    public $__table = 'GNUsocialPhotoAlbum';
    public $album_id;          // int(11) -- Unique identifier for the album
    public $profile_id;        // int(11) -- Profile ID for the owner of the album
    public $album_name;        // varchar(191) -- Title for this album   not 255 because utf8mb4 takes more space
    public $album_description; // text -- A description of the album
    public $created;           // datetime()   not_null
    public $modified;          // timestamp()   not_null default_CURRENT_TIMESTAMP

    public static function schemaDef()
    {
        return array(
            'fields' => array(
                'album_id' => array('type' => 'serial', 'not null' => true, 'description' => 'Unique identifier for the album'),
                'profile_id' => array('type' => 'int', 'not null' => true, 'description' => 'Profile ID for the owner of the album'),
                'album_name' => array('type' => 'varchar', 'not null' => true, 'length' => 191, 'description' => 'Title for this album'),
                'album_description' => array('type' => 'text', 'not null' => true, 'description' => 'A description for this album'),
                'created' => array('type' => 'datetime', 'not null' => true, 'description' => 'date this record was created'),
                'modified' => array('type' => 'timestamp', 'not null' => true, 'description' => 'date this record was modified'),
            ),
            'primary key' => array('album_id'),
            'unique keys' => array(
                'gnusocialphotoalbum_profile_id_key' => array('profile_id'),
            ),
            'foreign keys' => array(
                'gnusocialphotoalbum_profile_id_fkey' => array('profile', array('profile_id' => 'id')),
            ),
            'indexes' => array(
                'gnusocialphotoalbum_album_name_idx' => array('album_name'),
            ),
        );
    }

    public function getPageLink()
    {
        $profile = Profile::getKV('id', $this->profile_id);
        return '/' . $profile->nickname . '/photos/' . $this->album_id;
    }

    public function getThumbUri()
    {
        $photo = GNUsocialPhoto::getKV('album_id', $this->album_id);
        if (empty($photo)) {
            // For now...
            return '/theme/default/default-avatar-profile.png';
        }
        return $photo->thumb_uri;
    }

    public static function newAlbum($profile_id, $album_name, $album_description)
    {
        //TODO: Should use foreign key instead...
        if (!Profile::getKV('id', $profile_id)) {
            //Is this a bit extreme?
            throw new ServerException(_m('No such user exists with id ' . $profile_id . ', couldn\'t create album.'));
        }
        
        $album = new GNUsocialPhotoAlbum();
        $album->profile_id = $profile_id;
        $album->album_name = $album_name;
        $album->album_description = $album_description;
       
        $album->album_id = $album->insert();
        if (!$album->album_id) {
            common_log_db_error($album, 'INSERT', __FILE__);
            throw new ServerException(_m('Error creating new album.'));
        }
        common_log(LOG_INFO, 'album_id : ' . $album->album_id);
        return $album;
    }
}
