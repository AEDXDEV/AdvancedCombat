# Combat
To prevent the player from escaping during combat and knowing if the player is in Combat or not

# API
```php
use AEDXDEV\Combat\Main as Combat;

// To knowing if the player is in Combat or not
/*return true or false if it exists*/
Combat::getInstance()->hasCombat($player);

// To knowing if the playername is in Combat or not
/*return true or false if it exists*/
Combat::getInstance()->hasCombatName($name);

// To add the players to Combat
Combat::getInstance()->addCombat($player1, $player2);

// To add the names of the players to Combat
Combat::getInstance()->addCombatName("playername1:playername2");

// To get the damager of the player
/*return $player or null*/
Combat::getInstance()->getPlayerCombat($player);

// To remove the player from Combat
/*return false if it not exists*/
Combat::getInstance()->unCombat($player);

// To remove the names of the players from Combat
/*return false if it not exists*/
Combat::getInstance()->unCombatPlayers("playername1:playername2");
```

# Config
```yaml
Enable: true
Time: 10
```

# Other
- [YouTube](https://youtube.com/@AEDXDEV)