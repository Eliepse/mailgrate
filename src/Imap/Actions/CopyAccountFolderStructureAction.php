<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;

class CopyAccountFolderStructureAction extends Action
{
    use AccountManagement;

    /**
     * @var Account
     */
    private $from;

    /**
     * @var Account
     */
    private $to;


    /**
     * CopyAccountFolderStructureAction constructor.
     *
     * @param int $source_id Id of the source account
     */
    public function __construct(int $source_id)
    {
        parent::__construct();

        $this->from = $this->getAccountFromId($source_id);
    }


    public function __invoke(int $destination_id)
    {
        $this->timer->start();

        $this->to = $this->getAccountFromId($destination_id);

        $foldersToCreate = $this->from->folders
            ->diffUsing($this->to->folders, function (Folder $a, Folder $b) {
                return strcmp($a->nameWithoutRoot, $b->nameWithoutRoot);
            });

        // Create new folders to prevent original replacement in the database
        $foldersToCreate->transform(function (Folder $folder) {
            return new Folder([
                'name' => $folder->name,
                'attributes' => $folder->attributes,
            ]);
        });

        // Terminate action if there is no change
        if ($foldersToCreate->count() === 0) {
            $this->timer->stop();

            return;
        }

        (new CreateFoldersToAccountAction)($this->to, $foldersToCreate);

        $this->timer->stop();
    }
}
