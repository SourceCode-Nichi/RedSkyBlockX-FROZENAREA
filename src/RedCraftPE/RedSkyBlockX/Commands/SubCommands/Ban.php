<?php

declare(strict_types=1);

namespace RedCraftPE\RedSkyBlockX\Commands\SubCommands;

use CortexPE\Commando\args\TextArgument;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use RedCraftPE\RedSkyBlockX\Commands\SBSubCommand;
use RedCraftPE\RedSkyBlockX\Island;

class Ban extends SBSubCommand {

	public function prepare(): void {

		$this->addConstraint(new InGameRequiredConstraint($this));
		$this->setPermission("redskyblockx.island");
		$this->registerArgument(0, new TextArgument("name", false));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {

		$island = $this->plugin->islandManager->getIslandAtPlayer($sender);
		$name = $args["name"];

		if ($island instanceof Island) {

			$creator = $island->getCreator();
			$members = $island->getMembers();

			if (array_key_exists(strtolower($sender->getName()), $members) || $sender->getName() === $island->getCreator() || $sender->hasPermission("redskyblockx.admin")) {

				if (array_key_exists(strtolower($sender->getName()), $members) && !$sender->hasPermission("redskyblockx.admin")) {

					$islandPermissions = $island->getPermissions();
					$senderRank = $members[strtolower($sender->getName())];

					if (in_array("island.ban", $islandPermissions[$senderRank], true)) {

						if (array_key_exists(strtolower($name), $members)) {

							$nameRank = $members[strtolower($name)];
							$memberRanks = Island::MEMBER_RANKS;
							$namePos = array_search($nameRank, $memberRanks, true);
							$senderPos = array_search($senderRank, $memberRanks, true);
							if ($namePos >= $senderPos) {

								$message = $this->getMShop()->construct("CANT_BAN");
								$sender->sendMessage($message);
								return;
							}
						}

						$island->removeMember($name);

						if (!(strtolower($name) === strtolower($creator) || strtolower($name) === strtolower($sender->getName()))) {

							if ($island->ban($name)) {

								$message = $this->getMShop()->construct("BANNED_PLAYER");
								$message = str_replace("{NAME}", $name, $message);
								$sender->sendMessage($message);

								$player = $this->plugin->getServer()->getPlayerExact($name);
								if ($player instanceof Player && !$player->hasPermission("redskyblockx.admin")) {

									if ($this->plugin->islandManager->isOnIsland($player, $island)) {

										$spawn = $this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn();
										$player->teleport($spawn);
									}
									$message = $this->getMShop()->construct("BANNED");
									$message = str_replace("{ISLAND_NAME}", $island->getName(), $message);
									$player->sendMessage($message);
								}
							} else {

								$message = $this->getMShop()->construct("ALREADY_BANNED");
								$message = str_replace("{NAME}", $name, $message);
								$sender->sendMessage($message);
							}
						} else {

							$message = $this->getMShop()->construct("CANT_BAN");
							$sender->sendMessage($message);
						}
					} else {

						$message = $this->getMShop()->construct("RANK_TOO_LOW");
						$message = str_replace("{ISLAND_NAME}", $island->getName(), $message);
						$sender->sendMessage($message);
					}
				} else {

					$island->removeMember($name);

					if (!(strtolower($name) === strtolower($creator) || strtolower($name) === strtolower($sender->getName()))) {

						if ($island->ban($name)) {

							$message = $this->getMShop()->construct("BANNED_PLAYER");
							$message = str_replace("{NAME}", $name, $message);
							$sender->sendMessage($message);

							$player = $this->plugin->getServer()->getPlayerExact($name);
							if ($player instanceof Player && !$player->hasPermission("redskyblockx.admin")) {

								if ($this->plugin->islandManager->isOnIsland($player, $island)) {

									$spawn = $this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn();
									$player->teleport($spawn);
								}
								$message = $this->getMShop()->construct("BANNED");
								$message = str_replace("{ISLAND_NAME}", $island->getName(), $message);
								$player->sendMessage($message);
							}
						} else {

							$message = $this->getMShop()->construct("ALREADY_BANNED");
							$message = str_replace("{NAME}", $name, $message);
							$sender->sendMessage($message);
						}
					} else {

						$message = $this->getMShop()->construct("CANT_BAN");
						$sender->sendMessage($message);
					}
				}
			} else {

				$message = $this->getMShop()->construct("NOT_A_MEMBER_SELF");
				$message = str_replace("{ISLAND_NAME}", $island->getName(), $message);
				$sender->sendMessage($message);
			}
		} elseif ($this->checkIsland($sender)) {

			$island = $this->plugin->islandManager->getIsland($sender);
			$creator = $island->getCreator();
			$island->removeMember($name);

			if (strtolower($name) !== strtolower($creator)) {

				if ($island->ban($name)) {

					$message = $this->getMShop()->construct("BANNED_PLAYER");
					$message = str_replace("{NAME}", $name, $message);
					$sender->sendMessage($message);

					$player = $this->plugin->getServer()->getPlayerExact($name);
					if ($player instanceof Player && !$player->hasPermission("redskyblockx.admin")) {

						if ($this->plugin->islandManager->isOnIsland($player, $island)) {

							$spawn = $this->plugin->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn();
							$player->teleport($spawn);
						}
						$message = $this->getMShop()->construct("BANNED");
						$message = str_replace("{ISLAND_NAME}", $island->getName(), $message);
						$player->sendMessage($message);
					}
				} else {

					$message = $this->getMShop()->construct("ALREADY_BANNED");
					$message = str_replace("{NAME}", $name, $message);
					$sender->sendMessage($message);
				}
			} else {

				$message = $this->getMShop()->construct("CANT_BAN");
				$sender->sendMessage($message);
			}
		} else {

			$message = $this->getMShop()->construct("NO_ISLAND");
			$sender->sendMessage($message);
		}
	}
}
