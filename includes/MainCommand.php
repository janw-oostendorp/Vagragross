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
            ->setName('vagragross')
            // the short description shown while running "php bin/console list"
            ->setDescription('List current running vagrants')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('List all running vagrant boxes');

        $this->setDefinition(new InputDefinition([
            new InputOption('halt', '', null, 'halt all running boxes'),
            new InputOption(
                'details',
                'd',
                null,
                'List details of the box'
            ),
        ]));

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // get list of all virtual boxes
        $virtual_box_list = explode("\n", trim(shell_exec('VBoxManage list vms')));

        sort($virtual_box_list);

        foreach ($virtual_box_list as $virtual_box_row) {
            $writeln = [];
            $virtualbox = new Virtualbox(
                $this->extractVirtualboxUIID($virtual_box_row)
            );

            // not a vagrant?
            if (!$virtualbox->isVagrant()) {
                continue;
            }

            if ($input->getOption('details')) {
                $writeln[] = '<fg=magenta>' . str_repeat('=', 65) . '</>';
            }


            $name = '<fg=magenta>' . str_pad($virtualbox->getName(), 24) . '</>';

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

            $writeln[] = "{$name} {$status}";


            if ($input->getOption('details')) {
//                $writeln[] =;

                $writeln[] = '<fg=cyan>' . str_pad('VB id ', 25, ' ', STR_PAD_LEFT)
                    . $virtualbox->getUuid();

                if (!$virtualbox->getVagrantId()) {
                    $writeln[] = str_pad('vagrant id ', 25, ' ', STR_PAD_LEFT)
                        . "<fg=red>Broken?</>";
                } else {
                    $writeln[] = str_pad('vagrant id ', 25, ' ', STR_PAD_LEFT)
                        . $virtualbox->getVagrantId();
                }

                $writeln[] = str_pad('Vagrant root ', 25, ' ', STR_PAD_LEFT)
                    . $virtualbox->getVagrantPath() . '</>';
            }

            // add output so far
            $output->writeln($writeln);


            if ($input->getOption('halt') && 'running' === $virtualbox->getStatus()) {
                $output->writeln(str_repeat(' ', 25) . '<fg=green>Trying to turn down the machine</>');
                if ($virtualbox->haltVagrant()) {
                    $output->writeln(str_repeat(' ', 25) . '<fg=green>Halted</>');
                } else {
                    $output->writeln(str_repeat(' ', 25) . '<fg=green>Can\'t halt</>');
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
