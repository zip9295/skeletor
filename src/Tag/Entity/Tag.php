<?php
namespace Skeletor\Tag\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;
use Skeletor\Tag\Model\Tag as DtoModel;
use Skeletor\Image\Entity\Image;

#[ORM\Entity]
#[ORM\Table(name: 'tag')]
class Tag
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING)]
    private string $title;

    #[ORM\Column(type: Types::INTEGER)]
    private int $stickerImagePosition;

    #[ORM\Column(type: Types::STRING)]
    private string $priceLabel;

    #[ORM\Column(type: Types::INTEGER)]
    private string $stickerLabel;

    #[ORM\ManyToOne(targetEntity: Image::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'stickerImageId', referencedColumnName: 'id', unique: false)]
    private ?Image $stickerImage;

    public function populateFromDto(DtoModel $dto)
    {
        if ($dto->getId()) {
            $this->id = $dto->getId();
        }
        $this->title = $dto->getTitle();
        $this->stickerLabel = $dto->getStickerLabel();
        $this->priceLabel = $dto->getPriceLabel();
        $this->stickerImagePosition = $dto->getStickerImagePosition();
    }

    public function setImage(?Image $image)
    {
        $this->stickerImage = $image;
    }

    public function getId()
    {
        return $this->id;
    }
}