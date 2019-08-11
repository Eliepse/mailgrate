<?php


namespace App;


use Carbon\Carbon;
use Eliepse\Imap\Utils;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Account
 *
 * @package App
 * @property-read int id
 * @property string host
 * @property string username
 * @property string delimiter
 * @property string|null root
 * @property-read Carbon created_at
 * @property-read Carbon updated_at
 * Relations
 * @property-read Collection folders
 * @property-read Collection transferts
 */
final class Account extends Model
{
    protected $table = 'accounts';
    protected $guarded = [];
    protected $with = ['folders'];

    /**
     * For security purpose, the password
     * is not stored in the database
     *
     * @var string|null
     */
    public $password;


    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class, 'account_id', 'id');
    }


    public function transferts(): HasMany
    {
        return $this->hasMany(Transfert::class, 'destination_account_id', 'id');
    }


    public function toArray()
    {
        return array_merge(
            parent::toArray(),
            ['password' => $this->password]
        );
    }


    /**
     * Open an imap connection
     *
     * @param Folder $folder
     * @param int $options
     *
     * @return resource
     */
    public function connect(Folder $folder = null, int $options = OP_READONLY)
    {
        $host = $this->host;

        if ($folder)
            $host .= Utils::toCustomDelimiter($folder->name, $this->delimiter);

        return imap_open($host, $this->username, $this->password, $options);
    }
}
