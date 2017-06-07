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

class Virtualbox
{
    /**
     * @var string
     */
    protected $uuid = '';
    /**
     * @var bool
     */
    protected $is_vagrant = false;
    /**
     * @var null|string
     */
    protected $vagrant_path = null;
    /**
     * @var null|string
     */
    protected $vagrant_id = null;
    /**
     * @var array
     */
    protected $raw_details = [];
    /**
     * @var string
     */
    protected $name = '';
    /**
     * @var string
     */
    protected $status = '';

    public function __construct($virtual_box_uiid)
    {
        $this->fillRawDetails($virtual_box_uiid);

        $this->setName($this->getRawDetails('name'));
        $this->setStatus($this->getRawDetails('VMState'));

        $this->fillVagrantPath();
        $this->fillVagrantId();

        return $this;
    }

    protected function fillRawDetails($virtual_box_uiid = null)
    {
        if (is_null($virtual_box_uiid) && !empty($this->getUuid())) {
            $virtual_box_uiid = $this->getUuid();
        } elseif (is_null($virtual_box_uiid) && empty($this->getUuid())) {
            throw new \Exception('no uuid was given');
        }

        $boxinfo = shell_exec("VBoxManage showvminfo --machinereadable {$virtual_box_uiid} 2> /dev/null");

        if (is_null($boxinfo)) {
            throw new \Exception("invalid virtual box uuid {$virtual_box_uiid}");
        }

        $this->setUuid($virtual_box_uiid);

        $raw_details = explode("\n", trim($boxinfo));

        // reset
        $this->raw_details = [];
        // get all details in a nice array
        foreach ($raw_details as $raw_detail) {
            $parts = explode('=', trim($raw_detail), 2);
            $this->raw_details[$parts[0]] = trim($parts[1], ' "');
        }

        return $this;
    }

    protected function fillVagrantPath()
    {
        // get the vagrant path
        $check_one_more = true;
        $i = 1;
        while ($check_one_more) {
            // continue checking mappings?
            if (is_null($this->getRawDetails("SharedFolderNameMachineMapping{$i}"))) {
                $check_one_more = false;
                continue;
            }

            // is it the vagrant path?
            if ($this->getRawDetails("SharedFolderNameMachineMapping{$i}") == 'vagrant') {
                $this->setVagrantPath($this->getRawDetails("SharedFolderPathMachineMapping{$i}"));
                $this->setIsVagrant(true);
                $check_one_more = false;
            }
            $i++;
        }

        return $this;
    }

    /**
     * @param null $box_root_path
     * @return $this
     */
    protected function fillVagrantId($box_root_path = null)
    {
        if (is_null($box_root_path) && !is_null($this->vagrant_path)) {
            $box_root_path = $this->vagrant_path;
        } else {
            throw new \Exception('no path given');
        }

        $dir_list = glob("$box_root_path/.vagrant/machines/*", GLOB_ONLYDIR);

        // there should be 1 directory no more no less
        if (1 != count($dir_list)) {
            return $this;
        }

        $vagrand_id_file = "{$dir_list[0]}/virtualbox/index_uuid";
        $id = file_get_contents($vagrand_id_file);
        if (!empty($id)) {
            $this->setIsVagrant(true);
            $this->setVagrantId($id);
        }
        $this->fillRawDetails($this->getUuid());

        return $this;
    }

    /**
     * @return bool
     */
    public function haltVagrant()
    {
        if (!$this->isVagrant()) {
            return false;
        }

        if ('running' !== $this->getStatus()) {
            return false;
        }

        $this->fillRawDetails();
        // @todo check error?
        $halt = trim(shell_exec("vagrant halt {$this->getVagrantId()}"));
        return true;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     * @return Virtualbox
     */
    public function setUuid(string $uuid): Virtualbox
    {
        $this->uuid = $uuid;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVagrant(): bool
    {
        return $this->is_vagrant;
    }

    /**
     * @param bool $is_vagrant
     * @return Virtualbox
     */
    public function setIsVagrant(bool $is_vagrant): Virtualbox
    {
        $this->is_vagrant = $is_vagrant;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getVagrantPath()
    {
        return $this->vagrant_path;
    }

    /**
     * @param null|string $vagrant_path
     * @return Virtualbox
     */
    public function setVagrantPath($vagrant_path)
    {
        $this->vagrant_path = $vagrant_path;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getVagrantId()
    {
        return $this->vagrant_id;
    }

    /**
     * @param null|string $vagrant_id
     * @return Virtualbox
     */
    public function setVagrantId($vagrant_id)
    {
        $this->vagrant_id = $vagrant_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Virtualbox
     */
    public function setName(string $name): Virtualbox
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Virtualbox
     */
    public function setStatus(string $status): Virtualbox
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return array|string
     */
    public function getRawDetails($key = null)
    {
        if (!is_null($key)) {
            if (!isset($this->raw_details[$key])) {
                return null;
            }
            return $this->raw_details[$key];
        }
        return $this->raw_details;
    }
}
