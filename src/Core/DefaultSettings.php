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
 * Write the default settings to the database
 *
 * @package GNUsocial
 * @category DB
 *
 * @author    Hugo Sales <hugo@fc.up.pt>
 * @copyright 2020 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace App\Core;

use App\Entity\Config;
use App\Util\Common;
use Symfony\Component\Config\Definition\Exception\Exception;

abstract class DefaultSettings
{
    public static array $defaults;
    public static function setDefaults()
    {
        self::$defaults =
            ['site' =>
                 ['name'                 => $_ENV['SOCIAL_SITENAME'] ?? 'Another social instance',
                  'server'               => $_ENV['SOCIAL_DOMAIN'] ?? 'localhost',
                  'notice'               => null,  // site wide notice text
                  'theme'                => 'default',
                  'logo'                 => null,
                  'language'             => 'en',
                  'detect_language'      => true,
                  'languages'            => I18n::get_all_languages(),
                  'email'                => $_ENV['SERVER_ADMIN'] ?? $_ENV['SOCIAL_ADMIN_EMAIL'] ?? null,
                  'recovery_disclose'    => false, // Whether to not say that we found the email in the database, when asking for recovery
                  'timezone'             => 'UTC',
                  'brought_by'           => null,
                  'brought_by_url'       => null,
                  'closed'               => false,
                  'invite_only'          => true,
                  'private'              => false,
                  'ssl'                  => 'always',
                  'ssl_proxy'            => false, // set to true to force GNU social to think it is HTTPS (i.e. using reverse proxy to enable it)
                  'duplicate_time_limit' => 60,    // default for same person saying the same thing
                  'text_limit'           => 1000,  // in chars; 0 == no limit
                  'x-static-delivery'    => null,
                 ],
             'security' => ['hash_algos' => ['sha1', 'sha256', 'sha512']],   // set to null for anything that hash_hmac() can handle (and is in hash_algos())
             'db' => ['mirror' => null],   // TODO implement
             'fix' =>
                 ['fancy_urls'  => true,  // makes sure aliases in WebFinger etc. are not f'd by index.php/ URLs
                  'http' => true,         // set this to true if you have upgraded your site from http=>https
                 ],
             'queue' =>
                 ['enabled'               => true,
                  'daemon'                => false, // Use queuedaemon. Default to false
                  'threads'               => null,  // an empty value here uses processor count to determine
                  'subsystem'             => false,  // default to database, or 'stomp'
                  'basename'              => '/queue/gnusocial/',
                  'control_channel'       => '/topic/gnusocial/control', // broadcasts to all queue daemons
                  'monitor'               => null,  // URL to monitor ping endpoint (work in progress)
                  'soft_limit'            => '90%', // total size or % of memory_limit at which to restart queue threads gracefully
                  'spawn_delay'           => 1,     // Wait at least N seconds between (re)spawns of child processes to avoid slamming the queue server with subscription startup
                  'debug_memory'          => false, // true to spit memory usage to log
                  'stomp_server'          => null,
                  'stomp_username'        => null,
                  'stomp_password'        => null,
                  'stomp_persistent'      => true,  // keep items across queue server restart, if persistence is enabled
                  'stomp_transactions'    => true,  // use STOMP transactions to aid in detecting failures (supported by ActiveMQ, but not by all)
                  'stomp_acks'            => true,  // send acknowledgements after successful processing (supported by ActiveMQ, but not by all)
                  'stomp_manual_failover' => true,  // if multiple servers are listed, treat them as separate (enqueue on one randomly, listen on all)
                  'max_retries'           => 10,    // drop messages after N failed attempts to process (Stomp)
                  'dead_letter_dir'       => false, // set to directory to save dropped messages into (Stomp)
                 ],
             'avatar' =>
                 ['server'      => null,
                  'url_base'    => '/avatar/',
                  'ssl'         => null,
                  'dir'         => INSTALLDIR . '/file/avatar/',
                  'max_size_px' => 300,
                 ],
             'javascript' =>
                  ['server'      => null,
                   'url_base'    => '/js/',
                   'ssl'         => null,
                   'bust_frames' => true,
                  ],
             'attachments' =>
                 ['server'    => null,
                  'url_base'  => '/file/',
                  'ssl'       => null,
                  'dir'       => INSTALLDIR . '/file/uploads/',
                  'supported' =>
                      ['application/vnd.oasis.opendocument.chart'                 => 'odc',
                       'application/vnd.oasis.opendocument.formula'               => 'odf',
                       'application/vnd.oasis.opendocument.graphics'              => 'odg',
                       'application/vnd.oasis.opendocument.graphics-template'     => 'otg',
                       'application/vnd.oasis.opendocument.image'                 => 'odi',
                       'application/vnd.oasis.opendocument.presentation'          => 'odp',
                       'application/vnd.oasis.opendocument.presentation-template' => 'otp',
                       'application/vnd.oasis.opendocument.spreadsheet'           => 'ods',
                       'application/vnd.oasis.opendocument.spreadsheet-template'  => 'ots',
                       'application/vnd.oasis.opendocument.text'                  => 'odt',
                       'application/vnd.oasis.opendocument.text-master'           => 'odm',
                       'application/vnd.oasis.opendocument.text-template'         => 'ott',
                       'application/vnd.oasis.opendocument.text-web'              => 'oth',
                       'application/pdf'                                          => 'pdf',
                       'application/zip'                                          => 'zip',
                       'application/x-bzip2'                                      => 'bz2',
                       'application/x-go-sgf'                                     => 'sgf',
                       'application/xml'                                          => 'xml',
                       'application/gpx+xml'                                      => 'gpx',
                       image_type_to_mime_type(IMAGETYPE_PNG)                     => image_type_to_extension(IMAGETYPE_PNG),
                       image_type_to_mime_type(IMAGETYPE_JPEG)                    => image_type_to_extension(IMAGETYPE_JPEG),
                       image_type_to_mime_type(IMAGETYPE_GIF)                     => image_type_to_extension(IMAGETYPE_GIF),
                       image_type_to_mime_type(IMAGETYPE_ICO)                     => image_type_to_extension(IMAGETYPE_ICO),
                       'image/svg+xml'                                            => 'svg', // No built-in constant
                       'audio/ogg'                                                => 'ogg',
                       'audio/mpeg'                                               => 'mpg',
                       'audio/x-speex'                                            => 'spx',
                       'application/ogg'                                          => 'ogx',
                       'text/plain'                                               => 'txt',
                       'video/mpeg'                                               => 'mpeg',
                       'video/mp4'                                                => 'mp4',
                       'video/ogg'                                                => 'ogv',
                       'video/quicktime'                                          => 'mov',
                       'video/webm'                                               => 'webm',
                      ],
                  'file_quota'    => Common::get_preferred_php_upload_limit(),
                  'user_quota'    => Common::size_str_to_int('200M'),
                  'monthly_quota' => Common::size_str_to_int('20M'),
                  'uploads'       => true,
                  'show_html'     => true,    // show (filtered) text/html attachments (and oEmbed HTML etc.). Doesn't affect AJAX calls.
                  'show_thumbs'   => true,    // show thumbnails in notice lists for uploaded images, and photos and videos linked remotely that provide oEmbed info
                  'process_links' => true,    // check linked resources for embeddable photos and videos; this will hit referenced external web sites when processing new messages.
                  'ext_blacklist' => [],
                  'filename'      => 'hash',
                  'memory_limit'  => '1024M', // PHP memory limit to use temporarily when handling images
                 ],
             'thumbnail' =>
                 ['server'       => null,
                  'url_base'     => '/thumb/',
                  'ssl'          => null,
                  'dir'          => INSTALLDIR . '/file/thumbnails/',  // falls back to File::path('thumb') (equivalent to ['attachments']['dir'] .  '/thumb/')
                  'crop'         => false, // overridden to true if thumb height === null
                  'max_size_px'  => 1000,  // thumbs with an edge larger than this will not be generated
                  'width'        => 450,
                  'height'       => 600,
                  'upscale'      => false,
                  'animated'     => false, // null="UseFileAsThumbnail", false="can use still frame". true="allow animated"
                 ],
             'theme' =>
                 ['server'   => null,
                  'url_base' => '/theme/',
                  'ssl'      => null,
                  'dir'      => INSTALLDIR . '/public/theme/'
                 ],
             'plugins' =>
                 ['server'      => null,
                  'url_base'    => null,
                  'ssl'         => null,
                  'core'        => [],
                  'default'     => [],
                  'locale_path' => null, // Set to a path to use *instead of* each plugin's own locale subdirectories
                 ],
             'license' =>
                 ['type'  => 'cc',            // can be 'cc', 'allrightsreserved', 'private'
                  'owner' => null,  // can be name of content owner e.g. for enterprise
                  'url'   => 'https://creativecommons.org/licenses/by/4.0/',
                  'title' => 'Creative Commons Attribution 4.0',
                  'image' => '/theme/licenses/cc_by_4.0.png',
                 ],
             'mail' =>
                 ['backend'      => 'mail',
                  'params'       => null,
                  'domain_check' => true,
                 ],
             'nickname' =>
                 ['blacklist' => ['doc', 'main', 'avatar', 'theme'],
                  'featured'  => [],
                 ],
             'profile' =>
                 ['banned'               => [],
                  'bio_text_limit'       => null,
                  'allow_nick_change'    => false,
                  'allow_private_stream' => true,  // whether to allow setting stream to private ("only followers can read")
                  'backup'               => false, // can cause DoS, so should be done via CLI
                  'restore'              => false,
                  'delete'               => false,
                  'move'                 => false,
                 ],
             'image'  => ['jpegquality' => 85],
             'theme_upload' =>
                 ['enabled' => true,
                  'formats' => ['zip', 'tar', 'gz', 'tar.gz']],
             'foaf' => ['mbox_sha1sum' => false],
             'public' =>
                 ['local_only'       => false,
                  'blacklist'        => [],
                  'exclude_sources'  => [],
                 ],
             'throttle' =>
                 ['enabled'  => true, // whether to throttle posting dents
                  'count'    => 20,    // number of allowed messages in timespan
                  'timespan' => 600,   // timespan for throttling
                 ],
             'invite' => ['enabled' => true],
             'tag' =>
                 ['dropoff' => 86400 * 10, // controls weighting based on age
                  'cutoff'  => 86400 * 90, // only look at notices posted in last 90 days
                 ],
             'popular' =>
                 ['dropoff' => 86400 * 10, // controls weighting based on age
                  'cutoff'  => 86400 * 90, // only look at notices favorited in last 90 days
                 ],
             'daemon' =>
                 ['piddir' => sys_get_temp_dir(),
                  'user'   => false,
                  'group'  => false,
                 ],
             'ping'  =>
                 ['notify'  => [],
                  'timeout' => 2,
                 ],
             'new_users' =>
                ['default_subscriptions' => null,
                 'welcome_user'          => null,
                ],
             'linkify' => // "bare" below means "without schema", like domain.com vs. https://domain.com
                 ['bare_domains' => false, // convert domain.com to <a href="http://domain.com/" ...>domain.com</a> ?
                  'linkify_ipv4' => false, // convert IPv4 addresses to hyperlinks?
                  'linkify_ipv6' => false, // convert IPv6 addresses to hyperlinks?
                 ],
             'group' =>
                 ['max_aliases'       => 3,
                  'description_limit' => null,
                 ],
             'people_tag' =>
                 ['max_tags'          => 100,             // maximum number of tags a user can create.
                  'max_people'        => 500,             // maximum no. of people with the same tag by the same user
                  'allow_tagging'     => ['local' => true, 'remote' => true], // equivalent to array()
                  'description_limit' => null,
                 ],
             'search' => ['type' => 'like'],
             'html_filter' => ['tags' => ['img', 'video', 'audio', 'script']],
             'notice' =>
                 ['content_limit' => null,
                  'allow_private' => false, // whether to allow users to "check the padlock" to publish notices available for their subscribers.
                  'hide_banned'   => true,  // Whether to hide silenced users from timelines
                 ],
             'message'  => ['content_limit' => null],
             'location' => ['share'         => 'user'],
             'admin' => ['panels' => ['site', 'user', 'paths', 'access', 'sessions', 'sitenotice', 'license', 'plugins']],
             'single_user' =>
                 ['enabled'  => $_ENV['SOCIAL_SITE_PROFILE'] ?? '' == 'single_user',
                  'nickname' => null,
                 ],
             'robots_txt' =>
                 ['crawl_delay' => 0,
                  'disallow'    => ['main', 'settings', 'admin', 'search', 'message'],
                 ],
             'nofollow' =>
                 ['subscribers' => true,
                  'members'     => true,
                  'peopletag'   => true,
                  'external'    => 'sometimes', // Options: 'sometimes', 'never', default = 'sometimes'
                 ],
             'url_shortener' =>
                 ['service'            => 'internal',
                  'max_url_length'     => 100,
                  'max_notice_length'  => null,
                 ],
             'http' => // HTTP client settings when contacting other sites
                ['ssl_ca_file'       => '/docker/certbot/files/live/',
                 'timeout'           => (int) (ini_get('default_socket_timeout')),   // effectively should be this by default already, but this makes it more explicitly configurable for you users .)
                 'proxy_host'        => null,
                 'proxy_port'        => null,
                 'proxy_user'        => null,
                 'proxy_password'    => null,
                 'proxy_auth_scheme' => null,
                ],
             'discovery'     => ['CORS'  => false], // Allow Cross-Origin Resource Sharing for service discovery (host-meta, XRD, etc.)
             'performance'   => ['high'  => false], // disable some features for higher performance; default false
             'login_command' => ['enabled' => false],
            ];

        self::loadDefaults(!$_ENV['APP_DEBUG']);
    }

    public static function loadDefaults(bool $optimize = false)
    {
        if (!isset($_ENV['HTTPS']) || !isset($_ENV['HTTP_HOST']) || $optimize) {
            return;
        }

        // So, since not all DBMSs support multi row inserts, doctrine doesn't implement it.
        // The difference between this and the below version is that the one bellow does 221 queries in 30 to 50ms,
        // this does 2 in 10 to 15 ms
        // In debug mode, delete everything and reinsert, in case defaults changed
        DB::getConnection()->executeQuery('delete from config;');
        $sql = 'insert into config (section, setting, value) values';
        foreach (self::$defaults as $section => $def) {
            foreach ($def as $setting => $value) {
                $v = serialize($value);
                $sql .= " ('{$section}', '{$setting}', '{$v}'),";
            }
        }
        $sql = preg_replace('/,$/', ';', $sql);
        DB::getConnection()->executeQuery($sql);

        // $repo = DB::getRepository('\App\Entity\Config');
        // $repo->findAll();
        // foreach (self::$defaults as $section => $def) {
        //     foreach ($def as $setting => $value) {
        //         DB::persist(new Config($section, $setting, serialize($value)));
        //     }
        // }
        // DB::flush();
    }
}
