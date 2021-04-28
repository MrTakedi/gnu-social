<?php

// {{{ License

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

// }}}

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Event;
use App\Core\GSFile;
use App\Core\Router\Router;
use App\Entity\AttachmentThumbnail;
use App\Util\Common;
use App\Util\Exception\ClientException;
use App\Util\Exception\NotFoundException;
use App\Util\Exception\ServerException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Attachment extends Controller
{
    private function attachment(int $id, callable $handle)
    {
        if (Event::handle('AttachmentFileInfo', [$id, &$res]) != Event::stop) {
            // If no one else claims this attachment, use the default representation
            $res = GSFile::getAttachmentFileInfo($id);
        }
        if (!empty($res)) {
            return $handle($res);
        } else {
            throw new ClientException('No such attachment', 404);
        }
    }

    /**
     * The page where the attachment and it's info is shown
     */
    public function attachment_show(Request $request, int $id)
    {
        try {
            $attachment = DB::findOneBy('attachment', ['id' => $id]);
            return $this->attachment($id, function ($res) use ($id, $attachment) {
                return [
                    '_template'     => 'attachments/show.html.twig',
                    'title'         => $res['title'],
                    'download'      => Router::url('attachment_download', ['id' => $id]),
                    'attachment'    => $attachment,
                    'related_notes' => DB::dql('select n from attachment_to_note an ' .
                                               'join note n with n.id = an.note_id ' .
                                               'where an.attachment_id = :attachment_id', ['attachment_id' => $id]),
                    'related_tags' => DB::dql('select distinct t.tag ' .
                                              'from attachment_to_note an join note_tag t with an.note_id = t.note_id ' .
                                              'where an.attachment_id = :attachment_id', ['attachment_id' => $id]),
                ];
            });
        } catch (NotFoundException) {
            throw new ClientException('No such attachment', 404);
        }
    }

    /**
     * Display the attachment inline
     */
    public function attachment_view(Request $request, int $id)
    {
        return $this->attachment($id, fn (array $res) => GSFile::sendFile($res['filepath'], $res['mimetype'], $res['title'], HeaderUtils::DISPOSITION_INLINE));
    }

    public function attachment_download(Request $request, int $id)
    {
        return $this->attachment($id, fn (array $res) => GSFile::sendFile($res['filepath'], $res['mimetype'], $res['title'], HeaderUtils::DISPOSITION_ATTACHMENT));
    }

    /**
     * Controller to produce a thumbnail for a given attachment id
     *
     * @param Request $request
     * @param int     $id      Attachment ID
     *
     * @throws NotFoundException
     * @throws ServerException
     *
     * @return Response
     */
    public function attachment_thumbnail(Request $request, int $id): Response
    {
        $attachment = DB::findOneBy('attachment', ['id' => $id]);
        if (!is_null($attachment->getScope())) {
            // && ($attachment->scope | VisibilityScope::PUBLIC) != 0
            // $user = Common::ensureLoggedIn();
            assert(false, 'Attachment scope not implemented');
        }

        // TODO rate limit, limit to known sizes

        $max_width  = Common::config('thumbnail', 'width');
        $max_height = Common::config('thumbnail', 'height');
        $width      = Common::clamp($this->int('w') ?: $max_width,  min: 0, max: $max_width);
        $height     = Common::clamp($this->int('h') ?: $max_height, min: 0, max: $max_height);
        $crop       = $this->bool('c') ?: false;

        $thumbnail = AttachmentThumbnail::getOrCreate(attachment: $attachment, width: $width, height: $height, crop: $crop);

        $filename = $thumbnail->getFilename();
        $path     = $thumbnail->getPath();

        return GSFile::sendFile(filepath: $path, mimetype: $attachment->getMimetype(), output_filename: $filename, disposition: 'inline');
    }
}
