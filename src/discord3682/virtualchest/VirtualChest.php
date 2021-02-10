<?php

namespace discord3682\virtualchest;

use discord3682\virtualchest\inventory\PluginInventory;
use pocketmine\plugin\PluginBase;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\Player;

function convert ($player) : string
{
  return strtolower ($player instanceof Player ? $player->getName () : $player);
}

class VirtualChest extends PluginBase
{

  use SingletonTrait;

  private $database;
  protected static $db = [];

  const VIRTUAL_CHEST = '§l§b[가상창고]§r§7 ';

  public function onEnable () : void
  {
    $this->database = new Config ($this->getDataFolder () . 'Data.yml', Config::YAML);
    self::$db = $this->database->getAll ();
    $this->getServer ()->getPluginManager ()->registerEvents (new EventListener (), $this);
    $this->getServer ()->getCommandMap ()->register ('discord3682', new class () extends PluginCommand
    {

      public function __construct ()
      {
        parent::__construct ('virtualchest', VirtualChest::getInstance ());

        $this->setAliases (['가상창고']);
      }

      public function execute (CommandSender $sender, string $label, array $args) : void
      {
        if (!$sender instanceof Player) return;

        VirtualChest::addVirtualChest ($sender);
      }
    });
  }

  public function onDisable () : void
  {
    $this->database->setAll (self::$db);
    $this->database->save ();
  }

  public function onLoad () : void
  {
    self::setInstance ($this);
  }

  public static function addVirtualChest (Player $player) : void
  {
    $data = self::$db [convert ($player)];
    $inv = new PluginInventory ($player, $player, '§l§b[가상창고]§r');

    for ($i=0; $i<=$data [0]; $i++)
    {
      $inv->setItem ($i, Item::jsonDeserialize ($data [1] [$i] ?? ['id' => 0]));
    }

    for ($i=$data [0]+1; $i<=53; $i++)
    {
      $inv->setItem ($i, Item::get (63)->setCustomName (' '));
    }

    $player->addWindow ($inv);
  }

  public static function getData ($player) : ?array
  {
    return self::$db [convert ($player)] ?? null;
  }

  public static function setData ($player, int $openedSlot = 0, array $contents = []) : void
  {
    self::$db [convert ($player)] = [
      $openedSlot,
      $contents
    ];
  }

  public static function msg ($player, string $msg) : void
  {
    $player->sendMessage (self::VIRTUAL_CHEST . $msg);
  }

  public static function convert ($player) : string
  {
    return strtolower ($player instanceof Player ? $player->getName () : $player);
  }
}
