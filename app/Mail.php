<?php


namespace App;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Class Mail
 *
 * @package App
 * @property-read int id
 * @property int uid
 * @property string subject
 * @property int folder_id
 * @property-read Carbon created_at
 * @property-read Carbon updated_at
 * Relations
 * @property-read Folder folder
 * @property-read Collection transferts
 */
final class Mail extends Model
{
	protected $table = 'mails';
	protected $guarded = [];
	public $timestamps = false;


	public function folder(): BelongsTo
	{
		return $this->belongsTo(Folder::class, 'folder_id', 'id');
	}


	public function transferts(): HasMany
	{
		return $this->hasMany(Transfert::class, 'mail_id', 'id');
	}


	/**
	 * @param Account|int $account
	 *
	 * @return HasMany
	 */
	public function transfertsToAccount($account): HasMany
	{
		return $this->transferts()
			->where('destination_account_id',
				is_int($account) ? $account : $account->id);
	}


	public function setSubjectAttribute(string $subject)
	{
		$this->attributes['subject'] = Str::limit($subject, 200);
	}
}
