<?php

namespace Tests\Unit\Imap;

use Eliepse\Imap\Utils;
use Tests\TestCase;

class UtilsTest extends TestCase
{

	public function testToRFC2683Delimiter()
	{
		$converted = Utils::toRFC2683Delimiter("INBOX.Archives.2019", ".");

		$this->assertEquals(join(Utils::IMAP_DELIMITER, ["INBOX", "Archives", "2019"]), $converted);
	}


	public function testToCustomDelimiter()
	{
		$converted = Utils::toCustomDelimiter(join(Utils::IMAP_DELIMITER, ["INBOX", "Archives", "2019"]), ".");

		$this->assertEquals("INBOX.Archives.2019", $converted);
	}


	public function testImapUtf7ToUtf8()
	{
		$converted = Utils::imapUtf7ToUtf8("&AMk-l&AOk-ments supprim&AOk-s");

		$this->assertEquals("Éléments supprimés", $converted);
	}


}
