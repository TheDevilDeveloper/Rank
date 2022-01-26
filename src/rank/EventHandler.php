<?php

namespace rank;

use pocketmine\Server;
use pocketmine\player\Player;

use rank\Ranks;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerChatEvent;

class EventHandler implements Listener
{
  /** @var Players */
  private $players;
  
  /** @var Groups */
  private $ranks;
  
  public function __construct(Ranks $source)
  {
    //Variables
    $this->source = $source;
    $this->ranks = $this->source->getInstance()->getRanks();
    $this->players = $this->source->getInstance()->getPlayers();
  }
  
  public function onJoin(PlayerJoinEvent $event)
  {
    //Variables 
    $player = $event->getPlayer();
    $playerName = $player->getName();
    
    //Code
    if(empty($this->players[$playerName]))
    {
      $this->source->addPlayerToData($playerName);
    }
    $format = $this->source->getInstance()->getNameFormat($this->source->getInstance()->getRankOfPlayer($playerName));
    $finalFormat = str_replace(["&", "{player_name}"], ["§", $playerName], $format);
    $player->setNameTag($finalFormat);
    $this->source->addPermissionsForPlayer($player);
  }
  
  public function onQuit(PlayerQuitEvent $event)
  {
    $player = $event->getPlayer(); 
    
    $this->source->getInstance()->removeAttach($player);
  }
  
  public function onChat(PlayerChatEvent $event)
  {
    //Variables 
    $msg = $event->getMessage();
    $player = $event->getPlayer();
    $playerName = $player->getName();
    
    //Code 
    $format = $this->source->getInstance()->getChatFormat($this->source->getInstance()->getRankOfPlayer($playerName));
    $finalFormat = str_replace(["&", "{player_name}", "{msg}"], ["§", $playerName, $msg], $format);
    $event->setFormat($finalFormat);
  }
  
}