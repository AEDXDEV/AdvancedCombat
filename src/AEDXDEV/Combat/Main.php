<?php
declare(strict_types=1);

namespace AEDXDEV\Combat;

/**
 *  A paid plugin for PocketMine-MP.
 *	
 *	Copyright (c) AEDXDEV
 *  
 *	Youtube: AEDX DEV
 *	Discord: aedxdev
 *	GitHub: aedxdev
 *	Email: aedxdev@gmail.com
 *	Donate: https://paypal.me/AEDXDEV
 *
 *  This plugin was sold under the AEDXDEV Publication License
 *  
 *  The terms of the license must be adhered to and never violated
 *  any violation of license permissions will not be negotiated.
 *  
 *  You will receive the license file with the plugin
 *  and it will also be inside the plugin.
 *  
 *  Since you have this plugin means that you have purchased it
 *  and you are prohibited from using it
 *  as a commercial product, distributing it, selling it or changing the rights
 *  or the name of the original developer
 *  which is AEDXDEV and it only for private use.
 *   
 */

use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use pocketmine\world\Position;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\utils\SingletonTrait;

use AEDXDEV\Combat\command\CombatSettingsCommand;
use AEDXDEV\Combat\provider\Provider;
use AEDXDEV\Combat\provider\SqlProvider;
use AEDXDEV\Combat\provider\ConfigProvider;
use AEDXDEV\Combat\arena\CombatArenaManager;
use AEDXDEV\Combat\session\SessionManager;
use AEDXDEV\Combat\session\cache\BurnCache;
use AEDXDEV\Combat\session\cache\TntCache;
use AEDXDEV\Combat\listener\EventListener;
use AEDXDEV\Combat\task\CombatTask;

use Vecnavium\FormsUI\CustomForm;

use InvalidArgumentException;

class Main extends PluginBase{
  
  use SingletonTrait;
  
  private const DEFAULT_CONFIG = [
    "Enable" => true,
    "UseAllowedWorldOnly" => false,
    "BlockNonOpponents" => false,
    "AutoActivateTnt" => true,
    "SendMessages" => true,
    "DeathMessages" => true,
    "Messages" => [
      "Start" => "§eYou are now in combat with {PLAYER}",
      "NonOpponents" => "§cYou can only hit your current opponent.",
      "End" => "§eYou are no longer in combat.",
      "Kill" => "§aYou killed §f{PLAYER}§a!",
      "Death" => "§cYou were killed by §c{PLAYER}§c!",
      "BlockedCommand" => "§cYou cannot use this command while in combat!",
      "SaveSettings" => "§aYour settings have been saved!"
    ],
    "Sounds" => [
    	"Start" => [
    		"name" => "random.orb",
    		"volume" => 1.0,
    		"pitch" => 1.0
    	],
    	"Kill" => [
    		"name" => "note.pling",
    		"volume" => 1.0,
    		"pitch" => 1.5
    	],
    	"Death" => [
    		"name" => "note.bass",
    		"volume" => 1.0,
    		"pitch" => 1.0
    	],
    	"End" => [
    		"name" => "note.bass",
    		"volume" => 1.0,
    		"pitch" => 0.7
    	],
    	"Projectile" => [
    		"name" => "random.orb",
    		"volume" => 1.0,
    		"pitch" => 1.0
    	],
    	"SaveSettings" => [
    		"name" => "random.levelup",
    		"volume" => 1.0,
    		"pitch" => 1.0
    	]
    ],
    "AllowedWorld" => [
    	"arena"
    ],
    "BlockedCommands" => [
    	"/hub",
    	"/spawn"
    ],
    "KillCreditWindow" => 10.0,
    "KillCreditTtl" => 10.0,
    "Timer" => 10,
		"DatabaseInfo" => [
			"type" => "mysql",
			"mysql" => [
				"file" => "PlayerSettings.sql",
				"host" => "127.0.0.1",
				"username" => "root",
				"password" => "",
				"schema" => "your_schema",
				"port" => 3306
			],
			"sqlite" => [
				"file" => "PlayerSettings.sql"
			],
			"json" => [
			  "file" => "PlayerSettings.json"
			],
			"yaml" => [
			  "file" => "PlayerSettings.yml"
			],
			"worker-limit" => 1
		]
	];

	public static bool $isPluginEnabled = true;

	/** @var Provider */
	private Provider $provider;
	/** @var CombatArenaManager */
	private CombatArenaManager $arenaManager;
	/** @var SessionManager */
	private SessionManager $sessionManager;
	/** @var BurnCache */
	private BurnCache $burnCache;
	/** @var TntCache */
	private TntCache $tntCache;

	public const PREFIX = "§8[§cCOMBAT§8]";

	public const FORM_PREFIX = "§l§cCOMBAT §8>§r ";

	public const CMD_PREFIX = self::PREFIX . " §f>§c> §r";

	public function onLoad(): void{
		$this->arenaManager = new CombatArenaManager();
	}

	public function onEnable(): void{
    self::setInstance($this);
    $this->initConfig();
    $this->registerProvider();
		$this->sessionManager = new SessionManager();
		$this->burnCache = new BurnCache($this->getConfig()->get("KillCreditTtl", 10.0));
		$this->tntCache = new TntCache($this->getConfig()->get("KillCreditTtl", 10.0));
    $this->getServer()->getCommandMap()->register("combatsettings", new CombatSettingsCommand($this));
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
		$this->getScheduler()->scheduleRepeatingTask(new CombatTask(), 20);
	}

	public function initConfig(): void{
		if (!is_file($path = $this->getDataFolder() . "config.yml") || filesize($path) == 0) {
			(new Config($path, 2, self::DEFAULT_CONFIG));
		} else {
		  $all = $this->getConfig()->getAll();
			foreach (array_keys(self::DEFAULT_CONFIG) as $key) {
				if (!isset($all[$key])) {
					rename($path, $this->getDataFolder() . "config_old.yml");
					(new Config($path, 2, self::DEFAULT_CONFIG));
					break;
				}
			}
		}
		self::$isPluginEnabled = $this->getConfig()->get("Enable", false);
		if ($this->getConfig()->get("UseAllowedWorldOnly", false)) {
			$this->arenaManager->addCondition(fn (Player $player) => in_array($player->getWorld()->getFolderName(), $this->getConfig()->get("AllowedWorld", [])));
		}
	}

	public function registerProvider(): void{
	  $info = $this->getConfig()->get("DatabaseInfo");
	  $class = match (strtolower($info["type"])) {
	    "mysql" => SqlProvider::class,
	    "sqlite" => SqlProvider::class,
	    "json" => ConfigProvider::class,
	    "yaml" => ConfigProvider::class,
	    default => throw new InvalidArgumentException("Unsupported database type \"" . $info["type"] . "\". Try \"" . implode("\" or \"", ["mysql", "sqlite", "json", "yaml"]) . "\".")
	  };
	  $this->provider = new $class($this);
	}

	public function getProvider(): Provider{
	  return $this->provider;
	}

	public function getCombatArenaManager(): CombatArenaManager{
	  return $this->arenaManager;
	}

	public function getSessionManager(): SessionManager{
	  return $this->sessionManager;
	}

	public function getBurnCache(): BurnCache{
		return $this->burnCache;
	}

	public function getTntCache(): TntCache{
		return $this->tntCache;
	}

	public function onDisable(): void{
	  $this->sessionManager?->close();
	  $this->provider?->close();
	}

	public function CombatSettingsForm(Player $player): void{
	  if (($session = $this->sessionManager->get($player)) == null) {
      $player->sendMessage(self::CMD_PREFIX . "§c[Error 404] Session not found.");
      return;
    }
	  $form = new CustomForm(function(Player $player, ?array $data) use ($session): void{
	    if ($data === null) return;
	    $session->setCpsPopup((bool)$data[0]);
	    $session->setCpsDisplay((bool)$data[1]);
	    $session->setCombatSounds((bool)$data[2]);
	    $session->setProjectilesSound((bool)$data[3]);
	    $session->setHideNonOpponents((bool)$data[4]);
	    $session->setSendMessages((bool)$data[5]);
	    $session->setHealthType((int)$data[6]);
	    $player->sendMessage(self::CMD_PREFIX . $this->getConfig()->getNested("Messages.SaveSettings"));
	    $this->playSound($player, "SaveSettings");
	  });
	  $form->setTitle(self::FORM_PREFIX . "§6Settings");
	  $form->addToggle("Show Cps Popup", $session->isCpsPopup());
	  $form->addToggle("Cps Nametag", $session->isCpsDisplay());
	  $form->addToggle("Combat Sounds", $session->isCombatSounds());
	  $form->addToggle("Projectiles Sound", $session->isProjectilesSound());
	  $form->addToggle("Hide Non-opponents", $session->isHideNonOpponents());
	  $form->addToggle("Send Combat Messages", $session->isSendMessages());
	  $form->addStepSlider("Health Type", ["None", "Number", "Bar"], $session->getHealthType());
	  $player->sendForm($form);
	}

	public function getMessage(string $key, array $replace = []): string{
    $message = $this->getConfig()->getNested("Messages.{$key}", "");
    empty($message) ? ($message = "§cMessage Not Found!") : null;
    foreach ($replace as $k => $v) {
    	$message = str_replace(("{" . strtoupper($k) . "}"), $v, $message);
    }
    return $message;
  }

  public function playSound(?Player $player, string $sound): void{
  	if (!$player instanceof Player)return;
    $pos = $player->getPosition();
    $data = $this->getConfig()->getNested("Sounds.{$sound}", null);
    if ($data == null || empty($data["name"] ?? ""))return;
    $pk = PlaySoundPacket::create($data["name"], $pos->getX(), $pos->getY(), $pos->getZ(), ($data["volume"] ?? 1), ($data["pitch"] ?? 1), 0, null);
    $player->getNetworkSession()->sendDataPacket($pk);
  }
}