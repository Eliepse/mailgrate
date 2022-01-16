<?php


namespace Eliepse\Imap;


use App\Account;

final class Utils
{
	const IMAP_DELIMITER = '/';


	/**
	 * Convert delimiters of a path to match RFC2683
	 *
	 * @param string $path The path to convert
	 * @param string $delimiter The custom delimiter
	 *
	 * @return string
	 */
	public static function toRFC2683Delimiter(string $path, string $delimiter): string
	{
		return str_replace($delimiter, self::IMAP_DELIMITER, $path);
	}


	/**
	 * Convert RFC2683 delimiters to custom ones
	 *
	 * @param string $path The path to convert
	 * @param string $delimiter The custom delimiter
	 *
	 * @return string
	 */
	public static function toCustomDelimiter(string $path, string $delimiter): string
	{
		return str_replace(self::IMAP_DELIMITER, $delimiter, $path);
	}


	/**
	 * Convert an imap utf-7 string to utf-8
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function imapUtf7ToUtf8(string $string): string
	{
		return mb_convert_encoding($string, 'UTF-8', 'UTF7-IMAP');
	}


	/**
	 * Convert an utf-8 string to imap utf-7
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function imapUtf8ToUtf7(string $string): string
	{
		return mb_convert_encoding($string, 'UTF7-IMAP', 'UTF-8');
	}


	/**
	 * Convert mailbox's name to RFC2683 standart, UTF-8 and remove useless parts
	 *
	 * @param string $name
	 * @param Account $account
	 *
	 * @return string Return the name as an UTF-8 string
	 */
	public static function cleanMailboxName(string $name, Account $account): string
	{
		return substr(
			self::toRFC2683Delimiter(
				self::imapUtf7ToUtf8($name),
				$account->delimiter),
			strlen($account->host)
		);
	}


	/**
	 * Convert RFC2683 standart, UTF-8 and 'hostless' to a valid imap mailbox name
	 *
	 * @param string $name
	 * @param Account $account
	 *
	 * @return string
	 */
	public static function uncleanMailboxName(string $name, Account $account): string
	{
		return $account->host .
			self::toCustomDelimiter(
				self::imapUtf8ToUtf7($name),
				$account->delimiter
			);
	}


	/**
	 * Convert a subject to utf-8, mime encoded or not
	 *
	 * @param string $subject
	 *
	 * @return string
	 */
	public static function convertMailSubject(string $subject): string
	{
		if (preg_match("/=\?/", $subject))
			return iconv_mime_decode($subject, ICONV_MIME_DECODE_CONTINUE_ON_ERROR);
		else
			return mb_convert_encoding($subject, 'UTF-8');
	}
}
