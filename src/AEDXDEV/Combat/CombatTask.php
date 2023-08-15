<?php

namespace AEDXDEV\Combat;

use pocketmine\scheduler\Task;

class CombatTask extends Task {
	
	/** @var Main */
	private $plugin;
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	public function onRun(): void{
	  $this->plugin->CombatTask();
	}
}
