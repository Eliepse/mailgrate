<?php

namespace App\Commands;

use App\Account;
use App\Folder;
use Eliepse\Console\Component\AccountSelection;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Helper\TableSeparator;

class ListAccountFoldersCommand extends Command
{
    use AccountSelection;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'account:folders';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List folders of an account.';

    /**
     * @var Account
     */
    private $account;


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->account = $this->selectAccount(Account::all());

        $folders = $this->account->folders;

        if ($folders->isEmpty()) {
            $this->warn("This account does not have folders registered. You might want to update it before?");
        }

				$total = [
					$this->account->folders->count() . " folders",
					$this->account->folders->sum("mails_count") . " mails"
				];

        $this->table(["Name", "Mails"], $folders->map(function (Folder $folder) {
					return [$folder->name, $folder->mails_count];
				})->add(new TableSeparator([]))->add($total));

        return;
    }


    /**
     * Define the command's schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     *
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
