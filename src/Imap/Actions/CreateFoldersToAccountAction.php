<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use ArrayAccess;
use Eliepse\Imap\Utils;

class CreateFoldersToAccountAction
{
    /**
     * @param Account $account Account where to create mailboxes.
     * @param ArrayAccess $mailboxes Array of new Folders to create on the targeted Account.
     */
    public function __invoke(Account $account, ArrayAccess $mailboxes)
    {
        $stream = $account->connectWithoutRoot();

        /** @var Folder $mailbox */
        foreach ($mailboxes as $mailbox) {
            if (!is_a($mailbox, Folder::class))
                continue;

            $path = ($account->root ? $account->root . Utils::IMAP_DELIMITER : '') . $mailbox->nameWithoutRoot;

            if (imap_createmailbox($stream, Utils::uncleanMailboxName($path, $account))) {
                $mailbox->account()->associate($account);
                $mailbox->save();
            }
        }

        imap_close($stream);
    }
}
