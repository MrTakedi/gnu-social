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
 * ActivityPub queue handler for notice distribution
 *
 * @package   GNUsocial
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class ActivityPubQueueHandler extends QueueHandler
{
    /**
     * Getter of the queue transport name.
     * 
     * @return string transport name
     */
    public function transport(): string
    {
        return 'activitypub';
    }

    /**
     * Notice distribution handler.
     *
     * @param Notice $notice notice to be distributed.
     * @return bool true on success, false otherwise
     * @throws HTTP_Request2_Exception
     * @throws InvalidUrlException
     * @throws ServerException
     * @author Diogo Cordeiro <diogo@fc.up.pt>
     */
    public function handle($notice): bool
    {
        if (!($notice instanceof Notice)) {
            common_log(LOG_ERR, "Got a bogus notice, not distributing");
            return true;
        }

        $profile = $notice->getProfile();

        if (!$profile->isLocal()) {
            return true;
        }

        // Ignore activity/non-post/share-verb notices
        $is_valid_verb = ($notice->verb == ActivityVerb::POST ||
                          $notice->verb == ActivityVerb::SHARE);

        if ($notice->source == 'activity' || !$is_valid_verb) {
            common_log(LOG_ERR, "Ignoring distribution of notice:{$notice->id}: activity source or invalid Verb");
            return true;
        }
        
        $other = Activitypub_profile::from_profile_collection(
            $notice->getAttentionProfiles()
        );

        // Handling a reply?
        if ($notice->reply_to) {
            try {
                $parent_notice = $notice->getParent();

                try {
                    $other[] = Activitypub_profile::from_profile($parent_notice->getProfile());
                } catch (Exception $e) {
                    // Local user can be ignored
                }

                foreach ($parent_notice->getAttentionProfiles() as $mention) {
                    try {
                        $other[] = Activitypub_profile::from_profile($mention);
                    } catch (Exception $e) {
                        // Local user can be ignored
                    }
                }
            } catch (NoParentNoticeException $e) {
                // This is not a reply to something (has no parent)
            } catch (NoResultException $e) {
                // Parent author's profile not found! Complain louder?
                common_log(LOG_ERR, "Parent notice's author not found: ".$e->getMessage());
            }
        }

        // Handling an Announce?
        if ($notice->isRepeat()) {
            $repeated_notice = Notice::getKV('id', $notice->repeat_of);
            if ($repeated_notice instanceof Notice) {
                $other = array_merge($other,
                                     Activitypub_profile::from_profile_collection(
                                         $repeated_notice->getAttentionProfiles()
                                     ));

                try {
                    $other[] = Activitypub_profile::from_profile($repeated_notice->getProfile());
                } catch (Exception $e) {
                    // Local user can be ignored
                }

                // That was it
                $postman = new Activitypub_postman($profile, $other);
                $postman->announce($repeated_notice);
            }

            // either made the announce or found nothing to repeat
            return true;
        }
		
        switch ($notice->verb) {
            case 'onStartSubscribe': 
                if (!$other instanceof Activitypub_profile) {
            	    return true;
                }
                $postman = new Activitypub_postman($profile, [$other]);
                $postman->follow();
                return true;
                break;
            case 'onStartUnsubscribe':
                if (!$other instanceof Activitypub_profile) { 
                    return true; 
                }
                $postman = new ActivityPub_postman($profile, [$other]);
                $postman->undo_follow();
                return true;
                break;
	   case onEndFavorNotice:
            	if ($notice->reply_to) {
                    try {
                        $parent_notice = $notice->getParent();
                        
                        try {
                            $other[] = Activitypub_profile::from_profile($parent_notice->getProfile());
                        } catch (Exception $e) {
                            // Local user can be ignored
                        }

                        $other = array_merge($other,
                                             Activitypub_profile::from_profile_collection(
                                                 $parent_notice->getAttentionProfiles()
                                             ));
                    } catch (NoParentNoticeException $e) {
                        // This is not a reply to something (has no parent)
                    } catch (NoResultException $e) {
                        // Parent author's profile not found! Complain louder?
                        common_log(LOG_ERR, "Parent notice's author not found: ".$e->getMessage());
                    }
                }

                $postman = new Activitypub_postman($profile, $other);
                $postman->like($notice);
                break;
           case 'onEndDisfavorNotice':
            	if ($notice->reply_to) {
               	    try {
                    	$parent_notice = $notice->getParent();

                        try {
                            $other[] = Activitypub_profile::from_profile($parent_notice->getProfile());
                        } catch (Exception $e) {
                            // Local user can be ignored
                        }

                        $other = array_merge($other,
                                             Activitypub_profile::from_profile_collection(
                                                 $parent_notice->getAttentionProfiles()
                                             ));
                    } catch (NoParentNoticeException $e) {
                        // This is not a reply to something (has no parent)
                    } catch (NoResultException $e) {
                        // Parent author's profile not found! Complain louder?
                        common_log(LOG_ERR, "Parent notice's author not found: ".$e->getMessage());
                    }
                }

                $postman = new Activitypub_postman($profile, $other);
                $postman->undo_like($notice);
                break;
            case 'onStartDeleteOwnNotice':
            	if ($notice->isRepeat() || ($notice->getProfile()->getID() != $profile->getID())) {
                    return true;
                }

                $other = Activitypub_profile::from_profile_collection(
                    $notice->getAttentionProfiles()
                );

                if ($notice->reply_to) {
                    try {
                        $parent_notice = $notice->getParent();

                        try {
                            $other[] = Activitypub_profile::from_profile($parent_notice->getProfile());
                        } catch (Exception $e) {
                            // Local user can be ignored
                        }

                        $other = array_merge($other,
                                             Activitypub_profile::from_profile_collection(
                                                 $parent_notice->getAttentionProfiles()
                                             ));
                    } catch (NoParentNoticeException $e) {
                        // This is not a reply to something (has no parent)
                    } catch (NoResultException $e) {
                        // Parent author's profile not found! Complain louder?
                        common_log(LOG_ERR, "Parent notice's author not found: ".$e->getMessage());
                    }
                }

                $postman = new Activitypub_postman($profile, $other);
                $postman->delete_note($notice);
                break;
            case 'onEndDeleteUser':
            	$postman = new Activitypub_postman($user->getProfile());
                $postman->delete_profile();
                break;
            case 'onSendDirectMessage':
            	if (!empty($to)) {
                    $postman = new Activitypub_postman($from, $to);
                    $postman->create_direct_note($message);
                }
                break;
            case 'onStartNoticeSourceLink':
            	// If we don't handle this, keep the event handler going
                if (!in_array($notice->source, array('ActivityPub', 'share'))) {
                    return true;
                }

                try {
                    $url = $notice->getUrl();
                    // If getUrl() throws exception, $url is never set

                    $bits = parse_url($url);
                    $domain = $bits['host'];
                    if (substr($domain, 0, 4) == 'www.') {
                        $name = substr($domain, 4);
                    } else {
                        $name = $domain;
                    }

                    // TRANS: Title. %s is a domain name.
                    $title = sprintf(_m('Sent from %s via ActivityPub'), $domain);

                    // Abort event handler, we have a name and URL!
                    return false;
                } catch (InvalidUrlException $e) {
                    // This just means we don't have the notice source data
                    return true;
                }
                break;
        }
        
        // That was it
        $postman = new Activitypub_postman($profile, $other);
        $postman->create_note($notice);
        return true;
    }
}
