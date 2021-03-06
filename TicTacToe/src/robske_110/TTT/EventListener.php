<?php

namespace robske_110\TTT;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\tile\Sign;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\network\mcpe\protocol\BatchPacket;

use robske_110\TTT\Game\Game;
use robske_110\TTT\Game\Arena;

class EventListener implements Listener{
	/** @var TicTacToe */
	private $main;
	/** @var array */
	private $arenaCreationSessions;

	public function __construct(TicTacToe $main){
		$this->main = $main;
	}
    
	public function onItemFrameItemSet(PlayerInteractEvent $event){
		if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			if(($game = $this->main->getPlayerManager()->getGameForPlayer($event->getPlayer()->getId())) instanceof Game){
				$event->setCancelled(!$game->onGameMove($event->getPlayer()->getId(), $event->getBlock(), $event->getItem()));
			}
		}
	}
	
	public function onItemFrameItemRemove(DataPacketReceiveEvent $event){
		if($event->getPacket() instanceof ItemFrameDropItemPacket){
			if(($game = $this->main->getPlayerManager()->getGameForPlayer($event->getPlayer()->getId())) instanceof Game){
				if($game->getPositionOnMap(new Vector3($event->getPacket()->x, $event->getPacket()->y, $event->getPacket()->z)) !== null){
					$event->setCancelled();
				}
			}
		}
	}
	
	public function onSignTap(PlayerInteractEvent $event){
		if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			$block = $event->getBlock();
			$player = $event->getPlayer();
			$signTile = $player->getLevel()->getTile($block);
			if($signTile instanceof Sign){
				$sign = $signTile->getText();
				if($sign[0] == '[TTT]'){
					$this->main->getPlayerManager()->addPlayer($event->getPlayer()->getId());
				}
			}
		}	
	}
	
	public function addArenaCreationSession(int $playerID){
		$this->arenaCreationSessions[$playerID] = [];
	}
	
	public function onBlockTap(PlayerInteractEvent $event){
		if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			$player = $event->getPlayer();
			$playerID = $player->getId();
			if(isset($this->arenaCreationSessions[$playerID])){
				$block = $event->getBlock();
				$this->arenaCreationSessions[$playerID][] = $block;
				$aCS = $this->arenaCreationSessions[$playerID];
				if(count($aCS) >= 2){
					if($aCS[0]->x === $aCS[1]->x && $aCS[0]->z === $aCS[1]->z){
						$player->sendMessage("Attempted to create invalid arena!");
					}
					$this->main->getGameManager()->addArena(
						new Arena($aCS[0], $aCS[1], $this)
					);
					$this->main->saveArena($aCS);
					$player->sendMessage("Arena created succesfully!");
					unset($this->arenaCreationSessions[$playerID]);
				}
			}
		}
	}
	
	public function onLeave(PlayerQuitEvent $event){
		$this->main->getPlayerManager()->removePlayer($event->getPlayer()->getId());
	}
}
//Theory is when you know something, but it doesn't work. Practice is when something works, but you don't know why. Programmers combine theory and practice: Nothing works and they don't know why!