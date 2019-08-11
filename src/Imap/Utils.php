<?php


namespace Eliepse\Imap;


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
}
