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
	 * @param bool $update_mails Update mail list or not
	 *
	 * @throws ErrorException
	 */
	public function __invoke(bool $update_mails = true)
	{
		$this->timer->start();

		$this->output->writeln("<comment>Fetch folder list...</comment>");

		$mailboxes = (new FetchAccountFoldersAction)($this->account);
		(new UpdateFoldersToDatabaseAction($this->account))($mailboxes);
		$this->account->load(['folders']);

		$this->output->writeln("<comment>Fetch mails list...</comment>");

		$step = 1;

		$updateFolderAction = new UpdateFolderMailsToDatabaseAction($this->output);

		if (! $update_mails) {
			$this->timer->stop();

			return;
		}

		/** @var Folder $folder */
		foreach ($this->account->folders as $folder) {
			$this->output->write("\033[2K\r  <comment>$step/{$this->account->folders->count()}: $folder->name</comment>");
			$updateFolderAction($this->account, $folder);
			$step++;
		}

		$this->output->newLine();

		$this->timer->stop();
	}
}
