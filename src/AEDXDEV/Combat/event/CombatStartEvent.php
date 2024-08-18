<?php

declare(strict_types=1);

namespace AEDXDEV\Combat\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

/*
 * Called a player attack a player
 */

class CombatStartEvent extends CombatEvent implements Cancellable{
  use CancellableTrait;
  
  protected bool $penalty = false;
  
  public function __construct(
    protected Player $player1,
    protected Player $player2,
    protected int $time,
    protected bool $hidePlayers
  ){
  }

  public function getPlayer1(): Player{
    return $this->player1;
  }
  
  public function getPlayer2(): Player{
    return $this->player2;
  }
  
  public function getTime(): int{
    return $this->time;
  }
  
  public function setTime(int $time): void{
    $this->time = $time;
  }
  
  public function getHidePlayers(): bool{
    return $this->hidePlayers;
  }

  public function setHidePlayers(bool $hidePlayers): void{
    $this->hidePlayers = $hidePlayers;
  }
  
  public function getPenalty(): bool{
    return $this->penalty;
  }

  public function setPenalty(bool $penalty): void{
    $this->penalty = $penalty;
  }
}