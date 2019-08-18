<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use Eliepse\Imap\Utils;
use ErrorException;

class FetchAccountFoldersAction
{
    /**
     * @param Account $account
     *
     * @return array
     * @throws ErrorException
     */
    public function __invoke(Account $account): array
    {
        $stream = $account->connectWithoutRoot();
        $pattern = Utils::toCustomDelimiter(
            $account->root . Utils::IMAP_DELIMITER . '%',
            $account->delimiter);

        $mailboxes = imap_getmailboxes($stream, $account->host, $account->root ? $pattern : '*');

        imap_close($stream);

        if (!is_array($mailboxes))
            throw new ErrorException("Error on fetching mailboxes list.");

        return $mailboxes;
    }
}
