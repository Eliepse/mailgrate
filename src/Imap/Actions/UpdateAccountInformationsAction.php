<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use Eliepse\Runtimer;
use ErrorException;

class UpdateAccountInformationsAction
{
    use AccountManagement;

    /**
     * @var Account
     */
    private $account;

    /**
     * @var Runtimer
     */
    public $timer;

    /**
     * @var callable
     */
    public $callback;


    public function __construct(int $account_id)
    {
        $this->timer = new Runtimer();
        $this->account = $this->getAccountFromId($account_id);
    }


    /**
     * @throws ErrorException
     */
    public function __invoke()
    {
        $this->timer->start();

        $mailboxes = (new FetchAccountFoldersAction)($this->account);
        (new UpdateFoldersToDatabaseAction($this->account))($mailboxes);
        $this->account->load(['folders.mails']);

        /** @var Folder $folder */
        foreach ($this->account->folders as $key => $folder) {
            (new UpdateFolderMailsToDatabaseAction)($this->account, $folder);
        }

        $this->timer->stop();
    }
}
