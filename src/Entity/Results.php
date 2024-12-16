<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JsonSerializable;

#[ORM\Entity, ORM\Table(name: 'results')]
class Results implements JsonSerializable
{
    public final const RESULT_ATTR = 'result';
    public final const TIME_ATTR = 'time';
    public final const USER_ID_ATTR = 'user_id';

    #[ORM\Column(
        name: 'id',
        type: 'integer',
        nullable: false
    )]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\XmlAttribute]
    protected ?int $id = 0;

    #[ORM\Column(
        name: 'user_id',
        type: 'integer',
        nullable: false
    )]
    #[Serializer\SerializedName(Result::USER_ID_ATTR), Serializer\XmlElement(cdata: false)]
    protected int $userId;

    #[ORM\Column(
        name: 'result',
        type: 'string',
        length: 255,
        nullable: false
    )]
    #[Serializer\SerializedName(Result::RESULT_ATTR), Serializer\XmlElement(cdata: false)]
    protected string $result;

    #[ORM\Column(
        name: 'time',
        type: 'datetime',
        nullable: false
    )]
    #[Serializer\SerializedName(Result::TIME_ATTR), Serializer\XmlElement(cdata: false)]
    protected \DateTime $time;

    /**
     * Result constructor.
     * @param int $userId
     * @param string $result
     * @param \DateTime $time
     */
    public function __construct(int $userId = 0, string $result = '', \DateTime $time = null)
    {
        $this->userId = $userId;
        $this->result = $result;
        $this->time = $time ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult(string $result): void
    {
        $this->result = $result;
    }

    public function getTime(): \DateTime
    {
        return $this->time;
    }

    public function setTime(\DateTime $time): void
    {
        $this->time = $time;
    }

    /**
     * @inheritDoc
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'Id' => $this->getId(),
            self::USER_ID_ATTR => $this->getUserId(),
            self::RESULT_ATTR => $this->getResult(),
            self::TIME_ATTR => $this->getTime()->format('Y-m-d H:i:s'),
        ];
    }
}
