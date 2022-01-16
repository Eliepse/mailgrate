<?php

if (! function_exists('imap_flush_errors')) {
	function imap_flush_errors(): void
	{
		imap_errors();
	}
}
