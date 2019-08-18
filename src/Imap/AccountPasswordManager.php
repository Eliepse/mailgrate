<?php


namespace Eliepse\Imap;


use App\Account;
use Illuminate\Support\Collection;

final class AccountPasswordManager
{
    /**
     * Accounts with a valid password
     *
     * @var Collection
     */
    private $accounts;


    public function __construct()
    {
        $this->accounts = collect();
    }


    public function add(Account $account)
    {
        $this->accounts->put($account->id, $account);
    }


    public function get(int $id): ?Account
    {
        return $this->accounts->get($id);
    }


    public function getPassword(int $id): ?string
    {
        $account = $this->get($id);

        return $account->password ?? null;
    }
}
