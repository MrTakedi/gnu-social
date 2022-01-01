<?php

declare(strict_types = 1);

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

namespace Plugin\Directory\Controller;

use App\Core\DB\DB;
use App\Entity\Actor;
use Component\Feed\Util\FeedController;
use Symfony\Component\HttpFoundation\Request;

class Directory extends FeedController
{
    /**
     * people stream
     *
     * @return array template
     */
    public function people(Request $request): array
    {
        return [
            '_template' => 'directory/people.html.twig',
            'actors'    => DB::findBy(Actor::class, ['type' => Actor::PERSON], order_by: ['created' => 'DESC', 'nickname' => 'ASC']),
        ];
    }

    /**
     * groups stream
     *
     * @return array template
     */
    public function groups(Request $request): array
    {
        return [
            '_template' => 'directory/groups.html.twig',
            'groups'    => DB::findBy(Actor::class, ['type' => Actor::GROUP], order_by: ['created' => 'DESC', 'nickname' => 'ASC']),
        ];
    }
}
