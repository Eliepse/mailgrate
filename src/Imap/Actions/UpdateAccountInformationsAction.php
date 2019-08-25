<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use Eliepse\Runtimer;
use ErrorException;
use Illuminate\Console\OutputStyle;

class UpdateAccountInformationsAction extends Action
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


    public function __construct(OutputStyle $output, int $account_id)
    {
        parent::__construct($output);

        $this->account = $this->getAccountFromId($account_id);
    }


    /**
     * @throws ErrorException
     */
    public function __invoke()
    {
        $this->timer->start();

        $this->output->writeln("<comment>Fetch folder list...</comment>");

        $mailboxes = (new FetchAccountFoldersAction)($this->account);
        (new UpdateFoldersToDatabaseAction($this->account))($mailboxes);
        $this->account->load(['folders.mails']);

        $this->output->writeln("<comment>Fetch mails list...</comment>");

        $step = 1;

        /** @var Folder $folder */
        foreach ($this->account->folders as $folder) {
            $this->output->write("\033[2K\r  <comment>$step/{$this->account->folders->count()}: $folder->name</comment>");
            (new UpdateFolderMailsToDatabaseAction)($this->account, $folder);
            $step++;
        }

        $this->output->newLine();

        $this->timer->stop();
    }
}
