<?php
/**
 * @package    vagragross
 * @author     Janw Oostendorp <janw.me>
 * @copyright  2017
 * @license    https://opensource.org/licenses/MIT MIT
 * @version    SVN: $Id$
 * @link       https://github.com/janw-oostendorp/Vagragross
 * @since      0.9
 */

namespace vagragross\includes;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MainCommand extends Command
{
    public function __construct($name = null)
    {
        //@todo do the virtual box command and vagrant command exist

        parent::__construct($name);
    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('manage')
            // the short description shown while running "php bin/console list"
            ->setDescription('List current running vagrants')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('List all running vagrant boxes');

        $this->setDefinition(new InputDefinition([
            new InputOption('halt', '', null, 'halt all running boxes'),
            new InputOption(
                'listids',
                'i',
                null,
                'Add the Virtualbox UIID and Vagrant id of the boxes to the output'
            ),
        ]));

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get list of all virtual boxes
        $virtual_box_list = explode("\n", trim(shell_exec('VBoxManage list vms')));

        foreach ($virtual_box_list as $virtual_box_row) {
            $virtualbox = new Virtualbox(
                $this->extractVirtualboxUIID($virtual_box_row)
            );

            // not a vagrant?
            if (!$virtualbox->isVagrant()) {
                $name = '<fg=red>' . str_pad($virtualbox->getName(), 24) . '</>';
            } else {
                $name = '<fg=white>' . str_pad($virtualbox->getName(), 24) . '</>';
            }

            // status
            switch ($virtualbox->getStatus()) :
                case 'poweroff':
                    $status = '<fg=white>' . str_pad($virtualbox->getStatus(), 12) . '</>';
                    break;
                case 'running':
                    $status = '<fg=green>' . str_pad($virtualbox->getStatus(), 12) . '</>';
                    break;
                case 'aborted':
                    $status = '<fg=red>' . str_pad($virtualbox->getStatus(), 12) . '</>';
                    break;
                default:
                    $status = '<fg=yellow>' . str_pad($virtualbox->getStatus(), 12) . '</>';
                    break;
            endswitch;

            // should add id?
            $id = '';
            if ($input->getOption('listids')) {
                $id = "<fg=cyan>{$virtualbox->getUuid()}</>";

                if (!$virtualbox->getVagrantId()) {
                    $id .= " <fg=red>Unknown</>";
                } else {
                    $id .= " <fg=blue>{$virtualbox->getVagrantId()}</>";
                }

            }

            // add output
            $output->writeln("{$name} {$status} {$id}");

            if ($input->getOption('halt')) {
                if ($virtualbox->haltVagrant()) {
                    $output->writeln(str_repeat(' ', 25) . '<fg=green>Halted</>');
                }
            }
        }
    }

    /**
     * @param string $box_string
     * @return Virtualbox
     */
    protected function extractVirtualboxUIID($box_string)
    {
        preg_match("|\{(.*)\}|U", $box_string, $box_id);

        return $box_id[1];
    }
}
