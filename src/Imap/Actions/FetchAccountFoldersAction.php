<?php


namespace Eliepse\Imap\Actions;


use App\Account;

class FetchAccountFoldersAction
{
    public function __invoke(Account $account): array
    {
        $stream = $account->connectWithoutRoot();
        $prepend = $account->root ? $account->root . $account->delimiter : '';

        $mailboxes = imap_getmailboxes($stream, $account->host, $prepend . '*');

        imap_close($stream);

        return $mailboxes;
    }
}
