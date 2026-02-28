<?php

declare(strict_types=1);

namespace Rename;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class Main extends PluginBase {

    private array $pendingRename = [];

    public function onEnable(): void {
        $this->saveDefaultConfig();
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {

        if (!$sender instanceof Player) {
            $sender->sendMessage("Use this command in-game.");
            return true;
        }

        $item = $sender->getInventory()->getItemInHand();

        if ($item->isNull()) {
            $sender->sendMessage($this->getConfig()->get("messages")["no-item"]);
            return true;
        }

        $cost = (int) $this->getConfig()->get("xp-cost-levels");

        // CONFIRMATION STEP
        if (isset($this->pendingRename[$sender->getName()]) && count($args) === 0) {

            $newName = $this->pendingRename[$sender->getName()];
            unset($this->pendingRename[$sender->getName()]);

            // Bypass permission
            if ($sender->hasPermission("rename.bypass")) {
                $item->setCustomName("§r" . $newName);
                $sender->getInventory()->setItemInHand($item);
                $sender->sendMessage($this->getConfig()->get("messages")["bypass"]);
                return true;
            }

            // Check XP levels
            if ($sender->getXpManager()->getXpLevel() < $cost) {
                $msg = str_replace("{cost}", (string)$cost, $this->getConfig()->get("messages")["not-enough-xp"]);
                $sender->sendMessage($msg);
                return true;
            }

            // Take XP levels
            $sender->getXpManager()->setXpLevel(
                $sender->getXpManager()->getXpLevel() - $cost
            );

            $item->setCustomName("§r" . $newName);
            $sender->getInventory()->setItemInHand($item);

            $sender->sendMessage($this->getConfig()->get("messages")["success"]);
            return true;
        }

        // FIRST RUN: Set pending rename
        if (count($args) > 0) {

            $newName = implode(" ", $args);
            $this->pendingRename[$sender->getName()] = $newName;

            if ($sender->hasPermission("rename.bypass")) {
                $sender->sendMessage($this->getConfig()->get("messages")["confirm"]);
                return true;
            }

            $msg = str_replace("{cost}", (string)$cost, $this->getConfig()->get("messages")["confirm"]);
            $sender->sendMessage($msg);
            return true;
        }

        $sender->sendMessage("§cUsage: /rename <name>");
        return true;
    }
}
