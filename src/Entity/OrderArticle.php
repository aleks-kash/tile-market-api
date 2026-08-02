<?php

namespace App\Entity;

use App\Repository\OrderArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderArticleRepository::class)]
#[ORM\Table(name: 'orders_article')]
#[ORM\Index(name: 'IDX_318C0B7C7294869C', columns: ['article_id'])]
#[ORM\Index(name: 'IDX_318C0B7C7FC358ED', columns: ['orders_id'])]
class OrderArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'orders_id', referencedColumnName: 'id', nullable: true)]
    private ?Order $order = null;

    /** ID коллекции */
    #[ORM\Column(name: 'article_id', type: Types::INTEGER, nullable: true, options: ['comment' => 'ID коллекции'])]
    private ?int $articleId = null;

    /** Количество артикулов в ед. измерения */
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => 'Количество артикулов в ед. измерения'])]
    private float $amount = 0;

    /** Цена на момент оплаты заказа */
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => 'Цена на момент оплаты заказа'])]
    private float $price = 0;

    /** Цена в Евро по заказу */
    #[ORM\Column(name: 'price_eur', type: Types::FLOAT, nullable: true, options: ['comment' => 'Цена в Евро по заказу'])]
    private ?float $priceEur = null;

    /** Валюта для которой установлена цена */
    #[ORM\Column(type: Types::STRING, length: 3, nullable: true, options: ['comment' => 'Валюта для которой установлена цена'])]
    private ?string $currency = null;

    /** Ед. изм. для которой установлена цена */
    #[ORM\Column(type: Types::STRING, length: 2, nullable: true, options: ['comment' => 'Ед. изм. для которой установлена цена'])]
    private ?string $measure = null;

    /** Минимальный срок доставки */
    #[ORM\Column(name: 'delivery_time_min', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Минимальный срок доставки'])]
    private ?\DateTimeInterface $deliveryTimeMin = null;

    /** Максимальный срок доставки */
    #[ORM\Column(name: 'delivery_time_max', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Максимальный срок доставки'])]
    private ?\DateTimeInterface $deliveryTimeMax = null;

    /** Вес упаковки */
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => 'Вес упаковки'])]
    private float $weight = 0;

    /** Кратность палете: 1 - кратно упаковке, 2 - кратно палете, 3 - не меньше палеты */
    #[ORM\Column(name: 'multiple_pallet', type: Types::SMALLINT, nullable: true, options: ['comment' => 'Кратность палете: 1 - кратно упаковке, 2 - кратно палете, 3 - не меньше палеты'])]
    private ?int $multiplePallet = null;

    /** Количество кратно которому можно добавлять товар в заказ */
    #[ORM\Column(name: 'packaging_count', type: Types::FLOAT, options: ['comment' => 'Количество кратно которому можно добавлять товар в заказ'])]
    private float $packagingCount = 0;

    /** Количество в палете на момент заказа */
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => 'Количество в палете на момент заказа'])]
    private float $pallet = 0;

    /** Количество в упаковке */
    #[ORM\Column(type: Types::FLOAT, options: ['comment' => 'Количество в упаковке'])]
    private float $packaging = 0;

    /** Плитка специально для бассейна */
    #[ORM\Column(name: 'swimming_pool', type: Types::BOOLEAN, options: ['default' => false, 'comment' => 'Плитка специально для бассейна'])]
    private bool $swimmingPool = false;

    public function getId(): ?int { return $this->id; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): self { $this->order = $order; return $this; }

    public function getArticleId(): ?int { return $this->articleId; }
    public function setArticleId(?int $articleId): self { $this->articleId = $articleId; return $this; }

    public function getAmount(): float { return $this->amount; }
    public function setAmount(float $amount): self { $this->amount = $amount; return $this; }

    public function getPrice(): float { return $this->price; }
    public function setPrice(float $price): self { $this->price = $price; return $this; }

    public function getPriceEur(): ?float { return $this->priceEur; }
    public function setPriceEur(?float $priceEur): self { $this->priceEur = $priceEur; return $this; }

    public function getCurrency(): ?string { return $this->currency; }
    public function setCurrency(?string $currency): self { $this->currency = $currency; return $this; }

    public function getMeasure(): ?string { return $this->measure; }
    public function setMeasure(?string $measure): self { $this->measure = $measure; return $this; }

    public function getDeliveryTimeMin(): ?\DateTimeInterface { return $this->deliveryTimeMin; }
    public function setDeliveryTimeMin(?\DateTimeInterface $v): self { $this->deliveryTimeMin = $v; return $this; }

    public function getDeliveryTimeMax(): ?\DateTimeInterface { return $this->deliveryTimeMax; }
    public function setDeliveryTimeMax(?\DateTimeInterface $v): self { $this->deliveryTimeMax = $v; return $this; }

    public function getWeight(): float { return $this->weight; }
    public function setWeight(float $weight): self { $this->weight = $weight; return $this; }

    public function getMultiplePallet(): ?int { return $this->multiplePallet; }
    public function setMultiplePallet(?int $v): self { $this->multiplePallet = $v; return $this; }

    public function getPackagingCount(): float { return $this->packagingCount; }
    public function setPackagingCount(float $v): self { $this->packagingCount = $v; return $this; }

    public function getPallet(): float { return $this->pallet; }
    public function setPallet(float $pallet): self { $this->pallet = $pallet; return $this; }

    public function getPackaging(): float { return $this->packaging; }
    public function setPackaging(float $packaging): self { $this->packaging = $packaging; return $this; }

    public function isSwimmingPool(): bool { return $this->swimmingPool; }
    public function setSwimmingPool(bool $v): self { $this->swimmingPool = $v; return $this; }
}
