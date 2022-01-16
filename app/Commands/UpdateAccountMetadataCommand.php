<?php

namespace App\Commands;

use App\Account;
use App\Folder;
use Eliepse\Console\Component\AccountSelection;
use Eliepse\Imap\Actions\FetchAccountFoldersAction;
use Eliepse\Imap\Actions\UpdateFolderMailsToDatabaseAction;
use Eliepse\Imap\Actions\UpdateFoldersToDatabaseAction;
use Eliepse\Runtimer;
use ErrorException;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class UpdateAccountMetadataCommand extends Command
{
	use AccountSelection;

	/**
	 * The signature of the command.
	 *
	 * @var string
	 */
	protected $signature = 'account:update';

	/**
	 * The description of the command.
	 *
	 * @var string
	 */
	protected $description = 'Update metadata of an account';


	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 * @throws ErrorException
	 */
	public function handle()
	{
		$accounts = Account::all();
		$account = $this->selectAccountWithPassword($accounts);
		$mainRuntimer = new Runtimer(true);


		/* * * * * * * * * * * *
		 * Fetch mailbox list  *
		 * * * * * * * * * * * */

		$localRuntimer = new Runtimer(true);
		$this->comment("Fetching mailboxes list...");
		$mailboxes = (new FetchAccountFoldersAction)($account);
		$this->comment($localRuntimer);


		/* * * * * * * * * * * *
		 * Update folders list *
		 * * * * * * * * * * * */

		$this->comment("Updating mailboxes metadata...");
		$localRuntimer->reset();
		$foldersStats = (new UpdateFoldersToDatabaseAction($account))($mailboxes);
		$account->load(['folders.mails']); // We have to reload relations because list might have changed
		$this->table(['name'], $account->folders->map(function (Folder $folder) { return [$folder->name]; }));
		$this->info("{$foldersStats['total']} folders (+{$foldersStats['added']}, -{$foldersStats['deleted']})");
		$this->comment($localRuntimer);

		/* * * * * * * * * * * *
		 * Update mails lists  *
		 * * * * * * * * * * * */

		$this->comment("Fetching mails lists...");
		$localRuntimer->reset();

		$gTotalMails = 0;
		$gAddedMails = 0;
		$gDeletedMails = 0;

		/** @var Folder $folder */
		foreach ($account->folders as $key => $folder) {
			$this->info("Updating(" . ($key + 1) . "/{$account->folders->count()}): {$folder->name}");

			$stats = (new UpdateFolderMailsToDatabaseAction($this->output))($account, $folder);

			$gAddedMails += $stats['added'];
			$gDeletedMails += $stats['deleted'];
			$gTotalMails += $stats['total'];

			$this->info("\t{$stats['total']} mails ({$stats['added']} added, {$stats['deleted']} deleted)");
		}

		$localRuntimer->stop();
		$this->comment($localRuntimer);
		$this->info("Total: $gTotalMails (+$gAddedMails, -$gDeletedMails).");
		$this->line("");
		$this->info("Update done.");
		$this->comment("Executed in $mainRuntimer");

		return;
	}


	/**
	 * Define the command's schedule .
	 *
	 * @param Schedule $schedule
	 *
	 * @return void
	 */
	public function schedule(Schedule $schedule): void
	{
		// $schedule->command(static::class)->everyMinute();
	}
}
