<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use App\Mail;
use App\Transfert;
use Eliepse\Imap\AccountPasswordManager;
use Eliepse\Imap\Utils;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class CopyFolderMailsToAccountAction
{
    /**
     * @var Account
     */
    private $origin;

    /**
     * @var Account
     */
    private $destin;

    /**
     * @var array
     */
    public $stats = [];


    public function __construct(Account $origin, Account $destination)
    {
        /** @var AccountPasswordManager $manager */
        $manager = app(AccountPasswordManager::class);
        $this->origin = $manager->get($origin->id);
        $this->destin = $manager->get($destination->id);
        $this->stats = [
            'success' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];
    }


    public function __invoke(Folder $from, Folder $to, Command $command)
    {
        /** @var Collection $transferts */
//        $transferts = Transfert::query()
//            ->with(['mail'])
//            ->whereIn('mail_id', $from->mails->pluck('id'))
//            ->where('destination_account_id', $to->account->id)
//            ->select(['id', 'mail_id', 'destination_account_id', 'status'])
//            ->get();

        // Prepare mails to transfert (exclude already transfered ones)
        $mailToProcess = $from->mails
            ->load(['transferts'])
            ->filter(function (Mail $mail) {
                /** @var Transfert $transfert */
                $transfert = $mail->transferts
                    ->first(function (Transfert $t) {
                        return $t->destination_account_id === $this->destin->id;
                    });

                // Exclude only if the transfert has been tried and succeed
                return isset($transfert) ? !$transfert->isSucess() : true;
            });

        // TODO(eliepse): make clean stats (including skipped)

        $streamFrom = $this->origin->connect($from);
        $streamTo = $this->destin->connect($to);

        $this->stats['total'] = $from->mails->count();
        $this->stats['skipped'] = $from->mails->count() - $mailToProcess->count();

        /** @var Mail $mail */
        foreach ($mailToProcess as $key => $mail) {
            $command->comment("Processing $key/{$mailToProcess->count()}");

            // Download the email
            $body = imap_body($streamFrom, $mail->uid, FT_UID | FT_PEEK);

            $transfert = $mail->transferts->firstWhere('destination_account_id', $this->destin->id) ?? new Transfert();

            $transfert->mail()->associate($mail);
            $transfert->destination()->associate($this->destin);

            // Upload the email
            if (imap_append($streamTo, Utils::uncleanMailboxName($to->name, $to->account), $body)) {
                $this->stats['success']++;
                $transfert->status = Transfert::STATUS_SUCCESS;
                $transfert->message = '';
            } else {
                $this->stats['failed']++;
                $transfert->status = Transfert::STATUS_FAILED;
                $transfert->message = imap_last_error();
            }

            $transfert->save();
        }

        imap_close($streamFrom);
        imap_close($streamTo);

        return $this->stats;
    }
}
