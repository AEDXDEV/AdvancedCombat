<?php

namespace AEDXDEV\Combat;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\entity\projectile\Arrow;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener {
  // [Player1:Player2 => Time]
  public array $combat = [];
  
  public Config $config;
  public bool $Enable = true;
  public int $Time = 10;
  public static $instance;
  
  public function onLoad(): void{
		self::$instance = $this;
	}
	
	public static function getInstance(): Main{
		return self::$instance;
	}
  
	public function onEnable(): void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$config = new Config($this->getDataFolder() . "config.yml", 2, [
		  "Enable" => true,
		  "Time" => 10,
		]);
	  $this->Enable = $config->get("Enable", false);
	  $this->Time = $config->get("Time", 10);
	}
	
	public function onDamage(EntityDamageEvent $event){
		$entity = $event->getEntity();
	  if($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player && ($entity = $event->getEntity()) instanceof Player && $this->Enable){
	    $this->addCombat($damager, $entity);
		}
	}
	
	public function onProjectileHit(ProjectileHitEntityEvent $event) {
    if(($arrow = $event->getEntity()) instanceof Arrow && ($owner = $arrow->getOwningEntity()) instanceof Player && ($target = $event->getEntityHit()) instanceof Player) {
      $this->addCombat($owner, $target);
    }
  }
	
	public function onDeath(PlayerDeathEvent $event) {
	  $player = $event->getPlayer();
	  if ($this->hasCombat($player)) {
	    $this->unCombat($player);
	  }
	}
  
	public function addCombat(Player $damager, Player $entity) {
	  if ($this->hasCombat($damager) || $this->hasCombat($entity)){
	    $this->unCombat($damager);
	    $this->unCombat($entity);
	  }
	  $players = $damager->getName() . ":" . $entity->getName();
	  $this->combat[$players] = $this->Time;
	}
	
	public function addCombatName(string $players) {
	  $p = explode(":", $players);
	  if ($this->hasCombatName($p[0]) || $this->hasCombatName($p[1])){
	    $this->unCombatName($p[0]);
	    $this->unCombatName($p[1]);
	  }
	  $players = $p[0] . ":" . $p[1];
	  $this->combat[$players] = $this->Time;
	}
	
	public function unCombat(Player $player) {
	  if (!$this->hasCombat($player))return false;
	  $name = $player->getName();
		foreach($this->combat as $players => $time) {
		  $p = explode(":", $players);
		  if ($name === $p[0] or $name === $p[1]) {
		    unset($this->combat[$players]);
		  }
		}
	}
	
	public function unCombatName(string $players) {
	  $p = explode(":", $players);
	  if (!$this->hasCombatName($p[0]))return false;
	  if (!$this->hasCombatName($p[1]))return false;
		unset($this->combat[$players]);
	}
	
	public function hasCombat(Player $player): bool{
	  $name = $player->getName();
	  $bool = false;
	  foreach ($this->combat as $players => $time) {
	    $p = explode(":", $players);
	    if ($name === $p[0] or $name === $p[1]) {
	      $bool = true;
	    }
	  }
	  return $bool;
	}
	
	public function hasCombatName(string $name): bool{
	  $bool = false;
	  foreach ($this->combat as $players => $time) {
	    $p = explode(":", $players);
	    if ($name === $p[0] or $name === $p[1]) {
	      $bool = true;
	    }
	  }
	  return $bool;
	}
	
	public function CombatTask() {
	  foreach ($this->combat as $players => $time){
      if($time == 0){
        $this->unCombatName($players);
        } else {
          $this->combat[$players]--;
        }
		}
	}
}
