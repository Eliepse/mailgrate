<?php


namespace Eliepse\Imap;


use Illuminate\Support\ServiceProvider;

class AccountPasswordServiceProvider extends ServiceProvider
{
	/**
	 * Register bindings in the container.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton(AccountPasswordManager::class, function () {
			return new AccountPasswordManager();
		});
	}
}
