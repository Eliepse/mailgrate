<?php

namespace App\Commands;

use App\Account;
use App\Folder;
use App\Mail;
use Eliepse\Console\Component\AccountSelection;
use Eliepse\Imap\Utils;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use stdClass;
use function foo\func;

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
        $stream = $account->connect();

        $this->comment("Fetching mailboxes list...");

        // TODO(eliepse): handle root
        $mailboxes = imap_getmailboxes($stream, $account->host, '*');
        imap_close($stream);

        $this->comment("Updating mailboxes metadata...");

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

        $this->comment("Fetching mails lists...");

        /** @var Folder $folder */
        foreach ($account->folders as $folder) {
            $this->info("Updating: {$folder->name}");

            $stream = $account->connect($folder);
            $mailsCount = imap_check($stream)->Nmsgs;
            $mails = $mailsCount > 0 ? collect(imap_fetch_overview($stream, "1:$mailsCount")) : collect();
            imap_close($stream);

            $mails->transform(function ($mail) {
                return new Mail([
                    'subject' => Str::limit(
                        imap_utf8($mail->subject ?? ''), 250),
                    'uid' => $mail->uid,
                ]);
            });

            $newMails = $mails->diffUsing($folder->mails,
                function (Mail $imapMail, Mail $dbMail) {
                    return $imapMail->uid === $dbMail->uid;
                });

            $deletedMails = $folder->mails->diffUsing($mails,
                function ($dbMail, $imapMail) {
                    return $dbMail->uid === $imapMail->uid;
                });

            $folder->mails()->whereIn('uid', $deletedMails->pluck('id'))->delete();
            $folder->mails()->saveMany($newMails);

            $this->info("\t{$folder->mails()->count()} mails ({$newMails->count()} added, {$deletedMails->count()} deleted)");
        }

        // TODO(eliepse): print global stats and timer

        $this->info("Update done.");

        return;
    }


    /**
     * Define the command's schedule .
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
