<?php

namespace rank;

use pocketmine\Server;
use pocketmine\player\Player;

use rank\EventListener;
use pocketmine\plugin\PluginBase;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use ramsey\uuid\Uuid;
use pocketmine\utils\Config;
use pocketmine\scheduler\Closure;
use pocketmine\permission\PermissionManager;

class Ranks extends pluginBase 
{
  
  /** @var Players */
  private $player;
  
  /** @var Ranks */
  private $ranks;
  
  /** @var Attachment */
  private $attachments = [];
  
  /** @var Instance */
  private static $instance;
  
  public function onEnable(): void 
  {
    //Code 
    $this->saveResource("ranks.yml");
    $this->saveResource("players.yml");
    //Variables 
    self::$instance = $this;
    $this->ranks = new Config($this->getDataFolder() . "ranks.yml", Config::YAML, array());
    $this->players = new Config($this->getDataFolder() . "players.yml", Config::YAML, array());
    
    if(empty($this->ranks->getAll()))
    {
      $this->ranks->setNested("Default.isDefault", true);
      $this->ranks->setNested("Default.Alisa", "Default");
      $this->ranks->setNested("Default.ChatFormat", "&r(&8Default&r) &r{player_name} &7> &r{msg}");
      $this->ranks->setNested("Default.NameFormat", "&r(&8Default&r) &r{player_name}");
      $this->ranks->setNested("Default.Permissions", []);
      $this->ranks->setNested("Admin.isDefault", false);
      $this->ranks->setNested("Admin.Alisa", "Admin");
      $this->ranks->setNested("Admin.ChatFormat", "&r(&cAdmin&r) &a{player_name} &1>> &6{msg}");
      $this->ranks->setNested("Admin.NameFormat", "&r(&cAdmin&r) &a{player_name}");
      $this->ranks->setNested("Admin.Permissions", ["pocketmine.command.gamemode", "pocketmine.command.kick", "pocketmine.command.give", "pocketmine.command.teleport", "pocketmine.command.ban.player", "pocketmine.command.unban.player", "pocketmine.command.ban.list"]);
      $this->ranks->setNested("Owner.isDefault", false);
      $this->ranks->setNested("Owner.Alisa", "Owner");
      $this->ranks->setNested("Owner.ChatFormat", "&r(&4Owner&r) &b{player_name} &c>&a>&9> &e{msg}");
      $this->ranks->setNested("Owner.NameFormat", "&r(&4Owner&r) &b{player_name}");
      $this->ranks->setNested("Owner.Permissions", ["*"]);
      $this->ranks->save();
    }
    //Register Events
    $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
  }
  
  public static function getInstance(): Ranks 
  {
    return self::$instance;
  }
  
  public function onCommand(CommandSender $player, Command $cmd, string $label, array $args): bool 
  {
    switch ($cmd->getName())
    {
      case 'rank':
        if(count($args) === 1)
        {
          if($args[0] === "list")
          {
            $player->sendMessage("§aRanks: §e" . implode("§8, §e", $this->getRankList()));
          }elseif($args[0] === "add")
          {
            $player->sendMessage("§eusage: §a/rank add <rank name>");
          }elseif($args[0] === "del")
          {
            $player->sendMessage("§eusage: §a/rank del <rank name>");
          }elseif($args[0] === "set")
          {
            $player->sendMessage("§eusage: §a/rank set <player name> <rank name>");
          }elseif($args[0] === "addperm")
          {
            $player->sendMessage("§eusage: §a/rank addperm <rank name> <permission>");
          }elseif($args[0] === "delperm")
          {
            $player->sendMessage("§eusage: §a/rank delperm <rank name> <permission>");
          }elseif($args[0] === "format")
          {
            $player->sendMessage("§eusage: §a/rank format [name|chat] <rank> <format>");
          }
        }elseif(count($args) === 2)
        {
          if($args[0] === "add")
          {
            if($this->addRankToData($args[1]))
            {
              $player->sendMessage("§aSuccessfully Added Rank");
            }else{
              $player->sendMessage("§cError Can't Add Rank");
            }
          }elseif($args[0] === "del")
          {
            if(count($this->ranks->getAll()) !== 1 && $args[1] !== $this->getDefaultRank())
            {
              if($this->removeRankFromData($args[1])) 
              {
                $player->sendMessage("§aSuccessfully Deleted The Rank");
              }else{
                $player->sendMessage("§cError Can't Delete Rank");
              }
            }else{
              $player->sendMessage("§cError Can't Delete The Default Rank");
            }
          }elseif($args[0] === "set")
          {
            if($this->setPlayerRank($player->getName(), $args[1]))
            {
              $player->sendMessage("§aYour Rank Has Been Set");
            }else{
              $player->sendMessage("§cSomething Went Wrong");
            }
          }elseif($args[0] === "addperm")
          {
            $player->sendMessage("§eusage: §a/rank addperm <rank name> <permission>");
          }elseif($args[0] === "delperm")
          {
            $player->sendMessage("§eusage: §a/rank delperm <rank name> <permission>");
          }elseif($args[0] === "format")
          {
            $player->sendMessage("§eusage: §a/rank format [name|chat] <rank> <format>");
          }
        }elseif(count($args) === 3)
        {
          if($args[0] === "set")
          {
            if($this->setPlayerRank($args[1], $args[2]))
            {
              $player->sendMessage("§e".$args[1]." §aRank Has Been Set");
            }else{
              $player->sendMessage("§cSomething Went Wrong");
            }
          }elseif($args[0] === "addperm")
          {
            if($this->addPermissionToRank($args[1], $args[2]))
            {
              $player->sendMessage("§aSuccessfully Added Permission To Rank");
            }else{
              $player->sendMessage("§cError Can't Add Permission");
            }
          }elseif($args[0] === "delperm")
          {
            if($this->removePermissionFromRank($args[1], $args[2]))
            {
              $player->sendMessage("§aSuccessfully Removed Permission To Rank");
            }else{
              $player->sendMessage("§cError Can't Remove Permission");
            }
          }elseif($args[0] === "format")
          {
            $player->sendMessage("§eusage: §a/rank format [name|chat] <rank> <format>");
          }
        }elseif(count($args) >= 4)
        {
          if($args[0] === "format")
          {
            if($args[1] === "name")
            {
              $format = implode(" ", array_slice($args, 3)); 
              if($this->setNameFormat($args[2], $format))
              {
                $player->sendMessage("§aSuccessfully Changed The Format");
              }else{
                $player->sendMessage("§cError Can't Change Format");
              }
            }elseif($args[1] === "chat")
            {
              $format = implode(" ", array_slice($args, 3)); 
              if($this->setChatFormat($args[2], $format))
              {
                $player->sendMessage("§aSuccessfully Changed The Format");
              }else{
                $player->sendMessage("§cError Can't Change Format");
              }
            }
          }else{
            $player->sendMessage("§eusage: §a/rank [add|del|set|addperm|delperm|format]");
          }
        }else{
          $player->sendMessage("§eusage: §a/rank [add|del|set|addperm|delperm|format]");
        }
        return true;
        break;
    }
    return false;
  }
  
  public function getPlayers()
  {
    if(!empty($this->players->getAll()))
    {
      $data = $this->players->getAll();
      return $data;
    }else{
      return null;
    }
  }
  
  public function getRanks()
  {
    if(!empty($this->ranks->getAll()))
    {
      $data = $this->ranks->getAll();
      return $data;
    }else{
      return null;
    }
  }
  
  public function getRankList()
  {
    $list = [];
    foreach($this->getRanks() as $rank)
    {
      $list[] = $rank["Alisa"];
    }
    return $list;
  }
  
  public function addPlayerToData(string $playerName)
  {
    $this->players->setNested("$playerName.Alisa", $playerName);
    $this->players->setNested("$playerName.Rank", $this->getDefaultRank());
    $this->players->save();
  }
  
  public function addRankToData(string $rankName): bool
  {
    if(empty($this->ranks->get($rankName)) && $rankName !== "del" && $rankName !== "add" && $rankName !== "set")
    {
      $this->ranks->setNested("$rankName.isDefault", false);
      $this->ranks->setNested("$rankName.Alisa", $rankName);
      $this->ranks->setNested("$rankName.ChatFormat", "&r(&8$rankName&r) &r{player_name} &7> &r{msg}");
      $this->ranks->setNested("$rankName.NameFormat", "&r(&8$rankName&r) &r{player_name}");
      $this->ranks->setNested("$rankName.Permissions", []);
      $this->ranks->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function setPlayerRank(string $playerName, string $rankName) 
  {
    if(!empty($this->ranks->get($rankName)))
    {
      if(!empty($this->players->get($playerName)))
      {
        $this->players->setNested("$playerName.Rank", $rankName);
        $this->players->save();
        $player = $this->getServer()->getPlayerExact($playerName);
        if($player instanceof Player)
        {
          $this->addPermissionsForPlayer($player);
        }
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function getRankOfPlayer(string $playerName)
  {
    if(!empty($this->players->get($playerName)))
    {
      $data = $this->players->getNested("$playerName.Rank");
      return $data;
    }else{
      return null;
    }
  }
  
  public function removeRankFromData(string $rankName): bool
  {
    if(!empty($this->ranks->get($rankName)))
    {
      $this->ranks->remove($rankName);
      foreach($this->players->getAll() as $player)
      {
        if($player["Rank"] === $rankName)
        {
          $this->players->remove($player["Alisa"]);
          $this->addPlayerToData($player["Alisa"]);
        }
      }
      return true;
    }else{
      return false;
    }
  }
  
  public function addPermissionToRank(string $rankName, string $permission): bool
  {
    if(!empty($this->ranks->get($rankName)))
    {
      $perms = [];
      $permissions = $this->ranks->getNested("$rankName.Permissions");
      $nested = false;
      foreach($permissions as $key)
      {
        $perms[] = $key;
        if($key === $permission)
        {
          $nested = true;
        }
      }
      if(!$nested)
      {
        $perms[] = $permission;
      }
      $this->ranks->setNested("$rankName.Permissions", $perms);
      $this->ranks->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function removePermissionFromRank(string $rankName, string $permission)
  {
    if(!empty($this->ranks->get($rankName)))
    {
      $perms = [];
      $permissions = $this->ranks->getNested("$rankName.Permissions");
      foreach($permissions as $key)
      {
        if($key !== $permission)
        {
          $perms[] = $key;
        }
      }
      $this->ranks->setNested("$rankName.Permissions", $perms);
      $this->ranks->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function getPermissionsOfRank(string $rankName) 
  {
    if(!empty($this->ranks->get($rankName)))
    {
      $data = $this->ranks->getNested("$rankName.Permissions");
      return $data;
    }
  }
  
  public function getDefaultRank()
  {
    foreach($this->getRanks() as $rank)
    {
      if($rank["isDefault"])
      {
        return $rank["Alisa"];
      }
    }
  }
  
  public function getChatFormat(string $rankName)
  {
    if(!empty($this->ranks->get($rankName)))
    {
      $data = $this->ranks->getNested("$rankName.ChatFormat");
      return $data;
    }
    return null;
  }
  
  public function setChatFormat(string $rankName, string $format): bool
  {
    if(!empty($this->ranks->get($rankName)))
    {
      $this->ranks->setNested("$rankName.ChatFormat", "$format");
      $this->ranks->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function getNameFormat(string $rankName) 
  {
    if(!empty($this->ranks->get($rankName)))
    {
      $data = $this->ranks->getNested("$rankName.NameFormat");
      return $data;
    }
    return null;
  }
  
  public function setNameFormat(string $rankName, string $format): bool
  {
    if(!empty($this->ranks->get($rankName)))
    {
      $this->ranks->setNested("$rankName.NameFormat", "$format");
      $this->ranks->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function addPermissionsForPlayer(Player $player)
  {
    $rankName = $this->getRankOfPlayer($player->getName());
    $perms = [];
    if($this->getPermissionsOfRank($rankName) !== [])
    {
      foreach($this->getPermissionsOfRank($rankName) as $permission)
      {
        if($permission === "*")
        {
          foreach(PermissionManager::getInstance()->getPermissions() as $tmp)
          {
            $perms[$tmp->getName()] = true;
          }
        }else{
          $perms[$permission] = true;
        }
          $this->attachments[$player->getName()] = $player->addAttachment($this);
          $this->attachments[$player->getName()]->clearPermissions();
          $this->attachments[$player->getName()]->setPermissions($perms);
      }
    }
  }
  
  public function removeAttach(Player $player)
  {
    if(array_key_exists($player->getName(), $this->attachments))
    {
      $player->removeAttachment($this->attachments[$player->getName()]);
    }
  }
  
}