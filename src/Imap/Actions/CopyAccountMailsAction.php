<?php


namespace Eliepse\Imap\Actions;


use App\Account;
use App\Folder;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Log;

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

        $step = 0;

        /** @var Folder $folder */
        foreach ($this->from->folders as $folder) {
            /** @var Folder|null $destFolder */
            $destFolder = $this->to->folders->firstWhere('nameWithoutRoot', $folder->nameWithoutRoot);

            $step++;

            if (!$destFolder)
                continue;

            $this->output->writeln("{$step}/{$this->from->folders->count()}: " . $destFolder->name);

            $action = new CopyFolderMailsToAccountAction($this->output, $this->from, $this->to);

            $action($folder, $destFolder);

            Log::info("Copied mails from $folder->name.", $action->stats);
        };
    }
}
