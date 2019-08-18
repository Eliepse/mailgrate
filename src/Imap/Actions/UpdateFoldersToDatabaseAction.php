<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use Eliepse\Imap\AccountPasswordManager;
use Eliepse\Imap\Utils;

class UpdateFoldersToDatabaseAction
{

    /**
     * @var Account
     */
    private $account;


    public function __construct(Account $account)
    {
        $this->account = app(AccountPasswordManager::class)->get($account->id);
    }


    /**
     * @param array $imapFolders
     *
     * @return array Statistics of the task (added, deleted, total)
     */
    public function __invoke(array $imapFolders): array
    {
        $folders = array_map(function ($mailbox) {
            $f = new Folder([
                'name' => Utils::cleanMailboxName($mailbox->name, $this->account),
                'attributes' => $mailbox->attributes,
            ]);

            $f->account()->associate($this->account);

            return $f;
        }, $imapFolders);

        $folders = collect($folders);

        $foldersToAdd = collect();
        $foldersToDelete = collect();

        foreach ($folders as $imapFolder) {
            $match = false;

            foreach ($this->account->folders as $dbFolder) {
                if ($dbFolder->name === $imapFolder->name) {
                    $match = true;
                    break;
                }
            }

            if (!$match)
                $foldersToAdd->push($imapFolder);
        }

        foreach ($this->account->folders as $dbFolder) {
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

        $this->account->folders()->whereIn('id', $foldersToDelete->pluck('id'))->delete();
        $this->account->folders()->saveMany($foldersToAdd);

        return [
            'added' => count($foldersToAdd),
            'deleted' => count($foldersToDelete),
            'total' => count($imapFolders),
        ];
    }
}
