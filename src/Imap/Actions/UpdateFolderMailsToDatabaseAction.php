<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use App\Mail;
use Eliepse\Imap\Utils;
use ErrorException;
use Illuminate\Console\OutputStyle;

class UpdateFolderMailsToDatabaseAction extends Action
{
    /**
     * @var
     */
    public $stats;


    public function __construct(OutputStyle $output)
    {
        parent::__construct($output);

        $this->stats = [
            'added' => 0,
            'deleted' => 0,
            'total' => 0,
        ];
    }


    /**
     * @param Account $account
     * @param Folder $folder
     *
     * @return array Return statistics (added, deleted, total)
     * @throws ErrorException Throw an exception if the given folder does not belongs to the given account
     */
    public function __invoke(Account $account, Folder $folder): array
    {
        if ($account->isNot($folder->account))
            throw new ErrorException("The folder does not belongs to this account.");

        $stream = $account->connect($folder);
        $totalMails = imap_check($stream)->Nmsgs;
        $imapMails = $totalMails > 0 ? collect(imap_fetch_overview($stream, "1:$totalMails")) : collect();
        imap_close($stream);

        $this->stats['total'] = $imapMails->count();

        // Map imap mails to Mail objects
        $imapMails = $imapMails->map(function ($mail) use ($folder) {
            $m = new Mail([
                'subject' => Utils::convertMailSubject($mail->subject ?? ''),
                'uid' => $mail->uid,
            ]);

            $m->folder()->associate($folder);

            return $m;
        });

        // TODO(eliepse): try to optimize preparation

        $newMails = $imapMails->diffUsing($folder->mails, $this->diffMails());
        $deletedMails = $folder->mails->diffUsing($imapMails, $this->diffMails());

        $this->stats['added'] = $newMails->count();
        $this->stats['deleted'] = $deletedMails->count();

        $folder->mails()->whereIn('uid', $deletedMails->pluck('uid'))->delete();
        $folder->mails()->insert($newMails->map(function (Mail $mail) use ($folder) {
            return $mail->attributesToArray();
        })->toArray());

        return $this->stats;
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
