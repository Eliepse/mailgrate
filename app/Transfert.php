<?php


namespace App;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Transfert
 *
 * @package App
 * @property-read int id
 * @property int mail_id
 * @property int destination_account_id
 * @property int status
 * @property string|null message
 * @property-read Carbon created_at
 * @property-read Carbon updated_at
 * Relations
 * @property-read Account destination
 * @property-read Mail mail
 */
class Transfert extends Model
{
    const STATUS_FAILED = 1;
    const STATUS_IDLE = 0;
    const STATUS_SUCCESS = 2;

    protected $table = 'transferts';
    protected $guarded = [];


    public function mail(): BelongsTo
    {
        return $this->belongsTo(Mail::class, 'mail_id', 'id');
    }


    public function destination(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'destination_account_id', 'id');
    }


    public function isSucess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }


    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }


    public function isIdle(): bool
    {
        return $this->status === self::STATUS_IDLE;
    }
}
