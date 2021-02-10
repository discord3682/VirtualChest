<?php

namespace discord3682\virtualchest;

use discord3682\virtualchest\inventory\PluginInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\Listener;
use pocketmine\scheduler\ClosureTask;
use pocketmine\item\Item;
use pocketmine\form\Form;
use pocketmine\Player;

class EventListener implements Listener
{

  public function onPlayerJoin (PlayerJoinEvent $ev) : void
  {
    $player = $ev->getPlayer ();

    if (VirtualChest::getData ($player) === null)
    {
      VirtualChest::setData ($player);
    }
  }

  public function onInventoryTransaction (InventoryTransactionEvent $ev) : void
  {
		$player = $ev->getTransaction ()->getSource ();
    $actions = $ev->getTransaction ()->getActions ();

    foreach ($actions as $action)
    {
			if ($action instanceof SlotChangeAction)
      {
				$inv = $action->getInventory ();

				if ($inv instanceof PluginInventory)
        {
          $data = VirtualChest::getData ($player);

          if ($data [0] < $action->getSlot ())
          {
            $ev->setCancelled (true);
            $inv->close ($player);

            VirtualChest::getInstance ()->getScheduler ()->scheduleDelayedTask (new ClosureTask (function (int $currentTick) use ($player) : void
            {
              $player->sendForm (new class ($player) implements Form
              {

                private $player;

                public function __construct (Player $player)
                {
                  $this->player = $player;
                }

                public function jsonSerialize () : array
                {
                  return [
                    'type' => 'modal',
                    'title' => '§l§0가상창고 슬롯 구매',
                    'content' => "\n" . '§b' . (VirtualChest::getData ($this->player) [0] + 2) . '§r§f 번째 슬롯을 구매하시겠습니까?' . "\n",
                    'button1' => '§l§a> YES <',
                    'button2' => '§l§c> NO <'
                  ];
                }

                public function handleResponse (Player $player, $data) : void
                {
                  if (is_null ($data)) return;

                  if ($data)
                  {
                    if ($player->getInventory ()->contains (Item::get (399, 0, 3)))
                    {
                      $player->getInventory ()->removeItem (Item::get (399, 0, 3));
                      VirtualChest::setData ($player, (VirtualChest::getData ($player) [0] + 1), VirtualChest::getData ($player) [1]);
                      VirtualChest::msg ($player, '§b' . (VirtualChest::getData ($player) [0] + 2) . '§r§7 번째 슬롯을 구매하셨습니다.');
                    }else
                    {
                      VirtualChest::msg ($player, '네더의 별이 부족합니다.');
                    }
                  }else
                  {
                    VirtualChest::msg ($player, '슬롯 구매를 취소하셨습니다.');
                  }
                }
              });
            }), 10);
          }

          if ($ev->isCancelled ())
          {
      			$player->getCursorInventory ()->sendSlot (0, $player);
      		}
				}
			}
		}
	}
}
