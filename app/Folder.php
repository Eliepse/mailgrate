<?php


namespace App;


use Carbon\Carbon;
use Eliepse\Imap\Utils;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Folder
 *
 * @package App
 * @property-read int id
 * @property int account_id
 * @property string name
 * @property string nameWithoutRoot
 * @property string $fullname
 * @property int attributes
 * @property-read Carbon created_at
 * @property-read Carbon updated_at
 * Relations
 * @property-read Account account
 * @property-read Collection mails
 */
final class Folder extends Model
{
	protected $table = 'folders';
	protected $guarded = ['account_id'];
	protected $withCount = ["mails"];


	/**
	 * @return string
	 */
	public function getNameWithoutRootAttribute(): string
	{
		if (empty($this->account->root)) {
			return $this->name;
		} else {
			$quotedPrefix = preg_quote($this->account->root . Utils::IMAP_DELIMITER, Utils::IMAP_DELIMITER);

			return preg_replace("/^$quotedPrefix/", "", $this->name);
		}
	}


	/**
	 * Return the name of the folder, with host and root
	 *
	 * @return string
	 */
	public function getFullnameAttribute(): string
	{
		return $this->account->host . $this->nameWithoutRoot;
	}


	public function account(): BelongsTo
	{
		return $this->belongsTo(Account::class, 'account_id', 'id');
	}


	public function mails(): HasMany
	{
		return $this->hasMany(Mail::class, 'folder_id', 'id');
	}
}
