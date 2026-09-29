<?php
namespace Skeletor\Image\Model;

use Skeletor\Core\Model\Model;

class Image extends Model
{
    const TYPE_JPEG = 1;
    const TYPE_PNG = 2;
    const TYPE_WEBP = 3;

    /**
     * @param int $id
     * @param string $filename
     * @param string $alt
     * @param int $type
     * @param string $mimeType
     * @param \DateTime|null $createdAt
     * @param \DateTime|null $updatedAt
     */
    public function __construct(
        private string $id,
        private string $filename,
        private string $alt,
        private int $type,
        private string $mimeType,
        private ?string $label = null,
        private ?string $author = null,
        private ?string $orientation = null,
        private ?\DateTime $createdAt = null,
        private ?\DateTime $updatedAt = null
    ) {
        parent::__construct($this->createdAt, $this->updatedAt);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    public static function getHRType($status)
    {
        return static::getHRTypes()[$status];
    }

    public static function getHRTypes()
    {
        return [
            static::TYPE_JPEG => 'Jpeg',
            static::TYPE_PNG => 'Png',
            static::TYPE_WEBP => 'Webp',
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
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getAlt(): string
    {
        return $this->alt;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getUrl(): string
    {
        return \BASE_URL . \IMAGES_URL . $this->filename;
    }

    /**
     * @return string|null
     */
    public function getOrientation(): ?string
    {
        return $this->orientation;
    }
}