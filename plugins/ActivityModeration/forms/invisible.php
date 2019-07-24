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
 * Implementation of the delete after-action form.
 *
 * @package   ActivityModeration
 * @author    Bruno Casteleiro <brunoccast@fc.up.pt>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

namespace ActivityModeration;

defined('GNUSOCIAL') || die();

/**
 * The invisible form, uses John Cena's special
 * "You can't see me" move and succumbs into
 * the darkness.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class InvisibleForm extends Form
{
    protected $notice = null;

    function __construct(HTMLOutputter $out=null, Notice $notice=null)
    {
        parent::__construct($out);

        $this->notice = $notice;
    }

    function id()
    {
        return 'form_invisible-' . $this->notice->getID();
    }

    function formClass()
    {
        return 'ucantseeme';
    }
    
    function formData()
    {
        $this->out->hidden('notice-n'.$this->notice->getID(),
                           $this->notice->getID(),
                           'notice');
    }
}
