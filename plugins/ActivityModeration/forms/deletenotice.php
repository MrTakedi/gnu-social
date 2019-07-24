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
 * Implementation of the delete form.
 *
 * @package   ActivityModeration
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

defined('GNUSOCIAL') || die();

/**
 * Delete form.
 *
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */
class DeletenoticeForm extends Form
{
    protected $notice = null;

    /**
     * Form constructor
     *
     * @param HTMLOutputter $out html generator
     * @param Notice $notice form's target notice
     */
    function __construct(HTMLOutputter $out = null, Notice $notice = null)
    {
        parent::__construct($out);

        $this->notice = $notice;
    }

    /**
     * Form ID
     *
     * @return string form's ID
     */
    function id(): string
    {
        return 'form_notice_delete-' . $this->notice->getID();
    }

    /**
     * Form class
     *
     * @return string form's class
     */
    function formClass(): string
    {
        return 'form_delete ajax';
    }

    /**
     * Form action
     *
     * @return string URL to post to
     */
    function action(): string
    {
        return common_local_url('activityverb',
                                ['id' => $this->notice->getID(),
                                 'verb' => ActivityUtils::resolveUri(ActivityVerb::DELETE, true)]);
    }

    /**
     * Form legend
     *
     * @return void
     */
    function formLegend()
    {
        $this->out->element('legend', null, _('Are you sure you want to delete this notice?'));
    }

    /**
     * Form data elements
     *
     * @return void
     */
    function formData()
    {
        if (Event::handle('StartDeleteNoticeForm', [$this, $this->notice])) {
            $this->out->hidden('notice-n'.$this->notice->getID(),
                               $this->notice->getID(),
                               'notice');
            Event::handle('EndDeleteNoticeForm', [$this, $this->notice]);
        }
    }

    /**
     * Form action elements
     *
     * @return void
     */
    function formActions()
    {
        $this->out->submit('delete_submit-'.$this->notice->id,
                           // TRANS: Button label on the delete notice form.
                           _m('BUTTON','Yes'), 'submit', null,
                           // TRANS: Submit button title for 'Yes' when deleting a notice.
                           _('Delete this notice.'));
    }
}
