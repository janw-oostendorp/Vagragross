<?php
namespace vagragross;
/**
 * @package    vagragross
 * @author     Janw Oostendorp <janw.me>
 * @copyright  2017
 * @license    https://opensource.org/licenses/MIT MIT
 * @version    SVN: $Id$
 * @link       https://github.com/janw-oostendorp/Vagragross
 * @since      0.9
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/includes/MainCommand.php';
require __DIR__ . '/includes/Virtualbox.php';

use Symfony\Component\Console\Application;
use vagragross\includes\MainCommand;

$application = new Application();

// ... register commands
$command = new MainCommand();
$application->add($command);
$application->setDefaultCommand($command->getName(), true);
$application->run();
