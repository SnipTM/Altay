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

namespace pocketmine\entity\behavior;

use pocketmine\entity\passive\Horse;
use pocketmine\level\sound\PlaySound;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

class HorseRiddenBehavior extends Behavior{

	protected $rideTime = 0;
	/** @var Horse */
	protected $mob;

	public function __construct(Horse $mob){
		parent::__construct($mob);

		$this->mutexBits = 2;
	}

	public function canStart() : bool{
		return $this->mob->getRiddenByEntity() !== null;
	}

	public function onTick() : void{
		if($this->canStart()){ // a minor check
			if(!$this->mob->isInLove()){
				if($this->rideTime > 100 and !$this->mob->isRearing()){
					if($this->mob->random->nextBoundedInt(4) === 0){
						$this->mob->setInLove(true);
						$this->rideTime = 0;
					}else{
						$this->mob->setRearing(true);
						$this->mob->level->addSound(new PlaySound($this->mob, "mob.horse.jump"));
					}
				}elseif($this->rideTime > 120){
					$this->mob->broadcastEntityEvent(EntityEventPacket::TAME_FAIL);
					$this->mob->getRiddenByEntity()->dismountEntity();
					$this->mob->setRearing(false);
				}

				$this->rideTime++;
				$this->mutexBits = 2;
			}else{
				if($this->mob->isRearing() and $this->mob->onGround){
					$this->mob->setRearing(false);
				}

				$this->mutexBits = 7;
			}
		}
	}

	public function onEnd() : void{
		$this->rideTime = 0;
		$this->mob->setRearing(false);
	}
}