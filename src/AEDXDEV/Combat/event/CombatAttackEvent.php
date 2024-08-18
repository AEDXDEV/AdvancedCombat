<?php

namespace AEDXDEV\Combat\event;

declare(strict_types=1);

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

/*
 * Called when a player is in combat attacked
 */

class CombatAttackEvent extends CombatEvent implements Cancellable{
  use CancellableTrait;
  
  public function __construct(
    protected Player $damager,
    protected Player $entity,
    protected bool $sameCombat
  ){
  }
  
  public function getDamager(): Player{
    return $this->damager;
  }
  
  public function getEntity(): Player{
    return $this->entity;
  }
  
  public function inInSameCombat(): bool{
    return $this->sameCombat;
  }
}