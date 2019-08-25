<?php

namespace App\Commands;

use App\Account;
use Eliepse\Console\Component\AccountSelection;
use Eliepse\Imap\Actions\UpdateAccountInformationsAction;
use Eliepse\Runtimer;
use ErrorException;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class SynchronizeCommand extends Command
{
    use AccountSelection;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'sync';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Interactive command to synchronize an accounts to another';

    /**
     * @var Account
     */
    private $from;

    /**
     * @var Account
     */
    private $to;

    /**
     * @var Runtimer
     */
    private $timer;


    public function __construct()
    {
        parent::__construct();

        $this->timer = new Runtimer();
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws ErrorException
     */
    public function handle()
    {
        $accounts = Account::all();

        $this->question("Select the account that will be the source:");
        $this->from = $this->selectAccountWithPassword($accounts);

        $this->question("Select the account that will be the destination:");
        $this->to = $this->selectAccountWithPassword($accounts->whereNotInStrict('id', $this->from->id));

        $this->timer->start();

        $this->comment("Updating source account informations...");
        (new UpdateAccountInformationsAction($this->from->id))();

        $this->comment("Updating destination account informations...");
        (new UpdateAccountInformationsAction($this->to->id))();

        $this->from->load(['folders.mails']);
        $this->to->load(['folders.mails']);

        $this->table(
            ['Source account', 'Destination account'],
            [
                [$this->from->folders->count() . ' folders', $this->to->folders->count() . ' folders'],
                [$this->from->mailCount() . ' mails', $this->to->mailCount() . ' mails'],
            ]);


        $this->timer->stop();

        $this->comment("Command executed in $this->timer");

//        imap_flush_errors();

        return;
    }


    /**
     * Define the command's schedule.
     *
     * @param Schedule $schedule
     *
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
