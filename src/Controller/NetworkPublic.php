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

/**
 * Handle network public feed
 *
 * @package  GNUsocial
 * @category Controller
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Controller;

use App\Core\Controller;
use App\Core\DB\DB;
use App\Core\Form;
use function App\Core\I18n\_m;
use App\Entity\Note;
use App\Util\Common;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;

class NetworkPublic extends Controller
{
    public function handle(Request $request)
    {
        $form = Form::create([
            ['content', TextareaType::class, ['label' => ' ']],
            ['send',    SubmitType::class,   ['label' => _m('Send')]],
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            $data = $form->getData();
            if ($form->isValid()) {
                $content = $data['content'];
                $id      = Common::actor()->getId();
                $note    = Note::create(['gsactor_id' => $id, 'content' => $content]);
                DB::persist($note);
                DB::flush();
            } else {
                // TODO Display error
            }
        }

        $notes = DB::findBy('note', [], ['created' => 'DESC']);

        return [
            '_template' => 'network/public.html.twig',
            'post_form' => $form->createView(),
            'notes'     => $notes,
        ];
    }
}
