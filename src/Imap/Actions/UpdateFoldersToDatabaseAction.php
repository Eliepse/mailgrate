<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use Eliepse\Imap\Utils;

class UpdateFoldersToDatabaseAction
{
    /**
     * @param Account $account
     * @param array $imapFolders
     *
     * @return array Statistics of the task (added, deleted, total)
     */
    public function __invoke(Account $account, array $imapFolders): array
    {
        $folders = array_map(function ($mailbox) use ($account) {
            $f = new Folder([
                'name' => Utils::cleanMailboxName($mailbox->name, $account),
                'attributes' => $mailbox->attributes,
            ]);

            $f->account()->associate($account);

            return $f;
        }, $imapFolders);

        $folders = collect($folders);

        $foldersToAdd = collect();
        $foldersToDelete = collect();

        foreach ($folders as $imapFolder) {
            $match = false;

            foreach ($account->folders as $dbFolder) {
                if ($dbFolder->name === $imapFolder->name) {
                    $match = true;
                    break;
                }
            }

            if (!$match)
                $foldersToAdd->push($imapFolder);
        }

        foreach ($account->folders as $dbFolder) {
            $match = false;

            foreach ($folders as $imapFolder) {
                if ($dbFolder->name === $imapFolder->name) {
                    $match = true;
                    break;
                }
            }

            if (!$match)
                $foldersToDelete->push($dbFolder);
        }

        $account->folders()->whereIn('id', $foldersToDelete->pluck('id'))->delete();
        $account->folders()->saveMany($foldersToAdd);

        return [
            'added' => count($foldersToAdd),
            'deleted' => count($foldersToDelete),
            'total' => count($imapFolders),
        ];
    }
}
