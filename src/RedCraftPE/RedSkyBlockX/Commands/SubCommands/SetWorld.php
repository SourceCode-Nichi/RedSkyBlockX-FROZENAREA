<?php

declare(strict_types=1);

namespace RedCraftPE\RedSkyBlockX\Commands\SubCommands;

use CortexPE\Commando\args\RawStringArgument;
use pocketmine\command\CommandSender;
use RedCraftPE\RedSkyBlockX\Commands\SBSubCommand;

class SetWorld extends SBSubCommand {

	private $islandCount; //will use later for reconfiguration

	protected function prepare(): void {

		$this->setPermission("redskyblockx.admin;redskyblockx.setworld");
		$this->registerArgument(0, new RawStringArgument("name", false));
	}

	public function onRun(CommandSender $sender, string $aliasUsed, array $args): void {

		if (isset($args["name"])) {

			$plugin = $this->plugin;
			$name = $args["name"];

			if ($plugin->skyblock->get("Master World") !== $name) {

				if ($plugin->getServer()->getWorldManager()->loadWorld($name)) {

					$plugin->skyblock->set("Islands", 0); //reconfigure so if master world is changed back to previous skyblock world the amount of islands already created remains
					$plugin->skyblock->set("Master World", $name);
					$plugin->skyblock->save();

					$message = $this->getMShop()->construct("WORLD_SET");
					$message = str_replace("{WORLD}", $name, $message);
					$sender->sendMessage($message);
					return;
				} else {

					$message = $this->getMShop()->construct("NO_WORLD");
					$message = str_replace("{WORLD}", $name, $message);
					$sender->sendMessage($message);
					return;
				}
			} else {

				$message = $this->getMShop()->construct("NO_CHANGE");
				$message = str_replace("{WORLD}", $name, $message);
				$sender->sendMessage($message);
				return;
			}
		} else {

			$this->sendUsage();
			return;
		}
	}
}
