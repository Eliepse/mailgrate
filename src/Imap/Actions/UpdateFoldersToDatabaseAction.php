<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use Illuminate\Support\Arr;

class UpdateFoldersToDatabaseAction
{
    /**
     * @param Account $account
     * @param array $imapFolders
     *
     * @return array Statistics the task (added, deleted, total)
     */
    public function __invoke(Account $account, array $imapFolders): array
    {
        $imapFolders = array_map(function ($mailbox) use ($account) {
            $f = new Folder([
                'name' => Utils::cleanMailboxName($mailbox->name, $account),
                'attributes' => $mailbox->attributes,
            ]);
            $f->account()->associate($account);

            return $f;
        }, $imapFolders);

        $newFolders = array_udiff($imapFolders, $account->folders->toArray(), $this->diffFolders());
        $deletedFolders = array_udiff($account->folders->toArray(), $imapFolders, $this->diffFolders());

        $account->folders()->whereIn('id', Arr::pluck($deletedFolders, 'id'))->delete();
        $account->folders()->saveMany($newFolders);

        return [
            'added' => count($newFolders),
            'deleted' => count($deletedFolders),
            'total' => count($imapFolders),
        ];
    }


    /**
     * Compare function for arrays of folders to use with array_udiff
     */
    private function diffFolders(): callable
    {
        return function (Folder $a, Folder $b): bool {
            return $a->name === $b->name;
        };
    }
}
