<?php

/* @var $factory Factory */

use App\Account;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Arr;

$factory->define(Account::class, function (Faker $faker) {
    return [
        'host' => "{imap." . $faker->domainName . "/imap/ssl}",
        'username' => $faker->userName,
        'delimiter' => Arr::random(['/', '.']),
        'root' => $faker->boolean ? 'INBOX' : null,
    ];
});
