<?php

if (!defined('GNUSOCIAL')) { exit(1); }

class DeletenoticeForm extends Form
{
    protected $notice = null;

    function __construct(HTMLOutputter $out=null, Notice $notice=null)
    {
        parent::__construct($out);

        $this->notice = $notice;
    }

    function id()
    {
        return 'form_notice_delete-' . $this->notice->getID();
    }

    function formClass()
    {
        return 'form_delete ajax';
    }

    function action()
    {
        return common_local_url('activityverb',
                                ['id' => $this->notice->getID(),
                                 'verb' => ActivityUtils::resolveUri(ActivityVerb::DELETE, true)]);
    }

    function formLegend()
    {
        $this->out->element('legend', null, _('Are you sure you want to delete this notice?'));
    }

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
     * Action elements
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
