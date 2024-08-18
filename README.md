# Combat Plugin
This plugin prevents players from escaping during combat, manages combat interactions, and handles penalties for leaving combat.

## Features
- **Combat Management**: Automatically adds players to combat when they attack each other and removes them after a certain time.
- **Combat Penalty**: Penalties for players leaving combat include health reduction or item loss.
- **Same Combat Enforcement**: Optionally cancel interactions if players aren't engaged with the same opponent.
- **Messages**: Sends customizable messages during combat events.
- **Combat Events**: Custom events for when combat starts, attacks happen during combat, and when combat ends.

## API

### Checking Combat Status
```php
use AEDXDEV\Combat\Main as Combat;

// Check if a player is in combat
// Returns true if the player is in combat, false otherwise
Combat::getInstance()->isInCombat($player);
```

### Adding Players to Combat
```php
use AEDXDEV\Combat\Main as Combat;

// Add two players to combat
Combat::getInstance()->addCombat($player1, $player2);

```

### Retrieving Combat Information
```php
use AEDXDEV\Combat\Main as Combat;

// Get the player who is in combat with the specified player
// Returns the opponent player or null if not found
Combat::getInstance()->getPlayerCombat($player);
```

### Removing Players from Combat
```php
use AEDXDEV\Combat\Main as Combat;

// Remove a player from combat
Combat::getInstance()->removeCombat($player);
```

### Handling Combat Events
Custom combat events are fired during combat interactions:
- CombatStartEvent: When combat begins between two players.
- CombatAttackEvent: When one player attacks another during combat.
- CombatEndEvent: When combat ends between two players.

### Penalty System
If a player leaves combat, they may be penalized based on the configuration:
- Health Reduction: Reduces player health by a specified amount.
- Item Loss: Clears the player's inventory if enabled.

### How to use Events
```php
use AEDXDEV\Combat\Main as Combat;
use AEDXDEV\Combat\event\CombatStartEvent;
use AEDXDEV\Combat\event\CombatAttackEvent;
use AEDXDEV\Combat\event\CombatEndEvent;
// Called a player attack a player
public function onCombatStart(CombatStartEvent $event): void{
  $player1 = $event->getPlayer1(); // damager
  $player2 = $event->getPlayer2(); // victim
  $time = $event->getTime();
  // change the time
  $event->setTime(10); // 10 seconds
  // use penalty
  if (!$event->getPenalty()){
    $event->setPenalty(true);
  }
  // for 1vs1
  if (!$event->getHidePlayers()){
    $event->setHidePlayers(true);
  }
}
// Called when a player is in combat attacked
public function onCombatAttack(CombatAttackEvent $event): void{
  $damager = $event->getDamager();
  $entity = $event->getEntity(); // victim
  if (!$event->inInSameCombat()){ // attack a player not 
    $event->cancel();
  }
}
// Called when the combat time ends
public function onCombatEnd(CombatEndEvent $event): void{
  $player1 = $event->getPlayer1();
  $player2 = $event->getPlayer2();
}
```

## Configuration
Configure the plugin through `config.yml`:

```yaml
Enable: true # Enables or disables the plugin.
CancelIfNotInSameCombat: false # Cancels actions if players are not engaged in the same combat.
Penalty: false # Enables or disables penalties for leaving combat.
Penalties:
  Health: 5 # The amount of health to reduce when penalizing a player.
  ItemLoss: true # Whether the player will lose their items upon leaving combat.
sendMessages: true # Enables or disables messages during combat events.
Messages:
  Start: "§eYou are now in combat with {PLAYER}" # When combat begins.
  InSameCombat: "§cYou cannot proceed because you are not fighting the same opponent!" # When a player tries to attack a different opponent.
  End: "§eYou are no longer in combat." # When combat ends.
  QuitPenalty: "§cYou have been penalized for leaving combat." # When a player is penalized for leaving combat.
Time: 10 # Duration of the combat period (in seconds).
```

## Other
- [YouTube Channel](https://youtube.com/@AEDXDEV)
