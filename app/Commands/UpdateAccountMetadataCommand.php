<?php

namespace App\Commands;

use App\Account;
use App\Folder;
use App\Mail;
use Eliepse\Console\Component\AccountSelection;
use Eliepse\Imap\FetchAccountFolders;
use Eliepse\Imap\UpdateFoldersToDatabase;
use Eliepse\Imap\Utils;
use Eliepse\Runtimer;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

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
        $mainRuntimer = new Runtimer(true);


        /* * * * * * * * * * * *
         * Fetch mailbox list  *
         * * * * * * * * * * * */

        $localRuntimer = new Runtimer(true);
        $this->comment("Fetching mailboxes list...");
        $mailboxes = (new FetchAccountFolders)($account);
        $this->comment($localRuntimer);


        /* * * * * * * * * * * *
         * Update folders list *
         * * * * * * * * * * * */

        $this->comment("Updating mailboxes metadata...");
        $localRuntimer->reset();
        (new UpdateFoldersToDatabase)($account, $mailboxes);
        $account->load(['folders.mails']); // We have to load relations because list might have changed
        $this->comment($localRuntimer);
        $localRuntimer->reset();

        /* * * * * * * * * * * *
         * Update mails lists  *
         * * * * * * * * * * * */

        $this->comment("Fetching mails lists...");

        $gMails = 0;
        $gNewMails = 0;
        $gDeletedMails = 0;

        /** @var Folder $folder */
        foreach ($account->folders as $key => $folder) {
            $this->info("Updating(" . ($key + 1) . "/{$account->folders->count()}): {$folder->name}");

            $stream = $account->connect($folder);
            $mailsCount = imap_check($stream)->Nmsgs;
            $imapMails = $mailsCount > 0 ? collect(imap_fetch_overview($stream, "1:$mailsCount")) : collect();
            imap_close($stream);

            // Map imap mails to Mail objects
            $imapMails = $imapMails->map(function ($mail) {
                return new Mail([
                    'subject' => Str::limit(iconv_mime_decode($mail->subject ?? '', ICONV_MIME_DECODE_CONTINUE_ON_ERROR), 200),
                    'uid' => $mail->uid,
                ]);
            });

            // TODO(eliepse): try to optimize preparation

            $newMails = $imapMails->diffUsing($folder->mails, $this->diffMails());
            $deletedMails = $folder->mails->diffUsing($imapMails, $this->diffMails());

            $folder->mails()->whereIn('uid', $deletedMails->pluck('uid'))->delete();
            $folder->mails()->insert($imapMails->map(function ($mail) use ($folder) {
                return [
                    'uid' => $mail->uid,
                    'subject' => $mail->subject,
                    'folder_id' => $folder->id,
                ];
            })->toArray());

            $gNewMails += $newMails->count();
            $gDeletedMails += $deletedMails->count();
            $gMails += $mailsCount;

            $this->info("\t{$mailsCount} mails ({$newMails->count()} added, {$deletedMails->count()} deleted)");
        }

        $localRuntimer->stop();
        $this->comment($localRuntimer);
        $this->info("Total: $gMails (+$gNewMails, -$gDeletedMails).");
        $this->line("");
        $this->info("Update done.");
        $this->comment("Executed in $mainRuntimer");

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


    /**
     * Compare function for arrays of mails to use with array_udiff
     */
    private function diffMails(): callable
    {
        return function (Mail $a, Mail $b): bool {
            return $a->uid === $b->uid;
        };
    }
}
