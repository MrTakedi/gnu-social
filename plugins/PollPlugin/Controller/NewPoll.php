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

namespace Plugin\PollPlugin\Controller;

use App\Core\DB\DB;
use App\Entity\Poll;
use App\Util\Common;
use Plugin\PollPlugin\Forms\NewPollForm;
use Symfony\Component\HttpFoundation\Request;

class NewPoll
{
    public function newpoll(Request $request)
    {
        $user = Common::ensureLoggedIn();

        $numOptions = 3; //temporary
        $form       = NewPollForm::make($numOptions);

        $form->handleRequest($request);
        $question = 'Test Question?';
        $opt      = [];
        if ($form->isSubmitted()) {
            $data = $form->getData();
            //var_dump($data);
            for ($i = 1; $i <= $numOptions; ++$i) {
                array_push($opt,$data['Option_' . $i]);
            }
            $testPoll = Poll::make($question,$opt);
            DB::persist($testPoll);
            DB::flush();
            //var_dump($testPoll);
        }

        // testing

        //$test = Poll::create(['id' => '0', 'uri' => 'a']);
        //DB::persist($test);
        //DB::flush();
        /*
        $loadpoll = Poll::getFromId('0');
        var_dump($loadpoll);
        */

        return ['_template' => 'Poll/newpoll.html.twig', 'form' => $form->createView()];
    }
}
