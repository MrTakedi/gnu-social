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

defined('GNUSOCIAL') || die();

/**
 * Version info page
 *
 * A page that shows version information for this site. Helpful for
 * debugging, for giving credit to authors, and for linking to more
 * complete documentation for admins.
 *
 * @category Info
 * @package  GNUsocial
 * @author   Evan Prodromou <evan@status.net>
 * @author   Craig Andrews <candrews@integralblue.com>
 * @copyright 2009-2011 Free Software Foundation, Inc http://www.fsf.org
 * @license  http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPLv3
 * @link     http://status.net/
 */
class VersionAction extends Action
{
    public $pluginVersions = [];

    /**
     * Return true since we're read-only.
     *
     * @param array $args other arguments
     *
     * @return bool is read only action?
     */
    public function isReadOnly($args)
    {
        return true;
    }

    /**
     * Returns the page title
     *
     * @return string page title
     */
    public function title()
    {
        // TRANS: Title for version page. %1$s is the engine name, %2$s is the engine version.
        return sprintf(_('%1$s %2$s'), GNUSOCIAL_ENGINE, GNUSOCIAL_VERSION);
    }

    /**
     * Prepare to run
     *
     * Fire off an event to let plugins report their
     * versions.
     *
     * @param array $args array misc. arguments
     *
     * @return bool true
     * @throws ClientException
     */
    protected function prepare(array $args = [])
    {
        parent::prepare($args);

        $this->pluginVersions = PluginList::getActivePluginVersions();

        return true;
    }

    /**
     * Execute the action
     *
     * Shows a page with the version information in the
     * content area.
     *
     * @return void
     * @throws ClientException
     * @throws ReflectionException
     * @throws ServerException
     */
    protected function handle()
    {
        parent::handle();
        $this->showPage();
    }


    /*
    * Override to add h-entry, and content-inner classes
    *
    * @return void
    */
    public function showContentBlock()
    {
        $this->elementStart('div', ['id' => 'content', 'class' => 'h-entry']);
        $this->showPageTitle();
        $this->showPageNoticeBlock();
        $this->elementStart('div', ['id' => 'content_inner',
                                         'class' => 'e-content']);
        // show the actual content (forms, lists, whatever)
        $this->showContent();
        $this->elementEnd('div');
        $this->elementEnd('div');
    }

    /*
    * Overrride to add entry-title class
    *
    * @return void
    */
    public function showPageTitle()
    {
        $this->element('h1', ['class' => 'entry-title'], $this->title());
    }


    /**
     * Show version information
     *
     * @return void
     * @throws Exception
     */
    public function showContent()
    {
        $this->elementStart('p');

        // TRANS: Content part of engine version page.
        // TRANS: %1$s is the engine name (GNU social) and %2$s is the GNU social version.
        $this->raw(sprintf(
            _('This site is powered by %1$s version %2$s, ' .
            'Copyright 2010 Free Software Foundation, Inc.'),
            XMLStringer::estring(
                'a',
                ['href' => GNUSOCIAL_ENGINE_URL],
                // TRANS: Engine name.
                GNUSOCIAL_ENGINE
            ),
            GNUSOCIAL_VERSION
        ));
        $this->elementEnd('p');

        // TRANS: Header for engine software contributors section on the version page.
        $this->element('h2', null, _('Contributors'));

        $this->elementStart('p');
        $this->raw(sprintf(
            'See %s for a full list of contributors.',
            XMLStringer::estring(
                'a',
                ['href' => 'https://notabug.org/diogo/gnu-social/src/nightly/CREDITS.md'],
                'https://notabug.org/diogo/gnu-social/src/nightly/CREDITS.md'
            )
        ));
        $this->elementEnd('p');

        // TRANS: Header for engine software license section on the version page.
        $this->element('h2', null, _('License'));

        $this->element(
            'p',
            null,
            // TRANS: Content part of engine software version page. %1s is engine name
            sprintf(_('%1$s is free software: you can redistribute it and/or modify ' .
                'it under the terms of the GNU Affero General Public License as published by ' .
                'the Free Software Foundation, either version 3 of the License, or ' .
                '(at your option) any later version.'), GNUSOCIAL_ENGINE)
        );

        $this->element(
            'p',
            null,
            // TRANS: Content part of engine software version page.
            _('This program is distributed in the hope that it will be useful, ' .
                'but WITHOUT ANY WARRANTY; without even the implied warranty of ' .
                'MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the ' .
                'GNU Affero General Public License for more details.')
        );

        $this->elementStart('p');
        // TRANS: Content part of engine version page.
        // TRANS: %s is a link to the AGPL license with link description "http://www.gnu.org/licenses/agpl.html".
        $this->raw(sprintf(
            _('You should have received a copy of the GNU Affero General Public License ' .
            'along with this program.  If not, see %s.'),
            XMLStringer::estring(
                'a',
                ['href' => 'https://www.gnu.org/licenses/agpl.html'],
                'https://www.gnu.org/licenses/agpl.html'
            )
        ));
        $this->elementEnd('p');

        // XXX: Theme information?

        if (count($this->pluginVersions)) {
            // TRANS: Header for engine plugins section on the version page.
            $this->element('h2', null, _('Plugins'));

            $this->elementStart('table', ['id' => 'plugins_enabled']);

            $this->elementStart('thead');
            $this->elementStart('tr');
            // TRANS: Column header for plugins table on version page.
            $this->element('th', ['id' => 'plugin_name'], _m('HEADER', 'Name'));
            // TRANS: Column header for plugins table on version page.
            $this->element('th', ['id' => 'plugin_version'], _m('HEADER', 'Version'));
            // TRANS: Column header for plugins table on version page.
            $this->element('th', ['id' => 'plugin_authors'], _m('HEADER', 'Author(s)'));
            // TRANS: Column header for plugins table on version page.
            $this->element('th', ['id' => 'plugin_description'], _m('HEADER', 'Description'));
            $this->elementEnd('tr');
            $this->elementEnd('thead');

            $this->elementStart('tbody');
            foreach ($this->pluginVersions as $plugin) {
                $this->elementStart('tr');
                if (array_key_exists('homepage', $plugin)) {
                    $this->elementStart('th');
                    $this->element(
                        'a',
                        ['href' => $plugin['homepage']],
                        $plugin['name']
                    );
                    $this->elementEnd('th');
                } else {
                    $this->element('th', null, $plugin['name']);
                }

                $this->element('td', null, $plugin['version']);

                if (array_key_exists('author', $plugin)) {
                    $this->element('td', null, $plugin['author']);
                }

                if (array_key_exists('rawdescription', $plugin)) {
                    $this->elementStart('td');
                    $this->raw($plugin['rawdescription']);
                    $this->elementEnd('td');
                } elseif (array_key_exists('description', $plugin)) {
                    $this->element('td', null, $plugin['description']);
                }
                $this->elementEnd('tr');
            }
            $this->elementEnd('tbody');
            $this->elementEnd('table');
        }
    }
}
