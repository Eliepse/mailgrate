<?php

namespace App\Commands;

use App\Account;
use Eliepse\Console\Component\AccountSelection;
use Eliepse\Imap\Utils;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use stdClass;

class UpdateAccountMetadataCommand extends Command
{
    use AccountSelection;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'account:update';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Update metadata of an account';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $accounts = Account::all();

        $account = $this->selectAccountWithPassword($accounts);
        $account->load(['folders.mails']);
        $stream = $account->connect();

        $this->info("Getting mailboxes list...");

        // TODO(eliepse): handle root
        $mailboxes = imap_getmailboxes($stream, $account->host, '*');
        imap_close($stream);

        $this->info("Updating mailboxes metadata...");

        /** @var stdClass $mailbox */
        foreach ($mailboxes as $mailbox) {
            $name = Utils::imapUtf7ToUtf8($mailbox->name);
            $name = Utils::toRFC2683Delimiter($name, $account->delimiter);
            $name = substr($name, strlen($account->host));

            if ($account->folders->firstWhere('name', $name))
                continue;

            // TODO(eliepse): manage deleted folder (delete them if not present in the list)

            // TODO(eliepse): optimize requests with createMany()
            $account->folders()->create([
                'name' => $name,
                'attributes' => $mailbox->attributes,
            ]);
        }

        $this->info("Update done.");

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
