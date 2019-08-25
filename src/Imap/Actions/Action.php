<?php


namespace Eliepse\Imap\Actions;


use Eliepse\Runtimer;
use Illuminate\Console\OutputStyle;

class Action
{
    /**
     * @var OutputStyle
     */
    protected $output;

    /**
     * @var Runtimer
     */
    public $timer;


    public function __construct(OutputStyle $output)
    {
        $this->output = $output;
        $this->timer = new Runtimer();
    }
}
