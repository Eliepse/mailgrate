<?php


namespace Tests\Feature\Commands;


use Tests\TestCase;

class CreateAccountCommandTest extends TestCase
{
	public function testNoErrors()
	{
//        $this->artisan('account:create')
//            ->expectsQuestion("What is the host domain?", "imap.test.local")
//            ->expectsQuestion("What is the port?", null)
//            ->expectsQuestion("What is the security protocol?", null)
//            ->expectsQuestion("What is the username?", "j.doe@exemple.com");
		$this->markTestIncomplete("Should find a way to mock imap server.");
	}
}
