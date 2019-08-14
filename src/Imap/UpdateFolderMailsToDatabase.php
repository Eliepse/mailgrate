<?php


namespace Eliepse\Imap;


use App\Folder;
use App\Mail;

class UpdateFolderMailsToDatabase
{

    /**
     * @param Folder $folder
     *
     * @return array Return statistics (added, deleted, total)
     */
    public function __invoke(Folder $folder): array
    {
        $stream = $folder->account->connect($folder);
        $totalMails = imap_check($stream)->Nmsgs;
        $imapMails = $totalMails > 0 ? collect(imap_fetch_overview($stream, "1:$totalMails")) : collect();
        imap_close($stream);

        // Map imap mails to Mail objects
        $imapMails = $imapMails->map(function ($mail) {
            return new Mail([
                'subject' => iconv_mime_decode($mail->subject ?? '', ICONV_MIME_DECODE_CONTINUE_ON_ERROR),
                'uid' => $mail->uid,
            ]);
        });

        // TODO(eliepse): try to optimize preparation

        $newMails = $imapMails->diffUsing($folder->mails, $this->diffMails());
        $deletedMails = $folder->mails->diffUsing($imapMails, $this->diffMails());

        $folder->mails()->whereIn('uid', $deletedMails->pluck('uid'))->delete();
        $folder->mails()->insert($newMails->map(function ($mail) use ($folder) {
            return [
                'uid' => $mail->uid,
                'subject' => $mail->subject,
                'folder_id' => $folder->id,
            ];
        })->toArray());

        return [
            'added' => $newMails->count(),
            'deleted' => $deletedMails->count(),
            'total' => $imapMails->count(),
        ];
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
