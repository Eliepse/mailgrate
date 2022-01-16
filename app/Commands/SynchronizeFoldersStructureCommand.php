<?php

namespace App\Commands;

use App\Account;
use App\Folder;
use Eliepse\Console\Component\AccountSelection;
use Eliepse\Imap\Actions\CreateFoldersToAccountAction;
use Eliepse\Imap\Actions\FetchAccountFoldersAction;
use Eliepse\Imap\Actions\UpdateFoldersToDatabaseAction;
use Eliepse\Runtimer;
use ErrorException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use LaravelZero\Framework\Commands\Command;

class SynchronizeFoldersStructureCommand extends Command
{
	use AccountSelection;

	/**
	 * The signature of the command.
	 *
	 * @var string
	 */
	protected $signature = 'sync:folders';

	/**
	 * The description of the command.
	 *
	 * @var string
	 */
	protected $description = 'Synchronize folders structure (tree)';


	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 * @throws ErrorException
	 */
	public function handle()
	{
		$accounts = Account::with(['folders'])->get();

		$accountFrom = $this->selectAccountWithPassword($accounts);
		$accountTo = $this->selectAccountWithPassword($accounts->whereNotIn('id', [$accountFrom->id]));

		$mainTimer = new Runtimer(true);

		$this->comment("Update folder structure in database");

		$fromFolders = (new FetchAccountFoldersAction)($accountFrom);
		(new UpdateFoldersToDatabaseAction($accountFrom))($fromFolders);

		$toFolders = (new FetchAccountFoldersAction)($accountTo);
		(new UpdateFoldersToDatabaseAction($accountTo))($toFolders);

		$accountFrom->load(['folders']);
		$accountTo->load(['folders']);

		$this->comment("Finding differences...");

		$foldersToAdd = $accountFrom->folders
			->diffUsing($accountTo->folders, function (Folder $a, Folder $b) {
				return strcmp($a->nameWithoutRoot, $b->nameWithoutRoot);
			})
			// Create new folders to prevent original replacement in the database
			->transform(function (Folder $folder) use ($accountTo) {
				return new Folder([
					'name' => $folder->name,
					'attributes' => $folder->attributes,
				]);
			});

		if ($foldersToAdd->count() > 0) {
			$this->addFolders($accountTo, $foldersToAdd);
		} else {
			$this->info("All good! There is no folder to synchronize.");
		}

		$this->comment("Execution time: $mainTimer");

		return;
	}


	/**
	 * Define the command's schedule.
	 *
	 * @param Schedule $schedule
	 *
	 * @return void
	 */
	public function schedule(Schedule $schedule): void
	{
		// $schedule->command(static::class)->everyMinute();
	}


	public function addFolders(Account $account, Collection $folders)
	{
		$this->table(['Folders to add',], $folders->map(function (Folder $f) { return [$f->name]; }));

		if ($this->confirm("Do you want to add those folders?")) {
			(new CreateFoldersToAccountAction)($account, $folders);

			$errors = $folders->filter(function (Folder $folder) {
				return ! $folder->exists;
			});

			if ($errors->isEmpty()) {
				$this->info("Folders created!");
			} else {
				$this->alert("{$errors->count()} folders could not be created.");
			}
		}
	}
}
