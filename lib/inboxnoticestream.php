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
 * Stream of notices for a profile's "all" feed
 *
 * @category  NoticeStream
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Alexei Sorokin <sor.alexei@meowr.ru>
 * @author    Stephane Berube <chimo@chromic.org>
 * @copyright 2011 StatusNet, Inc.
 * @copyright 2014 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * @category  General
 * @copyright 2011 StatusNet, Inc.
 * @copyright 2014 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class InboxNoticeStream extends ScopingNoticeStream
{
    /**
     * Constructor
     *
     * @param Profile $target Profile to get a stream for
     * @param Profile $scoped Currently scoped profile (if null, it is fetched)
     */
    public function __construct(Profile $target, Profile $scoped = null)
    {
        parent::__construct(new CachingNoticeStream(new RawInboxNoticeStream($target), 'profileall:'.$target->getID()), $scoped);
    }
}

/**
 * Raw stream of notices for the target's inbox
 *
 * @category  General
 * @copyright 2011 StatusNet, Inc.
 * @copyright 2014 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class RawInboxNoticeStream extends FullNoticeStream
{
    protected $target = null;
    protected $inbox = null;

    /**
     * Constructor
     *
     * @param Profile $target Profile to get a stream for
     */
    public function __construct(Profile $target)
    {
        parent::__construct();
        $this->target = $target;
    }

    /**
     * Get IDs in a range
     *
     * @param int $offset Offset from start
     * @param int $limit Limit of number to get
     * @param int $since_id Since this notice
     * @param int $max_id Before this notice
     *
     * @return array IDs found
     */
    public function getNoticeIds($offset, $limit, $since_id = null, $max_id = null)
    {
        $notice = new Notice();
        $notice->selectAdd();
        $notice->selectAdd('notice.id');
        $notice->whereAdd(sprintf("notice.created > '%s'", $notice->escape($this->target->created)));
        // Reply:: is a table of mentions
        // Subscription:: is a table of subscriptions (every user is subscribed to themselves)
        $notice->_join .= sprintf(
            "\n" . 'INNER JOIN (' .
            '(SELECT id AS notice_id FROM notice WHERE profile_id IN (SELECT subscribed FROM subscription WHERE subscriber = %1$d)) UNION ' .
            '(SELECT notice_id FROM reply WHERE profile_id = %1$d) UNION ' .
            '(SELECT notice_id FROM attention WHERE profile_id = %1$d) UNION ' .
            '(SELECT notice_id FROM group_inbox WHERE group_id IN (SELECT group_id FROM group_member WHERE profile_id = %1$d))' .
            ') AS T ON T.notice_id = notice.id',
            $this->target->getID()
        );

        if (!empty($since_id)) {
            $notice->whereAdd(sprintf('notice.id > %d', $since_id));
        }
        if (!empty($max_id)) {
            $notice->whereAdd(sprintf('notice.id <= %d', $max_id));
        }

        self::filterVerbs($notice, $this->selectVerbs);

        $notice->limit($offset, $limit);

        if (!$notice->find()) {
            return [];
        }

        return $notice->fetchAll('id');
    }
}
