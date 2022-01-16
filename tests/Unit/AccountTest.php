<?php

namespace Tests\Unit;

use App\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
	use RefreshDatabase;


	public function testKeepPasswordThroughSerialization()
	{
		/** @var Account $account */
		$account = factory(Account::class)->create();
		$account->password = '1234';

		$serialized = serialize($account);
		$account = unserialize($serialized);

		$this->assertEquals(Account::class, get_class($account));
		$this->assertEquals('1234', $account->password);
	}
}
