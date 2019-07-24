<?php

if (!defined('GNUSOCIAL')) { exit(1); }

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
        return 'form_notice_delete-' . $this->notice->getID();
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
