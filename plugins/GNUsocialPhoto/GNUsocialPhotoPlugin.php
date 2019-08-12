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
 * @category  Widget
 * @package   GNUsocial
 * @author    Ian Denhardt <ian@zenhack.net>
 * @copyright 2011 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

class GNUsocialPhotoPlugin extends MicroAppPlugin
{
    public $oldSaveNew = true;

    public function onCheckSchema()
    {
        $schema = Schema::get();

        $schema->ensureTable('photo', Photo::schemaDef());

        return true;
    }

    public function onRouterInitialized($m)
    {
        $m->connect('main/photo/new', ['action' => 'newphoto']);
        $m->connect('main/photo/:id', ['action' => 'showphoto']);
        return true;
    }

    public function entryForm($out)
    {
        return new NewPhotoForm($out);
    }

    public function appTitle()
    {
        return _('Photo');
    }

    public function tag()
    {
        return 'Photo';
    }

    public function types()
    {
        return array(Photo::OBJECT_TYPE);
    }

    public function saveNoticeFromActivity(Activity $activity, Profile $actor, array $options = [])
    {
        if (count($activity->objects) != 1) {
            throw new Exception('Too many activity objects.');
        }

        $photoObj = $activity->objects[0];

        if ($photoObj->type != Photo::OBJECT_TYPE) {
            throw new Exception('Wrong type for object.');
        }

        $photo_uri = $photoObj->largerImage;
        $thumb_uri = $photo_uri;
        if (!empty($photoObj->thumbnail)) {
            $thumb_uri = $photoObj->thumbnail;
        }

        $description = $photoObj->description;
        $title = $photoObj->title;

        $options['object_type'] = Photo::OBJECT_TYPE;

        Photo::saveNew($actor, $photo_uri, $thumb_uri, $title, $description, $options);
    }

    public function activityObjectFromNotice(Notice $notice)
    {
        $photo = Photo::getByNotice($notice);

        $object = new ActivityObject();
        $object->id = $notice->uri;
        $object->type = Photo::OBJECT_TYPE;
        $object->title = $photo->title;
        $object->summary = $notice->content;
        $object->link = $notice->getUrl();

        $object->largerImage = $photo->photo_uri;
        $object->thumbnail = $photo->thumb_uri;
        $object->description = $photo->description;

        return $object;
    }

    public function showNoticeContent(Notice $notice, HTMLOutputter $out, Profile $scoped = null)
    {
        $photo = Photo::getByNotice($notice);
        if ($photo) {
            if ($photo->title) {
                // TODO: ugly. feel like we should have a more abstract way
                // of choosing the h-level.
                $out->element('h3', array(), $title);
            }
            $out->element('img', array('src' => $photo->photo_uri,
                'width' => '100%'));
            // TODO: add description
        }
    }

    public function deleteRelated(Notice $notice)
    {
        $photo = Photo::getByNotice($notice);
        if ($photo) {
            $photo->delete();
        }
    }
}
