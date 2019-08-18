<?php


namespace Eliepse\Console\Component;


use App\Account;
use Eliepse\Imap\AccountPasswordManager;
use ErrorException;
use Illuminate\Database\Eloquent\Collection;

trait AccountSelection
{
    /**
     * @param Collection $accounts
     *
     * @return Account
     */
    protected function selectAccount(Collection $accounts): Account
    {
        $this->displayAccountsChoicesList($accounts);

        return $this->askAccountChoice($accounts);
    }


    /**
     * @param Collection $accounts
     *
     * @return Account
     */
    protected function selectAccountWithPassword(Collection $accounts): Account
    {
        $account = $this->selectAccount($accounts);

        $this->askAccountPassword($account);

        return $account;
    }


    /**
     * Display given account in a formated choice list
     *
     * @param Collection $accounts
     */
    protected function displayAccountsChoicesList(Collection $accounts)
    {
        $rows = $accounts->reduce(function (array $arr, Account $account) {
            $arr[] = [
                $account->id,
                "{$account->host} - {$account->username}",
            ];

            return $arr;
        }, []);

        $this->table(["Choice", "Accounts"], $rows);
    }


    /**
     * @param Collection $accounts
     *
     * @return Account
     */
    protected function askAccountChoice(Collection $accounts): Account
    {
        do {
            /** @var Account $account */
            if (isset($account))
                $this->error("Selected choice does not exists.");

            $id = $this->ask("Select an account to update");

        } while (!$account = $accounts->firstWhere("id", intval($id)) ?? false);

        return $account;
    }


    /**
     * @param Account $account
     *
     * @return string
     */
    protected function askAccountPassword(Account $account): string
    {
        do {
            $account->password = $this->secret("Please enter the password");
            $this->comment("Testing the connection...");

            try {
                $stream = imap_open($account->host, $account->username, $account->password, OP_READONLY | OP_HALFOPEN, 2);
            } catch (ErrorException $exception) {
                $this->error("Failed to connect, try again.");
            }

        } while (!isset($stream));

        imap_close($stream);

        $this->info("Success!");

        app(AccountPasswordManager::class)->add($account);

        return $account->password;
    }
}
