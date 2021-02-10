<?php

namespace discord3682\virtualchest\inventory;

use discord3682\virtualchest\VirtualChest;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\convert\RuntimeBlockMapping;
use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\BaseInventory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\scheduler\ClosureTask;
use pocketmine\tile\Spawnable;
use pocketmine\block\BlockIds;
use pocketmine\math\Vector3;
use pocketmine\Player;

class PluginInventory extends ContainerInventory
{

  private $vector;
  private $player;

  protected $title;

  public function __construct (Player $player, string $title)
  {
    $this->title = $title;
    $this->player = $player;

    parent::__construct ($player->asVector3 ());
  }

  public function getName () : string
  {
    return $this->title;
  }

  public function getNetworkType () : int
  {
    return WindowTypes::CONTAINER;
  }

  public function getDefaultSize () : int
  {
    return 54;
  }

  public function onOpen (Player $player) : void
  {
    BaseInventory::onOpen ($player);

    $this->vector = $player->add (0, -4)->floor ();

    $x = $this->vector->x;
    $y = $this->vector->y;
    $z = $this->vector->z;

    for ($i=0; $i<=1; $i++)
    {
      $pk = new UpdateBlockPacket ();
      $pk->x = $x + $i;
      $pk->y = $y;
      $pk->z = $z;
      $pk->blockRuntimeId = RuntimeBlockMapping::toStaticRuntimeId (BlockIds::CHEST);
      $pk->flags = UpdateBlockPacket::FLAG_ALL_PRIORITY;
      $player->sendDataPacket ($pk);

      $pk = new BlockActorDataPacket();
      $pk->x = $x;
      $pk->y = $y;
      $pk->z = $z + $i;
      $pk->namedtag = (new NetworkLittleEndianNBTStream ())->write (new CompoundTag ('', [
        new StringTag ('id', 'Chest'),
        new IntTag ('x', $x),
        new IntTag ('y', $y),
        new IntTag ('z', $z + $i),
        new StringTag ('CustomName', $this->title),
        new IntTag ('pairz', $z),
        new IntTag ('pairx', $x + (1 - $i))
      ]));
      $player->sendDataPacket ($pk);
    }

    VirtualChest::getInstance ()->getScheduler ()->scheduleDelayedTask (new ClosureTask (function () use ($player, $x, $y, $z) : void
    {
      $pk = new ContainerOpenPacket ();
      $pk->x = $x;
      $pk->y = $y;
      $pk->z = $z;
      $pk->windowId = $player->getWindowId ($this);
      $pk->type = WindowTypes::CONTAINER;
      $player->sendDataPacket ($pk);

      $this->sendContents ($player);
    }), 10);
  }

  public function onClose (Player $player) : void
  {
    BaseInventory::onClose ($player);

    $res = [];

    for ($i=0; $i<=VirtualChest::getData ($player) [0]; $i++)
    {
      $res [] = $this->getItem ($i)->jsonSerialize ();
    }

    VirtualChest::setData ($player, count ($res) - 1, $res);

    $x = $this->vector->x;
    $y = $this->vector->y;
    $z = $this->vector->z;

    for ($i=0; $i<=1; $i++)
    {
      $block = $player->level->getBlock (new Vector3 ($x + $i, $y, $z));
      $pk = new UpdateBlockPacket ();
      $pk->x = $x + $i;
      $pk->y = $y;
      $pk->z = $z;
      $pk->blockRuntimeId = RuntimeBlockMapping::toStaticRuntimeId ($block->getId (), $block->getDamage ());
      $pk->flags = UpdateBlockPacket::FLAG_ALL_PRIORITY;
      $player->sendDataPacket ($pk);

      $tile = $block->level->getBlock ($block);

      if ($tile instanceof Spawnable)
      {
        $player->sendDataPacket ($tile->createSpawnPacket ());
      }else
      {
        $pk = new BlockActorDataPacket ();
        $pk->x = $x;
        $pk->y = $y;
        $pk->z = $z + $i;
        $pk->namedtag = (new NetworkLittleEndianNBTStream ())->write (new CompoundTag ());
        $player->sendDataPacket ($pk);
      }
    }
  }
}
