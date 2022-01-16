<?php

namespace App\Commands;

use App\Account;
use App\Folder;
use Eliepse\Console\Component\AccountSelection;
use Eliepse\Imap\Actions\CopyFolderMailsToAccountAction;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class SynchronizeMailsCommand extends Command
{
    use AccountSelection;

    /**
     * @var Account
     */
    private $from;
    /**
     * @var Account
     */
    private $to;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'sync:mails';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Synchronize mails between accounts (no folders update)';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $accounts = Account::all();
        $this->from = $this->selectAccountWithPassword($accounts);
        $this->to = $this->selectAccountWithPassword($accounts->whereNotIn('id', $this->from->id));

        /** @var Folder $folder */
        foreach ($this->from->folders as $folder) {
            $destFolder = $this->to->folders->firstWhere('nameWithoutRoot', $folder->nameWithoutRoot);

            if (!$destFolder) {
                $this->error("$folder->nameWithoutRoot does not have a valid destination.");
                continue;
            }

            $stats = (new CopyFolderMailsToAccountAction($this->output, $this->from, $this->to))($folder, $destFolder);

            $this->info("$folder->nameWithoutRoot: \n\t{$stats['total']} mails ({$stats['success']} ok, {$stats['failed']} failed, {$stats['skipped']} skipped)");
        };

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
