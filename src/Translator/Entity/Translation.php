<?php
namespace Skeletor\Translator\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Skeletor\Core\Entity\Timestampable;

#[ORM\Entity]
#[ORM\Table(name: 'translation', uniqueConstraints: [])]
// originalString is TEXT, which MySQL cannot index without a prefix length (error 1170).
// 191 chars is the safe utf8mb4 prefix (191 * 4 = 764 bytes, under the 767-byte limit on
// older InnoDB row formats); languageId is an int so it takes no length.
#[ORM\UniqueConstraint(
    name: 'translation_UNIQUE',
    columns: ['originalString', 'languageId'],
    options: ['lengths' => [191, null]],
)]
class Translation
{
    use Timestampable;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $groupName;

    #[ORM\Column(type: Types::TEXT)]
    public string $originalString;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $translatedString;

    #[ORM\ManyToOne(targetEntity: Language::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'languageId', referencedColumnName: 'id', unique: false)]
    public Language $language;

    public function getId(): int|string
    {
        return $this->id;
    }

    public function setLanguage(Language $language)
    {
        $this->language = $language;
    }
}