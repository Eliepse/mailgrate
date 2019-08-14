<?php


namespace Eliepse\Imap;


use App\Account;

class FetchAccountFolders
{
    public function __invoke(Account $account): array
    {
        $stream = $account->connect();
        // TODO(eliepse): handle root
        $mailboxes = imap_getmailboxes($stream, $account->host, '*');
        imap_close($stream);

        return $mailboxes;
    }
}
