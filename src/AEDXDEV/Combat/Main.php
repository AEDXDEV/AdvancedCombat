<?php

namespace AEDXDEV\Combat;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\entity\projectile\Projectile;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

use AEDXDEV\Combat\event\CombatStartEvent;
use AEDXDEV\Combat\event\CombatAttackEvent;
use AEDXDEV\Combat\event\CombatEndEvent;

class Main extends PluginBase implements Listener {
  
  use SingletonTrait;
  
  // [id => [player1, player2, time, hidePlayers, penalty]]
  private array $combat = [];
  
  public Config $config;
  
  private bool $isPluginEnabled = true;
  private bool $sameCombat = false;
  private bool $hidePlayers = false;
  //private array $hideAllPlayers = [];
  private bool $penalty = false;
  private array $penalties = [];
  private bool $sendMessages = true;
  private array $messages = [];
  private int $combatTime = 10;
  
	public function onEnable(): void{
	  self::setInstance($this);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
		  "Enable" => true,
		  "CancelIfNotInSameCombat" => false,
		  "Penalty" => false,
		  "HidePlayers" => false,
		  "Penalties" => [
		    "Health" => 5,
		    "ItemLoss" => true
		  ],
		  "sendMessages" => true,
		  "Messages" => [
		    "Start" => "§eYou are now in combat with {PLAYER}",
		    "InSameCombat" => "§cYou cannot proceed because you are not fighting the same opponent. Please focus on your current combat!",
		    "End" => "§eYou are no longer in combat.",
		    "QuitPenalty" => "§cYou have been penalized for leaving combat."
		  ],
		  "Time" => 10,
		]);
		$this->config = $config;
	  $this->isPluginEnabled = $config->get("Enable", false);
	  $this->sameCombat = $config->get("CancelIfNotInSameCombat", false);
	  $this->hidePlayers = $config->get("HidePlayers", false);
	  $this->penalty = $config->get("Penalty", false);
	  $this->penalties = $config->get("Messages", [
		  "Health" => 5,
		  "ItemLoss" => true
	  ]);
	  $this->sendMessages = $config->get("sendMessages", false);
	  $this->messages = $config->get("Messages", [
	    "Start" => "§eYou are now in combat with {PLAYER}",
	    "InSameCombat" => "§cYou cannot proceed because you are not fighting the same opponent. Please focus on your current combat!",
	    "End" => "§eYou are no longer in combat.",
	    "QuitPenalty" => "§cYou have been penalized for leaving combat."
	  ]);
	  $this->combatTime = $config->get("Time", 10);
	  $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(): void{
	    $this->handleCombatTask();
		}), 20);
		
	}
	
	public function onDamage(EntityDamageEvent $event): void{
	  if ($event instanceof EntityDamageByEntityEvent) {
	    $damager = $event->getDamager();
	    $entity = $event->getEntity();
	    if (!$this->isPluginEnabled || $event->isCancelled())return;
	    if ($event instanceof EntityDamageByChildEntityEvent) {
	      $projectile = $event->getChild();
	      if ($projectile !== null && !$projectile instanceof Projectile)return;
	    }
	    if ($damager instanceof Player && $entity instanceof Player) {
	      if ($damager->isCreative() || $entity->isCreative())return;
	      if($entity->getHealth() <= $event->getFinalDamage()){
	        $this->removeCombat($entity);
	        return;
	      }
	      if ($this->isInCombat($entity) || $this->isInCombat($damager)){
	        $sameCombat = false;
	        if ($this->getCombat($damager) === $this->getCombat($entity)) {
      	    $combat = $this->getCombat($damager);
      	    $combat["Time"] = $this->combatTime;
      	    $this->combat[$this->getCombatId($damager)] = $combat;
      	    $sameCombat = true;
      	  }
      	  $event = new CombatAttackEvent($damager, $entity, $sameCombat);
      	  $event->call();
      	  if ($event->isCancelled() || $this->sameCombat && !$sameCombat) {
      	    if ($this->sameCombat && $this->sendMessages) {
      	      $damager->sendMessage($this->messages["InSameCombat"]);
      	    }
      	    $event->cancel();
      	    return;
      	  }
	      }
	      $this->addCombat($damager, $entity);
	    }
	  }
	}
  
  public function onQuit(PlayerQuitEvent $event){
    $player = $event->getPlayer();
    if ($this->isInCombat($player)) {
      if ($this->getCombat($player)["Penalty"]) {
        $this->applyPenalty($player);
      }
      $this->removeCombat($player);
    }
  }
  
  private function isInCombat(Player $player): bool{
	  foreach ($this->combat as $id => $data) {
	    if (in_array($player->getName(), [$data["Player1"], $data["Player2"]])) {
	      return true;
	    }
	  }
    return false;
  }
	
	public function addCombat(Player $player1, Player $player2): void{
	  $event = new CombatStartEvent($player1, $player2, $this->combatTime, $this->hidePlayers);
	  $event->setPenalty($this->penalty);
	  $event->call();
	  if (!$event->isCancelled()) {
	    if ($player2->getCurrentWindow() !== null){
  	    $player2->removeCurrentWindow();
  	  }
	    $this->combat[] = [
  	    "Player1" => $player1->getName(),
  	    "Player2" => $player2->getName(),
  	    "Time" => $event->getTime(),
  	    "HidePlayers" => $event->getHidePlayers(),
  	    "Penalty" => $event->getPenalty()
  	  ];
  	  if ($this->hidePlayers || $event->getHidePlayers()) {
    	  $this->handlePlayerVisibility($player1, $player2, true);
	    }
  	  if ($this->sendMessages) {
  	    $msg = str_replace("{PLAYER}", "", $this->messages["Start"]);
  	    $player1->sendMessage($msg . $player2->getName());
  	    $player2->sendMessage($msg . $player1->getName());
  	  }
	  }
  }
  
  private function handlePlayerVisibility(Player $player1, Player $player2, bool $hide): void {
    foreach ([$player1, $player2] as $p) {
      foreach ($this->getServer()->getOnlinePlayers() as $pp) {
        if ($pp !== $player1 && $pp !== $player2) {
          if ($hide) {
            $p->hidePlayer($pp);
          } else {
            $p->showPlayer($pp);
          }
        } else {
          // Ensure the combat players can see each other
          if (!$hide) {
            $p->showPlayer($player1);
            $p->showPlayer($player2);
          }
        }
      }
    }
  }
  
  private function applyPenalty(Player $player): void{
    if ($this->penalties["Health"] > 0) {
      $health = $player->getHealth() - $this->penalties["Health"];
      if ($health < 0)return;
      $player->setHealth($health);
    }
    if ($this->penalties["ItemLoss"]) {
      $player->getInventory()->clearAll();
    }
    $player->sendMessage($this->messages["QuitPenalty"]);
  }
  
  public function getCombat(Player $player): ?array{
    return $this->combat[$this->getCombatId($player) ?? -1] ?? null;
  }
  
  public function getCombatId(Player $player): ?int{
    foreach ($this->combat as $id => $data) {
	    if (in_array($player->getName(), [$data["Player1"], $data["Player2"]])) {
	      return $id;
	    }
	  }
	  return null;
  }
  
  public function getPlayerCombat(Player $player): ?Player{
    $playerName = $player->getName();
    $target = null;
    foreach ($this->combat as $id => $data) {
      [$name1, $name2] = $data;
      if ($playerName === $name1) {
        $target = $name2;
      } elseif ($playerName === $name2) {
        $target = $name1;
      }
    }
    return $target !== null ? $this->getServer()->getPlayerExact($target) : null;
  }
  
  public function removeCombat(Player $player): void{
    $name = $player->getName();
    foreach ($this->combat as $id => $data) {
      if (in_array($player->getName(), [$data["Player1"], $data["Player2"]])) {
        $player1 = $this->getPlayer($data["Player1"]);
        $player2 = $this->getPlayer($data["Player2"]);
        $event = new CombatEndEvent($player1, $player2);
        $event->call();
        unset($this->combat[$id]);
        if ($this->hidePlayers) {
          $this->handlePlayerVisibility($player1, $player2, false);
        }
        if ($this->sendMessages) {
         $player1->sendMessage($this->messages["End"]);
         $player2->sendMessage($this->messages["End"]);
        }
      }
    }
  }
	
	private function handleCombatTask(): void{
	  foreach ($this->combat as $id => $data) {
	    if ($data["Time"] <= 0) {
	      $this->removeCombat($this->getPlayer($data["Player1"]));
	      //unset($this->combat[$id]);
	    } else {
	      $data["Time"]--;
	      $this->combat[$id] = $data;
	    }
	  }
	}
	
	private function getPlayer(?string $player = null): Player{
    return $this->getServer()->getPlayerExact($player ?? "");
  }
}
