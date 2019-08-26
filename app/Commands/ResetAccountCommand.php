<?php

namespace App\Commands;

use App\Account;
use App\Folder;
use App\Mail;
use App\Transfert;
use Eliepse\Console\Component\AccountSelection;
use LaravelZero\Framework\Commands\Command;

class ResetAccountCommand extends Command
{
    use AccountSelection;

    /**
     * @var Account
     */
    private $account;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'account:reset
                            {--all : reset all account\'s stored data}
                            {--folders : remove account\'s folders, mails and transferts}
                            {--mails : remove account\'s mails and transferts}
                            {--transferts : remove account\'s transferts}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Reset data of an account from the database';


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->account = $this->selectAccount(Account::all());

        $this->account->loadMissing([
            'folders:id,account_id',
            'folders.mails:id,folder_id',
            'folders.mails.transferts:id,mail_id',
        ]);

        if ($this->option('transferts')) {
            $this->deleteTransferts();
        } else if ($this->option('mails')) {
            $this->deleteTransferts();
            $this->deleteMails();
        } else if ($this->option('folders') || $this->option('all')) {
            $this->deleteTransferts();
            $this->deleteMails();
            $this->deleteFolders();
        } else {
            $this->error('No option selected. Please use --help to see available reset options.');
        }

        return;
    }


    private function deleteTransferts(): void
    {
        $this->account->folders->each(function (Folder $folder) {
            Transfert::query()
                ->whereIn('mail_id', $folder->mails->pluck('id'))
                ->delete();
        });
        $this->info("Transferts deleted.");
    }


    private function deleteMails(): void
    {
        Mail::query()
            ->whereIn('folder_id', $this->account->folders->pluck('id'))
            ->delete();
        $this->info("Mails deleted.");
    }


    private function deleteFolders(): void
    {
        $this->account->folders()->delete();
        $this->info("Folders deleted.");
    }


}
