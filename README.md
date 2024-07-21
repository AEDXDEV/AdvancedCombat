# Combat Plugin
This plugin prevents players from escaping during combat and allows you to check if a player is currently engaged in combat.

## Features
- Prevent players from escaping during combat.
- Check if a player is in combat.
- Manage combat status programmatically.

## API

### Checking Combat Status
```php
use AEDXDEV\Combat\Main as Combat;

// Check if a player is in combat
// Returns true if the player is in combat, false otherwise
Combat::getInstance()->isInCombat($player);
```

### Managing Combat Status
```php
use AEDXDEV\Combat\Main as Combat;

// Add two players to combat
Combat::getInstance()->addCombat($player1, $player2);

```

### Retrieving Combat Information
```php
use AEDXDEV\Combat\Main as Combat;

// Get the player who is in combat with the specified player
// Returns the other player or null if not found
Combat::getInstance()->getPlayerCombat($player);
```

### Checking If Two Players Are in the Same Combat
```php
use AEDXDEV\Combat\Main as Combat;

// Check if two players are in the same combat
// Returns true if the players are in combat with each other, false otherwise
Combat::getInstance()->isInSameCombat($player1, $player2);

### Removing Combat Status
```php
use AEDXDEV\Combat\Main as Combat;

// Remove a player from combat
// Returns false if the player was not in combat
Combat::getInstance()->removeCombat($player);

```

## Configuration
Configure the plugin through `config.yml`:

```yaml
Enable: true # To enable/disable the plugin.
CancelIfNotInSameCombat: false # To cancel actions if players are not in the same combat.
sendMessages: true # To send messages during combat events.
Messages:
  Start: "§eYou are now in combat with {PLAYER}" # When a player enters combat with another player
  InSameCombat: "§cYou cannot proceed because you are not fighting the same opponent. Please focus on your current combat!" # When a player tries to engage a different opponent while in combat
  End: "§eYou are no longer in combat." # When a player's combat status ends
Time: 10 # the combat duration (in secons)
```

## Other
- [YouTube Channel](https://youtube.com/@AEDXDEV)