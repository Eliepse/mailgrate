<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use Eliepse\Imap\Utils;
use ErrorException;
use stdClass;

class FetchAccountFoldersAction
{
	/**
	 * @param Account $account
	 *
	 * @return array
	 * @throws ErrorException
	 */
	public function __invoke(Account $account): array
	{
		$stream = $account->connectWithoutRoot();
		$pattern = Utils::toCustomDelimiter(
			$account->root . Utils::IMAP_DELIMITER . '*',
			$account->delimiter);

		// TODO(eliepse): handle non existing folders (throw error)
		$mailboxes = imap_getmailboxes($stream, $account->host, $account->root ? $pattern : '*') ?: [];

		imap_close($stream);

		if (! empty($account->root)) {
			$mailboxe = new stdClass();
			$mailboxe->name = $account->host . $account->root;
			$mailboxe->attributes = 64;
			$mailboxe->delimiter = $account->delimiter;
			$mailboxes[] = $mailboxe;
		}

		if (! is_array($mailboxes))
			throw new ErrorException("Error on fetching mailboxes list.");

		return $mailboxes;
	}
}
