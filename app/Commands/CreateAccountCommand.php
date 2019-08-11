<?php

namespace App\Commands;

use App\Account;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use LaravelZero\Framework\Commands\Command;
use stdClass;

class CreateAccountCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'account:create';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Add a new account to the database';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $account = new Account();

        $account->host = "{" . $this->ask("What is the host domain?");
        $account->host .= ":" . $this->ask("What is the port?", 993) . "/imap";
        $account->host .= "/" . $this->ask("What is the security protocol?", 'ssl') . "}";

        $account->username = $this->ask("What is the username?");

        do {
            if (isset($stream))
                $this->error("Failed to connect, try again.");

            $account->password = $this->secret("Please enter the password:");
            $this->comment("Testing the connection...");
        } while (!$stream = imap_open($account->host, $account->username, $account->password));

        $this->info("Connected!");

        $this->comment("Guessing delimiter...");

        $folders = imap_getmailboxes($stream, $account->host, '*');
        $folder = Arr::first($folders, null, new stdClass());
        $account->delimiter = $folder->delimiter ?? '/';

        $this->comment("Done.");

        $account->root = $this->ask("What is the root folder?");

        $this->comment("Saving account...");
        $account->save();

        $this->info("Account saved!");

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
