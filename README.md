# Combat
To prevent the player from escaping during combat and knowing if the player is in Combat or not

# API
```php
use AEDXDEV\Combat\Main as Combat;

// To knowing if the player is in Combat or not
Combat::getInstaance()->hasCombat($player);

// To knowing if the playername is in Combat or not
Combat::getInstaance()->hasCombatName($name);

// To add the players to Combat
Combat::getInstaance()->addCombat($player1, $player2);

// To add the names of the players to Combat
Combat::getInstaance()->addCombatBame("playername1:playername2");

// To remove the player from Combat
Combat::getInstaance()->unCombat($player);

// To remove the names of the players from Combat
Combat::getInstaance()->unCombatPlayers("playername1:playername2");
```

# Config
```yaml
Enable: true
Time: 10
BannedCommands:
  - /kill
  - /tp
```

# Other
- [![https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/YouTube_full-color_icon_%282017%29.svg/1024px-YouTube_full-color_icon_%282017%29.svg.png](https://youtube.com/@AEDXDEV)]
