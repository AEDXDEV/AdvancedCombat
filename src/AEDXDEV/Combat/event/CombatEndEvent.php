<?php

namespace AEDXDEV\Combat\event;

declare(strict_types=1);

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

/*
 * Called when the combat time ends
 */

class CombatEndEvent extends CombatEvent{
  
  public function __construct(
    protected Player $player1,
    protected Player $player2
  ){
  }

  public function getPlayer1(): Player{
    return $this->player1;
  }
  
  public function getPlayer2(): Player{
    return $this->player2;
  }
}