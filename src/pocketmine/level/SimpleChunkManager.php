<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

declare(strict_types=1);

namespace pocketmine\level;

use pocketmine\level\format\Chunk;

class SimpleChunkManager implements ChunkManager{

	/** @var Chunk[] */
	protected $chunks = [];

	protected $worldHeight;

	/**
	 * SimpleChunkManager constructor.
	 *
	 * @param int $worldHeight
	 */
	public function __construct(int $worldHeight = Level::Y_MAX){
		$this->worldHeight = $worldHeight;
	}

	/**
	 * Gets the raw block id.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-255
	 */
	public function getBlockIdAt(int $x, int $y, int $z) : int{
		if($chunk = $this->getChunk($x >> 4, $z >> 4)){
			return $chunk->getBlockId($x & 0xf, $y, $z & 0xf);
		}
		return 0;
	}

	/**
	 * Gets the raw block metadata
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 *
	 * @return int 0-15
	 */
	public function getBlockDataAt(int $x, int $y, int $z) : int{
		if($chunk = $this->getChunk($x >> 4, $z >> 4)){
			return $chunk->getBlockData($x & 0xf, $y, $z & 0xf);
		}
		return 0;
	}

	public function setBlockIdAndDataAt(int $x, int $y, int $z, int $id, int $data) : void{
		if(($chunk = $this->getChunk($x >> 4, $z >> 4)) !== null){
			$chunk->setBlock($x & 0xf, $y, $z & 0xf, $id, $data);
		}
	}

	public function getBlockLightAt(int $x, int $y, int $z) : int{
		if($chunk = $this->getChunk($x >> 4, $z >> 4)){
			return $chunk->getBlockLight($x & 0xf, $y, $z & 0xf);
		}

		return 0;
	}

	public function setBlockLightAt(int $x, int $y, int $z, int $level){
		if($chunk = $this->getChunk($x >> 4, $z >> 4)){
			$chunk->setBlockLight($x & 0xf, $y, $z & 0xf, $level);
		}
	}

	public function getBlockSkyLightAt(int $x, int $y, int $z) : int{
		if($chunk = $this->getChunk($x >> 4, $z >> 4)){
			return $chunk->getBlockSkyLight($x & 0xf, $y, $z & 0xf);
		}

		return 0;
	}

	public function setBlockSkyLightAt(int $x, int $y, int $z, int $level){
		if($chunk = $this->getChunk($x >> 4, $z >> 4)){
			$chunk->setBlockSkyLight($x & 0xf, $y, $z & 0xf, $level);
		}
	}

	/**
	 * @param int $chunkX
	 * @param int $chunkZ
	 *
	 * @return Chunk|null
	 */
	public function getChunk(int $chunkX, int $chunkZ){
		return $this->chunks[Level::chunkHash($chunkX, $chunkZ)] ?? null;
	}

	/**
	 * @param int        $chunkX
	 * @param int        $chunkZ
	 * @param Chunk|null $chunk
	 */
	public function setChunk(int $chunkX, int $chunkZ, Chunk $chunk = null){
		if($chunk === null){
			unset($this->chunks[Level::chunkHash($chunkX, $chunkZ)]);
			return;
		}
		$this->chunks[Level::chunkHash($chunkX, $chunkZ)] = $chunk;
	}

	public function cleanChunks(){
		$this->chunks = [];
	}

	public function getWorldHeight() : int{
		return $this->worldHeight;
	}

	public function isInWorld(int $x, int $y, int $z) : bool{
		return (
			$x <= INT32_MAX and $x >= INT32_MIN and
			$y < $this->worldHeight and $y >= 0 and
			$z <= INT32_MAX and $z >= INT32_MIN
		);
	}
}
