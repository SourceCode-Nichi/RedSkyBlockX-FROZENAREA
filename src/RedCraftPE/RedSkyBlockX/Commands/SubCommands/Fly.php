<?php

declare(strict_types=1);

namespace RedCraftPE\RedSkyBlockX\Commands\SubCommands;

use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;
use RedCraftPE\RedSkyBlockX\Commands\SBSubCommand;
use RedCraftPE\RedSkyBlockX\Island;

class Fly extends SBSubCommand {

	public function prepare(): void {

		$this->addConstraint(new InGameRequiredConstraint($this));
		$this->setPermission("redskyblockx.admin;redskyblockx.fly");
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {

		$island = $this->plugin->islandManager->getIslandAtPlayer($sender);
		if ($island instanceof Island) {

			if (array_key_exists(strtolower($sender->getName()), $island->getMembers()) || $sender->getName() === $island->getCreator()) {

				if ($sender->getAllowFlight()) {

					$sender->setAllowFlight(false);
					$sender->setFlying(false);

					$message = $this->getMShop()->construct("FLIGHT_DISABLED");
					$sender->sendMessage($message);
				} else {

					$sender->setAllowFlight(true);
					$sender->setFlying(true);

					$message = $this->getMShop()->construct("FLIGHT_ENABLED");
					$sender->sendMessage($message);
				}
			} else {

				$message = $this->getMShop()->construct("NOT_A_MEMBER_SELF");
				$message = str_replace("{ISLAND_NAME}", $island->getName(), $message);
				$sender->sendMessage($message);
			}
		} else {

			$message = $this->getMShop()->construct("NOT_ON_ISLAND");
			$sender->sendMessage($message);
		}
	}
}
