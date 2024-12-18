<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use JsonSerializable;

#[ORM\Entity, ORM\Table(name: 'results')]
class Results implements JsonSerializable
{
    public final const RESULTS_ATTR = 'results';
    public final const RESULT_ATTR = 'result';
    public final const TIME_ATTR = 'time';
    public final const USER_ATTR = 'user';

    #[ORM\Column(
        name: 'id',
        type: 'integer',
        nullable: false
    )]
    #[ORM\Id, ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[Serializer\XmlAttribute]
    protected ?int $id = 0;

    #[ORM\ManyToOne(
        targetEntity: User::class,
        inversedBy: 'results'
    )]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    protected User $user;

    #[ORM\Column(
        name: 'result',
        type: 'float',
        length: 255,
        nullable: false
    )]
    #[Serializer\SerializedName(Results::RESULT_ATTR), Serializer\XmlElement(cdata: false)]
    protected float $result;

    #[ORM\Column(
        name: 'time',
        type: 'datetime',
        nullable: false
    )]
    #[Serializer\SerializedName(Results::TIME_ATTR)]
    #[Serializer\Type("DateTime<'Y-m-d H:i:s'>")]
    #[Serializer\XmlElement(cdata: false)]
    protected \DateTime $time;

    public function __construct(User $user, float $result = 0.0, \DateTime $time = null)
    {
        $this->user = $user;
        $this->result = $result;
        $this->time = $time ?? new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getResult(): float
    {
        return $this->result;
    }

    public function setResult(float $result): void
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
            'id' => $this->getId(),
            self::USER_ATTR => $this->getUser()->jsonSerialize(),
            self::RESULT_ATTR => $this->getResult(),
            self::TIME_ATTR => $this->getTime()->format('Y-m-d H:i:s'),
        ];
    }
}
