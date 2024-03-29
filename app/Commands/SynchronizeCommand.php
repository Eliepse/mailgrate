<?php

namespace App\Commands;

use App\Account;
use App\Folder;
use App\Mail;
use App\Transfert;
use Eliepse\Console\Component\AccountSelection;
use Eliepse\Imap\Actions\CopyAccountFolderStructureAction;
use Eliepse\Imap\Actions\CopyAccountMailsAction;
use Eliepse\Imap\Actions\UpdateAccountInformationsAction;
use Eliepse\Runtimer;
use ErrorException;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class SynchronizeCommand extends Command
{
	use AccountSelection;

	/**
	 * The signature of the command.
	 *
	 * @var string
	 */
	protected $signature = 'sync 
                            {--no-update : only use database informations (include folders and mails)}';

	/**
	 * The description of the command.
	 *
	 * @var string
	 */
	protected $description = 'Interactive command to synchronize an accounts to another';

	/**
	 * @var Account
	 */
	private $from;

	/**
	 * @var Account
	 */
	private $to;

	/**
	 * @var Runtimer
	 */
	private $timer;


	public function __construct()
	{
		parent::__construct();

		$this->timer = new Runtimer();
	}


	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 * @throws ErrorException
	 */
	public function handle()
	{
		$accounts = Account::all();

		$this->question("Select the account that will be the source:");
		$this->from = $this->selectAccountWithPassword($accounts);

		$this->question("Select the account that will be the destination:");
		$this->to = $this->selectAccountWithPassword($accounts->whereNotInStrict('id', $this->from->id));

		$this->timer->start();

		if (! $this->option('no-update')) {
			$this->comment("\nUpdating source account informations...");
			(new UpdateAccountInformationsAction($this->output, $this->from->id))();

			$this->comment("Updating destination account informations...\n");
			(new UpdateAccountInformationsAction($this->output, $this->to->id))(false);
		} else {
			$this->comment("Skipped accounts update.");
		}

		$this->from->load(['folders.mails']);
		$this->to->load(['folders']);

		$this->table(
			['Source account', 'Destination account'],
			[
				[$this->from->folders->count() . ' folders', $this->to->folders->count() . ' folders'],
				[$this->from->mailCount() . ' mails', '-'],
			]);

		$this->info("\nCopying folder structure...");
		(new CopyAccountFolderStructureAction($this->output, $this->from->id))($this->to->id);

		$this->from->load(['folders.mails']);
		$this->to->load(['folders']);

		$this->info("\nCopying mails...");
		(new CopyAccountMailsAction($this->output, $this->from->id))($this->to->id);

		$this->timer->stop();

		$baseQuery = Transfert::query()
			->whereIn('mail_id', Mail::query()
				->whereIn('folder_id', Folder::query()
					->where('account_id', $this->from->id)
					->select('id'))
				->select('id'))
			->where('destination_account_id', $this->to->id);

		$this->info("Here are some global stats about mails transfers:");
		$this->table(["Succeed", "Failed"], [
			[
				$baseQuery->where('status', Transfert::STATUS_SUCCESS)->count(),
				$baseQuery->where('status', Transfert::STATUS_FAILED)->count(),
			],
		]);

		$this->comment("Command executed in $this->timer");

//        imap_flush_errors();

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
}
