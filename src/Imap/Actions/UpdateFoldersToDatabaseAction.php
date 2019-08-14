<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use Eliepse\Imap\Utils;
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
        $folders = array_map(function ($mailbox) use ($account) {
            $f = new Folder([
                'name' => Utils::cleanMailboxName($mailbox->name, $account),
                'attributes' => $mailbox->attributes,
            ]);

            $f->account()->associate($account);

            return $f;
        }, $imapFolders);

        $folders = collect($folders);

        $newFolders = $folders->diffUsing($account->folders, $this->diffFolders());
        $deletedFolders = $account->folders->diffUsing($folders, $this->diffFolders());

        $account->folders()->whereIn('id', $deletedFolders->pluck('id'))->delete();
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
        return function ($a, $b): bool {
            if (is_array($a) || is_array($b))
                dd($a, $b);

            return $a->name === $b->name;
        };
    }
}
