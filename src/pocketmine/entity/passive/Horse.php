<?php

/*
 *               _ _
 *         /\   | | |
 *        /  \  | | |_ __ _ _   _
 *       / /\ \ | | __/ _` | | | |
 *      / ____ \| | || (_| | |_| |
 *     /_/    \_|_|\__\__,_|\__, |
 *                           __/ |
 *                          |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author TuranicTeam
 * @link https://github.com/TuranicTeam/Altay
 *
 */

declare(strict_types=1);

namespace pocketmine\entity\passive;

use pocketmine\entity\Animal;
use pocketmine\entity\Attribute;
use pocketmine\entity\behavior\FloatBehavior;
use pocketmine\entity\behavior\FollowParentBehavior;
use pocketmine\entity\behavior\HorseRiddenBehavior;
use pocketmine\entity\behavior\LookAtPlayerBehavior;
use pocketmine\entity\behavior\MateBehavior;
use pocketmine\entity\behavior\PanicBehavior;
use pocketmine\entity\behavior\RandomLookAroundBehavior;
use pocketmine\entity\behavior\TemptedBehavior;
use pocketmine\entity\behavior\WanderBehavior;
use pocketmine\entity\Tamable;
use pocketmine\item\Saddle;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\mcpe\protocol\RiderJumpPacket;
use pocketmine\Player;

class Horse extends Animal{

	public const NETWORK_ID = self::HORSE;

	public const HORSE_VARIANT_WHITE = 0;
	public const HORSE_VARIANT_CREAMY = 1;
	public const HORSE_VARIANT_CHESTNUT = 2;
	public const HORSE_VARIANT_BROWN = 3;
	public const HORSE_VARIANT_BLACK = 4;
	public const HORSE_VARIANT_GRAY = 5;
	public const HORSE_VARIANT_DARK_BROWN = 6;

	public const HORSE_MARK_VARIANT_NONE = 0;
	public const HORSE_MARK_VARIANT_WHITE = 1;
	public const HORSE_MARK_VARIANT_WHITE_FIELD = 2;
	public const HORSE_MARK_VARIANT_WHITE_DOTS = 3;
	public const HORSE_MARK_VARIANT_BLACK_DOTS = 4;

	public $width = 1.3965;
	public $height = 1.6;

	protected $jumpRearingCounter = 0;

	protected function addBehaviors() : void{
		$this->behaviorPool->setBehavior(0, new HorseRiddenBehavior($this));
		$this->behaviorPool->setBehavior(1, new FloatBehavior($this));
		$this->behaviorPool->setBehavior(2, new PanicBehavior($this, 1.25));
		$this->behaviorPool->setBehavior(3, new MateBehavior($this, 1.0));
		$this->behaviorPool->setBehavior(4, new TemptedBehavior($this, [Item::WHEAT], 1.2));
		$this->behaviorPool->setBehavior(5, new FollowParentBehavior($this, 1.1));
		$this->behaviorPool->setBehavior(6, new WanderBehavior($this, 1.0));
		$this->behaviorPool->setBehavior(7, new LookAtPlayerBehavior($this, 6.0));
		$this->behaviorPool->setBehavior(8, new RandomLookAroundBehavior($this));
	}

	protected function initEntity(CompoundTag $nbt) : void{
		$this->setMaxHealth(10);
		$this->setMovementSpeed(0.3);
		$this->setFollowRange(35);
		$this->setSaddled(boolval($nbt->getByte("Saddle", 0)));

		if($nbt->hasTag("Variant", IntTag::class)){
			$variant = $nbt->getInt("Variant");
			$markVariant = $variant >> 8;
		}else{
			$variant = $this->random->nextBoundedInt(7);
			$markVariant = $this->random->nextBoundedInt(5);
		}

		$this->setVariant($variant - ($markVariant << 8));
		$this->setMarkVariant($markVariant);

		$this->setGenericFlag(self::DATA_FLAG_CAN_POWER_JUMP, true);

		parent::initEntity($nbt);
	}

	public function addAttributes() : void{
		parent::addAttributes();

		$this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::JUMP_STRENGTH));
	}

	public function getName() : string{
		return "Horse";
	}

	public function onInteract(Player $player, Item $item, Vector3 $clickPos, int $slot) : bool{
		if(!$this->isImmobile()){
			if($item instanceof Saddle){
				if(!$this->isSaddled()){
					$this->setSaddled(true);
					if($player->isSurvival()){
						$item->pop();
					}
					return true;
				}
			}elseif($this->riddenByEntity === null){
				$player->mountEntity($this);
				return true;
			}
		}
		return parent::onInteract($player, $item, $clickPos, $slot);
	}

	public function getXpDropAmount() : int{
		return rand(1, ($this->isInLove() ? 7 : 3));
	}

	public function getDrops() : array{
		return [
			ItemFactory::get(Item::LEATHER, 0, mt_rand(0, 2))
		];
	}

	public function isSaddled() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_SADDLED);
	}

	public function setSaddled(bool $value = true) : void{
		$this->setGenericFlag(self::DATA_FLAG_SADDLED, $value);
	}

	public function saveNBT() : CompoundTag{
		$nbt = parent::saveNBT();

		$nbt->setByte("Saddle", intval($this->isSaddled()));
		$nbt->setInt("Variant", $this->getVariant() | $this->getMarkVariant() << 8);

		return $nbt;
	}

	public function getRiderSeatPosition(int $seatNumber = 0) : Vector3{
		return new Vector3(0, 1.1, -0.2);
	}

	public function getLivingSound() : ?string{
		return "mob.horse.say";
	}

	public function isRearing() : bool{
		return $this->getGenericFlag(self::DATA_FLAG_REARING);
	}

	public function setRearing(bool $value) : void{
		$this->jumpRearingCounter = 0;
		$this->setGenericFlag(self::DATA_FLAG_REARING, $value);
	}

	public function onRidingUpdate(Player $player, float $motX, float $motY, bool $jumping = false, bool $sneaking = false) : void{
		if($this->isInLove()){
			$this->yaw = $this->headYaw = $this->riddenByEntity->headYaw;

			// TODO: Improve riding movement
			if($motY > 0){
				$this->moveForward(1.5);
			}

			if($jumping){
				// TODO: not working
				$player->getDataPropertyManager()->setInt(self::DATA_EXPERIENCE_VALUE, min(100, $this->jumpRearingCounter++));
				$player->getDataPropertyManager()->setInt(10, $this->jumpRearingCounter);
			}
		}
	}
}