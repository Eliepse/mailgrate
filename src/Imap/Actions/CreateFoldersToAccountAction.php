<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use ArrayAccess;
use Eliepse\Imap\Utils;
use Illuminate\Support\Facades\Log;

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

            Log::debug("Attempt to create mailbox: {$mailbox->name}", [
                'mailbox' => $mailbox->name,
                'to' => $account->id,
            ]);

            if (imap_createmailbox($stream, Utils::uncleanMailboxName($path, $account))) {

                Log::info("Create mailbox: {$mailbox->name}", [
                    'mailbox' => $mailbox->name,
                    'to' => $account->id,
                ]);

                $mailbox->account()->associate($account);
                $mailbox->save();
            } else {

                Log::warning("Failed to create mailbox: {$mailbox->name}", [
                    'error' => imap_last_error(),
                    'mailbox' => $mailbox->name,
                    'to' => $account->id,
                ]);

            }
        }

        imap_close($stream);
    }
}
