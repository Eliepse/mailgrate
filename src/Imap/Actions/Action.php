<?php


namespace Eliepse\Imap\Actions;


use Eliepse\Runtimer;

class Action
{
    /**
     * @var Runtimer
     */
    public $timer;


    public function __construct()
    {
        $this->timer = new Runtimer();
    }
}
