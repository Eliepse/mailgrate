<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use App\Mail;
use App\Transfert;
use Eliepse\Imap\Utils;
use ErrorException;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CopyFolderMailsToAccountAction extends Action
{
	use AccountManagement;

	/**
	 * @var Account
	 */
	private $origin;

	/**
	 * @var Account
	 */
	private $destin;

	/**
	 * @var array
	 */
	public $stats = [];


	public function __construct(OutputStyle $output, Account $origin, Account $destination)
	{
		parent::__construct($output);

		$this->origin = $this->getAccountFromModel($origin);
		$this->destin = $this->getAccountFromModel($destination);
		$this->stats = [
			'success' => 0,
			'skipped' => 0,
			'failed' => 0,
		];
	}


	public function __invoke(Folder $from, Folder $to)
	{
		$this->timer->start();

		/** @var Collection $transferts */
//        $transferts = Transfert::query()
//            ->with(['mail'])
//            ->whereIn('mail_id', $from->mails->pluck('id'))
//            ->where('destination_account_id', $to->account->id)
//            ->select(['id', 'mail_id', 'destination_account_id', 'status'])
//            ->get();

		// Prepare mails to transfert (exclude already transfered ones)
		$mailToProcess = $from->mails
			->load(['transferts'])
			->filter(function (Mail $mail) {
				/** @var Transfert $transfert */
				$transfert = $mail->transferts
					->first(function (Transfert $t) {
						return $t->destination_account_id === $this->destin->id;
					});

				// Exclude only if the transfert has been tried and succeed
				return isset($transfert) ? ! $transfert->isSucess() : true;
			});

		// TODO(eliepse): make clean stats (including skipped)

		$this->stats['total'] = $from->mails->count();
		$this->stats['skipped'] = $from->mails->count() - $mailToProcess->count();

		if ($mailToProcess->count() === 0) {
			$this->output->writeln("Skipped");
			$this->timer->stop();

			return $this->stats;
		}

		$streamFrom = $this->origin->connect($from);
		$streamTo = $this->destin->connect($to);

		$bar = $this->output->createProgressBar($mailToProcess->count());

		/** @var Mail $mail */
		foreach ($mailToProcess as $mail) {

			/** @var Transfert $transfert */
			$transfert = $mail->transferts->firstWhere('destination_account_id', $this->destin->id) ?? new Transfert();
			$transfert->mail()->associate($mail);
			$transfert->destination()->associate($this->destin);

			// Download the email
			try {
				$body = imap_fetchbody($streamFrom, $mail->uid, null, FT_UID | FT_PEEK);
			} catch (ErrorException $e) {
				Log::error($e->getMessage(), ['mail' => $mail->toArray()]);
				$this->stats['failed']++;
				$transfert->status = Transfert::STATUS_FAILED;
				$transfert->message = imap_last_error();
				$transfert->save();
				continue;
			}

			// Upload the email
			if (imap_append($streamTo, Utils::uncleanMailboxName($to->name, $to->account), $body)) {
				$this->stats['success']++;
				$transfert->status = Transfert::STATUS_SUCCESS;
				$transfert->message = null;
			} else {
				Log::error(imap_last_error(), ['mail' => $mail->toArray()]);
				$this->stats['failed']++;
				$transfert->status = Transfert::STATUS_FAILED;
				$transfert->message = imap_last_error();
			}

			$transfert->save();

			$bar->advance();
		}

		$bar->finish();
		$this->output->newLine();

		imap_close($streamFrom);
		imap_close($streamTo);

		$this->timer->stop();

		return $this->stats;
	}
}
