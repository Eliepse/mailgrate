<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use Eliepse\Imap\AccountPasswordManager;

trait AccountManagement
{
	protected function getAccountFromModel(Account $account): ?Account
	{
		return app(AccountPasswordManager::class)->get($account->id);
	}


	protected function getAccountFromId(int $id): ?Account
	{
		return app(AccountPasswordManager::class)->get($id);
	}


	/**
	 * @param int $id
	 *
	 * @return bool
	 */
	protected function accountHasPassword(int $id): bool
	{
		return ! empty($this->getAccountFromId($id)->password);
	}
}
