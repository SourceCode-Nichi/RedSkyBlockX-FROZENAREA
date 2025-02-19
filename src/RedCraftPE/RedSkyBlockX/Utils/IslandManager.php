<?php

declare(strict_types=1);

namespace RedCraftPE\RedSkyBlockX\Utils;

use pocketmine\block\Block;
use pocketmine\player\Player;
use pocketmine\world\World;
use RedCraftPE\RedSkyBlockX\Island;
use RedCraftPE\RedSkyBlockX\SkyBlock;

class IslandManager {

	private $plugin;

	private $islands = [];

	public static $instance;

	public function __construct(SkyBlock $plugin) {

		$this->plugin = $plugin;
		self::$instance = $this;
	}

	public function getIslandData(string $playerName): ?array {

		$playerNameLower = strtolower($playerName);

		if (in_array($playerNameLower . ".json", array_map('strtolower', scandir($this->plugin->getDataFolder() . "../RedSkyBlockX/Players")), true)) {

			$islandData = (array) json_decode(file_get_contents($this->plugin->getDataFolder() . "../RedSkyBlockX/Players/" . $playerName . ".json"), true);
			return $islandData;
		} else {

			return null;
		}
	}

	public function constructIsland(array $islandData, string $playerName): Island {

		$islandData = $this->verifyIslandDataIntegrity($islandData, $playerName);
		$island = new Island($islandData);
		$this->addIsland($island);
		$this->saveIsland($island);

		return $island;
	}

	public function verifyIslandDataIntegrity(array $islandData, string $playerName): array {

		$requiredKeys = [
			"creator",
			"name",
			"size",
			"value",
			"initialspawnpoint",
			"spawnpoint",
			"members",
			"banned",
			"resetcooldown",
			"lockstatus",
			"settings",
			"stats",
			"permissions",
			"experience"
		];

		foreach ($requiredKeys as $key) {

			if (!isset($islandData[$key])) {

				$islandData[$key] = null;
			}
		}
		if ($islandData["creator"] !== $playerName) $islandData["creator"] = $playerName;

		return $islandData;
	}

	public function constructAllIslands(): void {

		$plugin = $this->plugin;
		$playerFiles = scandir($plugin->getDataFolder() . "../RedSkyBlockX/Players");

		foreach ($playerFiles as $fileName) {

			$playerName = substr($fileName, 0, -5); // removes the .json from the file name
			if (is_file($plugin->getDataFolder() . "../RedSkyBlockX/Players/" . $fileName)) {

				$islandData = (array) json_decode(file_get_contents($plugin->getDataFolder() . "../RedSkyBlockX/Players/" . $fileName));
				$this->constructIsland($islandData, $playerName);
			}
		}
	}

	public function deconstructIsland(Island $island): array {

		$islandData = [
			"creator" => $island->getCreator(),
			"name" => $island->getName(),
			"size" => $island->getSize(),
			"value" => $island->getValue(),
			"initialspawnpoint" => $island->getInitialSpawnPoint(),
			"spawnpoint" => $island->getSpawnPoint(),
			"members" => $island->getMembers(),
			"banned" => $island->getBanned(),
			"resetcooldown" => $island->getResetCooldown(),
			"lockstatus" => $island->getLockStatus(),
			"settings" => $island->getSettings(),
			"stats" => $island->getStats(),
			"permissions" => $island->getPermissions(),
			"experience" => $island->getXP()
		];

		return $islandData;
	}

	public function saveIsland(Island $island): void {

		$islandData = $this->deconstructIsland($island);
		if (file_exists($this->plugin->getDataFolder() . "../RedSkyBlockX/Players/" . $islandData["creator"] . ".json")) {

			file_put_contents($this->plugin->getDataFolder() . "../RedSkyBlockX/Players/" . $islandData["creator"] . ".json", json_encode($islandData));
		}
	}

	public function saveAllIslands(): void {

		foreach ($this->islands as $island) {

			$this->saveIsland($island);
		}
	}

	public function getIslands(): array {

		return $this->islands;
	}

	public function getIsland(Player $player): ?Island {

		$playerName = $player->getName();

		if (array_key_exists($playerName, $this->islands)) {

			return $this->islands[$playerName];
		} else {

			return null;
		}
	}

	public function getIslandByCreatorName(string $name): ?Island {

		$island = null;
		foreach ($this->islands as $owner => $isle) {

			if (strtolower($isle->getCreator()) === strtolower($name)) {

				$island = $isle;
			}
		}
		return $island;
	}

	public function getIslandByName(string $islandName): ?Island {

		$islandName = strtolower($islandName);
		$islands = $this->islands;

		foreach ($islands as $island) {

			$isleName = strtolower($island->getName());
			if ($islandName === $isleName) {

				return $island;
			}
		}
		return null;
	}

	public function addIsland(Island $island): void {

		$this->islands[$island->getCreator()] = $island;
	}

	public function removeIsland(Island $island): void {

		unset($this->islands[$island->getCreator()]);
		$this->saveIsland($island);
		unset($island);
	}

	public function removeAllIslands(): void {

		$this->islands = [];
	}

	public function deleteIsland(Island $island): void {

		unset($this->islands[$island->getCreator()]);

		$filePath = $this->plugin->getDataFolder() . "../RedSkyBlockX/Players/" . $island->getCreator() . ".json";
		if (file_exists($filePath)) {

			unlink($filePath);
			unset($island);
		} else {

			unset($island);
		}
	}

	public function getMasterWorld(): ?world {

		$masterWorldName = $this->plugin->skyblock->get("Master World");
		$masterWorld = $this->plugin->getServer()->getWorldManager()->getWorldByName($masterWorldName);
		if ($masterWorld instanceof World) {

			if ($masterWorld->isLoaded()) {

				return $masterWorld;
			} else {

				if ($this->plugin->getServer()->getWorldManager()->loadWorld($masterWorldName)) {

					return $masterWorld;
				} else {

					return null;
				}
			}
		} else {

			return null;
		}
	}

	public function isOnIsland(Player $player, Island $island): bool {

		$playerPos = $player->getPosition();
		$islandCenter = $island->getIslandCenter();
		$centerX = $islandCenter[0];
		$centerZ = $islandCenter[1];
		$islandSize = $island->getSize();
		$halfSize = $islandSize / 2;
		$masterWorld = $this->getMasterWorld();
		$playerWorld = $player->getWorld();

		if ($playerWorld === $masterWorld) {

			if (($playerPos->x > $centerX - $halfSize && $playerPos->z > $centerZ - $halfSize) && ($playerPos->x < $centerX + $halfSize && $playerPos->z < $centerZ + $halfSize)) {

				return true;
			} else {

				return false;
			}
		} else {

			return false;
		}
	}

	public function getIslandAtPlayer(Player $player): ?Island {

		$foundIsland = null;
		$playerWorld = $player->getWorld();
		$masterWorld = $this->getMasterWorld();

		if ($playerWorld === $masterWorld) {

			foreach ($this->islands as $island) {

				$islandSize = $island->getSize();
				$halfSize = $islandSize / 2;
				$islandCenter = $island->getIslandCenter();
				$centerX = $islandCenter[0];
				$centerZ = $islandCenter[1];

				$playerX = $player->getPosition()->x;
				$playerZ = $player->getPosition()->z;

				if (($playerX > $centerX - $halfSize && $playerZ > $centerZ - $halfSize) && ($playerX < $centerX + $halfSize && $playerZ < $centerZ + $halfSize)) {

					$foundIsland = $island;
				}
			}
		}
		return $foundIsland;
	}

	public function getIslandAtBlock(Block $block): ?Island {

		$foundIsland = null;
		$blockWorld = $block->getPosition()->world;
		$masterWorld = $this->getMasterWorld();

		if ($masterWorld === $blockWorld) {

			foreach ($this->islands as $island) {

				$islandSize = $island->getSize();
				$halfSize = $islandSize / 2;
				$islandCenter = $island->getIslandCenter();
				$centerX = $islandCenter[0];
				$centerZ = $islandCenter[1];

				$blockX = $block->getPosition()->x;
				$blockZ = $block->getPosition()->z;

				if (($blockX > $centerX - $halfSize && $blockZ > $centerZ - $halfSize) && ($blockX < $centerX + $halfSize && $blockZ < $centerZ + $halfSize)) {

					$foundIsland = $island;
				}
			}
		}
		return $foundIsland;
	}

	public function getPlayersAtIsland(Island $island): array {

		$onlinePlayers = $this->plugin->getServer()->getOnlinePlayers();
		$playersOnIsland = [];

		$islandSize = $island->getSize();
		$halfSize = $islandSize / 2;
		$islandCenter = $island->getIslandCenter();
		$centerX = $islandCenter[0];
		$centerZ = $islandCenter[1];
		$masterWorld = $this->getMasterWorld();

		foreach ($onlinePlayers as $player) {

			$playerX = $player->getPosition()->x;
			$playerZ = $player->getPosition()->z;
			$playerWorld = $player->getWorld();

			if ($playerWorld->getFolderName() === $masterWorld->getFolderName()) {

				if (($playerX > $centerX - $halfSize && $playerZ > $centerZ - $halfSize) && ($playerX < $centerX + $halfSize && $playerZ < $centerZ + $halfSize)) {

					array_push($playersOnIsland, $player->getName());
				}
			}
		}
		return $playersOnIsland;
	}

	public function getIslandRank(Island $island): ?int {

		$valueArray = [];

		foreach ($this->islands as $creator => $isle) {

			$value = $isle->getValue();
			$valueArray[$creator] = $value;
		}

		arsort($valueArray);
		if (isset($valueArray[$island->getCreator()])) {

			$rank = array_search($island->getCreator(), array_keys($valueArray), true) + 1; // + 1 because arrays are 0 indexed
			return $rank;
		} else {

			return null;
		}
	}

	public function getTopIslands(): array {

		$topIslands = [];

		foreach ($this->islands as $island) {

			$value = $island->getValue();
			$islandName = $island->getName();
			$topIslands[$islandName] = $value;
		}

		arsort($topIslands);
		return $topIslands;
	}

	public function checkRepeatIslandName(string $name): bool {

		$name = strtolower($name);
		$bias = null;
		foreach ($this->islands as $island) {

			if ($name === strtolower($island->getName())) {

				$bias = true;
				break;
			} else {

				$bias = false;
				break;
			}
		}
		return $bias;
	}

	public function getIslandsEmployedAt(string $playerName): array {

		$employedAt = [];
		foreach ($this->islands as $owner => $island) {

			if (strtolower($playerName) === strtolower($owner) || array_key_exists(strtolower($playerName), $island->getMembers())) {

				$employedAt[] = $island;
			}
		}
		return $employedAt;
	}

	public function searchIslandChannels(string $playerName): ?Island {

		$playerName = strtolower($playerName);
		$possibleChannels = $this->getIslandsEmployedAt($playerName);
		$tuneToChannel = null;
		foreach ($possibleChannels as $channel) {

			if (in_array($playerName, $channel->getChatters(), true)) {

				$tuneToChannel = $channel;
			}
		}
		return $tuneToChannel;
	}

	public static function getInstance(): self {

		if (self::$instance === null) {

			self::$instance = new self(SkyBlock::getInstance());
		}
		return self::$instance;
	}
}
