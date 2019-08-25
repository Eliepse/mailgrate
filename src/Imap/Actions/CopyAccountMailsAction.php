<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use Illuminate\Console\OutputStyle;

class CopyAccountMailsAction extends Action
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


    public function __construct(OutputStyle $output, int $source_id)
    {
        parent::__construct($output);

        $this->from = $this->getAccountFromId($source_id);
    }


    public function __invoke(int $destination_id)
    {
        $this->to = $this->getAccountFromId($destination_id);

        /** @var Folder $folder */
        foreach ($this->from->folders as $folder) {
            $destFolder = $this->to->folders->firstWhere('nameWithoutRoot', $folder->nameWithoutRoot);

            if (!$destFolder)
                continue;

            (new CopyFolderMailsToAccountAction($this->output, $this->from, $this->to))($folder, $destFolder);
        };
    }
}
