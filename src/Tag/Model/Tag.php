<?php

namespace Skeletor\Tag\Model;

use Skeletor\Core\Model\Model;
use Skeletor\Image\Model\Image;

class Tag extends Model
{
    const POSITION_RIGHT = 0;
    const POSITION_LEFT = 1;

    public function __construct(private $id, private int $stickerImagePosition,
        private string $title,
        private ?\Datetime $createdAt = null, private ?\Datetime $updatedAt = null,
        private  ?string $priceLabel = null, private ?string $stickerLabel = null, private ?Image $stickerImage = null,
    private ?string $stickerImagePath = null)
    {
        parent::__construct($createdAt, $updatedAt);
    }

    public static function getHRPosition($position)
    {
        return static::getHRPositions()[$position];
    }

    public static function getHRPositions()
    {
        return [
          static::POSITION_RIGHT => 'Right',
          static::POSITION_LEFT => 'Left',
        ];
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return int
     */
    public function getStickerImagePosition(): int
    {
        return $this->stickerImagePosition;
    }

    /**
     * @return \Datetime|null
     */
    public function getCreatedAt(): ?\Datetime
    {
        return $this->createdAt;
    }

    /**
     * @return \Datetime|null
     */
    public function getUpdatedAt(): ?\Datetime
    {
        return $this->updatedAt;
    }

    /**
     * @return string|null
     */
    public function getPriceLabel(): ?string
    {
        return $this->priceLabel;
    }

    /**
     * @return string|null
     */
    public function getStickerLabel(): ?string
    {
        return $this->stickerLabel;
    }

    /**
     * @return int|null
     */
    public function getStickerImage(): ?Image
    {
        return $this->stickerImage;
    }

    /**
     * @return string|null
     */
    public function getStickerImagePath() :?string
    {
        return $this->stickerImage?->getFilename();
    }
}