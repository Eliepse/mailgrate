<?php


namespace App;


use Carbon\Carbon;
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
    protected $with = ['mails'];


    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id', 'id');
    }


    public function mails(): HasMany
    {
        return $this->hasMany(Mail::class, 'folder_id', 'id');
    }
}
