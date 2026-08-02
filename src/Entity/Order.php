<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\Index(name: 'IDX_1', columns: ['delivery_country'])]
#[ORM\Index(name: 'IDX_2', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_3', columns: ['create_date'])]
#[ORM\Index(name: 'IDX_4', columns: ['create_date', 'status'])]
#[ORM\Index(name: 'IDX_5', columns: ['hash'])]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /** hash заказа */
    #[ORM\Column(type: Types::STRING, length: 32, options: ['comment' => 'hash заказа'])]
    private string $hash = '';

    #[ORM\Column(name: 'user_id', type: Types::INTEGER, nullable: true)]
    private ?int $userId = null;

    /** Уникальный хеш пользователя */
    #[ORM\Column(type: Types::STRING, length: 64, options: ['comment' => 'Уникальный хеш пользователя'])]
    private string $token = '';

    /** Номер заказа */
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => 'Номер заказа'])]
    private ?string $number = null;

    /** Статус заказа */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1, 'comment' => 'Статус заказа'])]
    private int $status = 1;

    /** Контактный E-mail */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => 'Контактный E-mail'])]
    private ?string $email = null;

    /** Частное лицо или плательщик НДС */
    #[ORM\Column(name: 'vat_type', type: Types::INTEGER, options: ['default' => 0, 'comment' => 'Частное лицо или плательщик НДС'])]
    private int $vatType = 0;

    /** НДС-номер */
    #[ORM\Column(name: 'vat_number', type: Types::STRING, length: 100, nullable: true, options: ['comment' => 'НДС-номер'])]
    private ?string $vatNumber = null;

    /** Индивидуальный налоговый номер налогоплательщика */
    #[ORM\Column(name: 'tax_number', type: Types::STRING, length: 50, nullable: true, options: ['comment' => 'Индивидуальный налоговый номер налогоплательщика'])]
    private ?string $taxNumber = null;

    /** Процент скидки */
    #[ORM\Column(type: Types::SMALLINT, nullable: true, options: ['comment' => 'Процент скидки'])]
    private ?int $discount = null;

    /** Стоимость доставки */
    #[ORM\Column(type: Types::FLOAT, nullable: true, options: ['comment' => 'Стоимость доставки'])]
    private ?float $delivery = null;

    /** Тип доставки: 0 - адрес клиента, 1 - адрес склада */
    #[ORM\Column(name: 'delivery_type', type: Types::SMALLINT, nullable: true, options: ['default' => 0, 'comment' => 'Тип доставки: 0 - адрес клиента, 1 - адрес склада'])]
    private ?int $deliveryType = 0;

    /** Минимальный срок доставки */
    #[ORM\Column(name: 'delivery_time_min', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Минимальный срок доставки'])]
    private ?\DateTimeInterface $deliveryTimeMin = null;

    /** Максимальный срок доставки */
    #[ORM\Column(name: 'delivery_time_max', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Максимальный срок доставки'])]
    private ?\DateTimeInterface $deliveryTimeMax = null;

    /** Минимальный срок доставки подтверждённый производителем */
    #[ORM\Column(name: 'delivery_time_confirm_min', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Минимальный срок доставки подтверждённый производителем'])]
    private ?\DateTimeInterface $deliveryTimeConfirmMin = null;

    /** Максимальный срок доставки подтверждённый производителем */
    #[ORM\Column(name: 'delivery_time_confirm_max', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Максимальный срок доставки подтверждённый производителем'])]
    private ?\DateTimeInterface $deliveryTimeConfirmMax = null;

    /** Минимальный срок доставки (быстрая оплата) */
    #[ORM\Column(name: 'delivery_time_fast_pay_min', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Минимальный срок доставки (быстрая оплата)'])]
    private ?\DateTimeInterface $deliveryTimeFastPayMin = null;

    /** Максимальный срок доставки (быстрая оплата) */
    #[ORM\Column(name: 'delivery_time_fast_pay_max', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Максимальный срок доставки (быстрая оплата)'])]
    private ?\DateTimeInterface $deliveryTimeFastPayMax = null;

    /** Прошлый минимальный срок доставки */
    #[ORM\Column(name: 'delivery_old_time_min', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Прошлый минимальный срок доставки'])]
    private ?\DateTimeInterface $deliveryOldTimeMin = null;

    /** Прошлый максимальный срок доставки */
    #[ORM\Column(name: 'delivery_old_time_max', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Прошлый максимальный срок доставки'])]
    private ?\DateTimeInterface $deliveryOldTimeMax = null;

    #[ORM\Column(name: 'delivery_index', type: Types::STRING, length: 20, nullable: true)]
    private ?string $deliveryIndex = null;

    #[ORM\Column(name: 'delivery_country', type: Types::INTEGER, nullable: true)]
    private ?int $deliveryCountry = null;

    #[ORM\Column(name: 'delivery_region', type: Types::STRING, length: 50, nullable: true)]
    private ?string $deliveryRegion = null;

    #[ORM\Column(name: 'delivery_city', type: Types::STRING, length: 200, nullable: true)]
    private ?string $deliveryCity = null;

    #[ORM\Column(name: 'delivery_address', type: Types::STRING, length: 300, nullable: true)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(name: 'delivery_building', type: Types::STRING, length: 200, nullable: true)]
    private ?string $deliveryBuilding = null;

    #[ORM\Column(name: 'delivery_phone_code', type: Types::STRING, length: 20, nullable: true)]
    private ?string $deliveryPhoneCode = null;

    #[ORM\Column(name: 'delivery_phone', type: Types::STRING, length: 20, nullable: true)]
    private ?string $deliveryPhone = null;

    /** Пол клиента */
    #[ORM\Column(type: Types::SMALLINT, nullable: true, options: ['comment' => 'Пол клиента'])]
    private ?int $sex = null;

    /** Имя клиента */
    #[ORM\Column(name: 'client_name', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Имя клиента'])]
    private ?string $clientName = null;

    /** Фамилия клиента */
    #[ORM\Column(name: 'client_surname', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Фамилия клиента'])]
    private ?string $clientSurname = null;

    /** Название компании */
    #[ORM\Column(name: 'company_name', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Название компании'])]
    private ?string $companyName = null;

    /** Выбранный тип оплаты */
    #[ORM\Column(name: 'pay_type', type: Types::SMALLINT, options: ['comment' => 'Выбранный тип оплаты'])]
    private int $payType = 0;

    /** Дата до которой действует текущая цена заказа */
    #[ORM\Column(name: 'pay_date_execution', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Дата до которой действует текущая цена заказа'])]
    private ?\DateTimeInterface $payDateExecution = null;

    /** Дата сдвига предполагаемого расчета доставки */
    #[ORM\Column(name: 'offset_date', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Дата сдвига предполагаемого расчета доставки'])]
    private ?\DateTimeInterface $offsetDate = null;

    /** Причина сдвига сроков: 1 - каникулы на фабрике, 2 - фабрика уточняет сроки пр-ва, 3 - другое */
    #[ORM\Column(name: 'offset_reason', type: Types::SMALLINT, nullable: true, options: ['comment' => 'Причина сдвига сроков: 1 - каникулы на фабрике, 2 - фабрика уточняет сроки пр-ва, 3 - другое'])]
    private ?int $offsetReason = null;

    /** Предполагаемая дата поставки */
    #[ORM\Column(name: 'proposed_date', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Предполагаемая дата поставки'])]
    private ?\DateTimeInterface $proposedDate = null;

    /** Предполагаемая дата отгрузки */
    #[ORM\Column(name: 'ship_date', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Предполагаемая дата отгрузки'])]
    private ?\DateTimeInterface $shipDate = null;

    /** Номер треккинга */
    #[ORM\Column(name: 'tracking_number', type: Types::STRING, length: 50, nullable: true, options: ['comment' => 'Номер треккинга'])]
    private ?string $trackingNumber = null;

    /** Имя менеджера сопровождающего заказ */
    #[ORM\Column(name: 'manager_name', type: Types::STRING, length: 20, nullable: true, options: ['comment' => 'Имя менеджера сопровождающего заказ'])]
    private ?string $managerName = null;

    /** Email менеджера сопровождающего заказ */
    #[ORM\Column(name: 'manager_email', type: Types::STRING, length: 30, nullable: true, options: ['comment' => 'Email менеджера сопровождающего заказ'])]
    private ?string $managerEmail = null;

    /** Телефон менеджера сопровождающего заказ */
    #[ORM\Column(name: 'manager_phone', type: Types::STRING, length: 20, nullable: true, options: ['comment' => 'Телефон менеджера сопровождающего заказ'])]
    private ?string $managerPhone = null;

    /** Название транспортной компании */
    #[ORM\Column(name: 'carrier_name', type: Types::STRING, length: 50, nullable: true, options: ['comment' => 'Название транспортной компании'])]
    private ?string $carrierName = null;

    /** Контактные данные транспортной компании */
    #[ORM\Column(name: 'carrier_contact_data', type: Types::STRING, length: 255, nullable: true, options: ['comment' => 'Контактные данные транспортной компании'])]
    private ?string $carrierContactData = null;

    /** Локаль из которой был оформлен заказ */
    #[ORM\Column(type: Types::STRING, length: 5, options: ['comment' => 'Локаль из которой был оформлен заказ'])]
    private string $locale = '';

    /** Курс на момент оплаты */
    #[ORM\Column(name: 'cur_rate', type: Types::FLOAT, nullable: true, options: ['default' => 1, 'comment' => 'Курс на момент оплаты'])]
    private ?float $curRate = 1;

    /** Валюта при которой был оформлен заказ */
    #[ORM\Column(type: Types::STRING, length: 3, options: ['default' => 'EUR', 'comment' => 'Валюта при которой был оформлен заказ'])]
    private string $currency = 'EUR';

    /** Ед. изм. в которой был оформлен заказ */
    #[ORM\Column(type: Types::STRING, length: 3, options: ['default' => 'm', 'comment' => 'Ед. изм. в которой был оформлен заказ'])]
    private string $measure = 'm';

    /** Название заказа */
    #[ORM\Column(type: Types::STRING, length: 200, options: ['comment' => 'Название заказа'])]
    private string $name = '';

    /** Дополнительная информация */
    #[ORM\Column(type: Types::STRING, length: 1000, nullable: true, options: ['comment' => 'Дополнительная информация'])]
    private ?string $description = null;

    /** Дата создания */
    #[ORM\Column(name: 'create_date', type: Types::DATETIME_MUTABLE, options: ['comment' => 'Дата создания'])]
    private \DateTimeInterface $createDate;

    /** Дата изменения */
    #[ORM\Column(name: 'update_date', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Дата изменения'])]
    private ?\DateTimeInterface $updateDate = null;

    /** Данные склада: адрес, название, часы работы */
    #[ORM\Column(name: 'warehouse_data', type: Types::TEXT, nullable: true, options: ['comment' => 'Данные склада: адрес, название, часы работы'])]
    private ?string $warehouseData = null;

    /** Если true то заказ не будет сброшен вследствии изменений */
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1, 'comment' => 'Если true то заказ не будет сброшен вследствии изменений'])]
    private int $step = 1;

    /** Адреса плательщика и получателя совпадают (false - разные, true - одинаковые) */
    #[ORM\Column(name: 'address_equal', type: Types::BOOLEAN, nullable: true, options: ['default' => true, 'comment' => 'Адреса плательщика и получателя совпадают (false - разные, true - одинаковые)'])]
    private ?bool $addressEqual = true;

    /** Запрашивался ли счет на банковский перевод */
    #[ORM\Column(name: 'bank_transfer_requested', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'Запрашивался ли счет на банковский перевод'])]
    private ?bool $bankTransferRequested = null;

    /** Если true то заказ отправлен в работу */
    #[ORM\Column(name: 'accept_pay', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'Если true то заказ отправлен в работу'])]
    private ?bool $acceptPay = null;

    /** Конечная дата согласования сроков поставки */
    #[ORM\Column(name: 'cancel_date', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Конечная дата согласования сроков поставки'])]
    private ?\DateTimeInterface $cancelDate = null;

    /** Общий вес брутто заказа */
    #[ORM\Column(name: 'weight_gross', type: Types::FLOAT, nullable: true, options: ['comment' => 'Общий вес брутто заказа'])]
    private ?float $weightGross = null;

    /** Оставлен отзыв по коллекциям в заказе */
    #[ORM\Column(name: 'product_review', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'Оставлен отзыв по коллекциям в заказе'])]
    private ?bool $productReview = null;

    /** Метка зеркала на котором создается заказ */
    #[ORM\Column(type: Types::SMALLINT, nullable: true, options: ['comment' => 'Метка зеркала на котором создается заказ'])]
    private ?int $mirror = null;

    /** Метка массовой обработки */
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => 'Метка массовой обработки'])]
    private ?bool $process = null;

    /** Фактическая дата поставки */
    #[ORM\Column(name: 'fact_date', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Фактическая дата поставки'])]
    private ?\DateTimeInterface $factDate = null;

    /** Фиксирует вход клиента на страницу отзыва и последующие клики */
    #[ORM\Column(name: 'entrance_review', type: Types::SMALLINT, nullable: true, options: ['comment' => 'Фиксирует вход клиента на страницу отзыва и последующие клики'])]
    private ?int $entranceReview = null;

    /** Если true, то оплату посчитать в евро */
    #[ORM\Column(name: 'payment_euro', type: Types::BOOLEAN, nullable: true, options: ['default' => false, 'comment' => 'Если true, то оплату посчитать в евро'])]
    private ?bool $paymentEuro = false;

    /** Установлена спец цена по заказу */
    #[ORM\Column(name: 'spec_price', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'Установлена спец цена по заказу'])]
    private ?bool $specPrice = null;

    /** Показывать спец. сообщение */
    #[ORM\Column(name: 'show_msg', type: Types::BOOLEAN, nullable: true, options: ['comment' => 'Показывать спец. сообщение'])]
    private ?bool $showMsg = null;

    /** Стоимость доставки в евро */
    #[ORM\Column(name: 'delivery_price_euro', type: Types::FLOAT, nullable: true, options: ['comment' => 'Стоимость доставки в евро'])]
    private ?float $deliveryPriceEuro = null;

    #[ORM\Column(name: 'address_payer', type: Types::INTEGER, nullable: true)]
    private ?int $addressPayer = null;

    /** Расчетная дата поставки */
    #[ORM\Column(name: 'sending_date', type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => 'Расчетная дата поставки'])]
    private ?\DateTimeInterface $sendingDate = null;

    /** Тип расчета: 0 - ручной, 1 - автоматический */
    #[ORM\Column(name: 'delivery_calculate_type', type: Types::SMALLINT, nullable: true, options: ['default' => 0, 'comment' => 'Тип расчета: 0 - ручной, 1 - автоматический'])]
    private ?int $deliveryCalculateType = 0;

    /** Дата полной оплаты заказа */
    #[ORM\Column(name: 'full_payment_date', type: Types::DATE_MUTABLE, nullable: true, options: ['comment' => 'Дата полной оплаты заказа'])]
    private ?\DateTimeInterface $fullPaymentDate = null;

    /** Реквизиты банка для возврата средств */
    #[ORM\Column(name: 'bank_details', type: Types::TEXT, nullable: true, options: ['comment' => 'Реквизиты банка для возврата средств'])]
    private ?string $bankDetails = null;

    /** Квартира/офис */
    #[ORM\Column(name: 'delivery_apartment_office', type: Types::STRING, length: 30, nullable: true, options: ['comment' => 'Квартира/офис'])]
    private ?string $deliveryApartmentOffice = null;

    /** @var Collection<int, OrderArticle> */
    #[ORM\OneToMany(targetEntity: OrderArticle::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $articles;

    public function __construct()
    {
        $this->createDate = new \DateTime();
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getHash(): string { return $this->hash; }
    public function setHash(string $hash): self { $this->hash = $hash; return $this; }

    public function getUserId(): ?int { return $this->userId; }
    public function setUserId(?int $userId): self { $this->userId = $userId; return $this; }

    public function getToken(): string { return $this->token; }
    public function setToken(string $token): self { $this->token = $token; return $this; }

    public function getNumber(): ?string { return $this->number; }
    public function setNumber(?string $number): self { $this->number = $number; return $this; }

    public function getStatus(): int { return $this->status; }
    public function setStatus(int $status): self { $this->status = $status; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }

    public function getVatType(): int { return $this->vatType; }
    public function setVatType(int $vatType): self { $this->vatType = $vatType; return $this; }

    public function getVatNumber(): ?string { return $this->vatNumber; }
    public function setVatNumber(?string $vatNumber): self { $this->vatNumber = $vatNumber; return $this; }

    public function getTaxNumber(): ?string { return $this->taxNumber; }
    public function setTaxNumber(?string $taxNumber): self { $this->taxNumber = $taxNumber; return $this; }

    public function getDiscount(): ?int { return $this->discount; }
    public function setDiscount(?int $discount): self { $this->discount = $discount; return $this; }

    public function getDelivery(): ?float { return $this->delivery; }
    public function setDelivery(?float $delivery): self { $this->delivery = $delivery; return $this; }

    public function getDeliveryType(): ?int { return $this->deliveryType; }
    public function setDeliveryType(?int $deliveryType): self { $this->deliveryType = $deliveryType; return $this; }

    public function getDeliveryTimeMin(): ?\DateTimeInterface { return $this->deliveryTimeMin; }
    public function setDeliveryTimeMin(?\DateTimeInterface $v): self { $this->deliveryTimeMin = $v; return $this; }

    public function getDeliveryTimeMax(): ?\DateTimeInterface { return $this->deliveryTimeMax; }
    public function setDeliveryTimeMax(?\DateTimeInterface $v): self { $this->deliveryTimeMax = $v; return $this; }

    public function getDeliveryTimeConfirmMin(): ?\DateTimeInterface { return $this->deliveryTimeConfirmMin; }
    public function setDeliveryTimeConfirmMin(?\DateTimeInterface $v): self { $this->deliveryTimeConfirmMin = $v; return $this; }

    public function getDeliveryTimeConfirmMax(): ?\DateTimeInterface { return $this->deliveryTimeConfirmMax; }
    public function setDeliveryTimeConfirmMax(?\DateTimeInterface $v): self { $this->deliveryTimeConfirmMax = $v; return $this; }

    public function getDeliveryTimeFastPayMin(): ?\DateTimeInterface { return $this->deliveryTimeFastPayMin; }
    public function setDeliveryTimeFastPayMin(?\DateTimeInterface $v): self { $this->deliveryTimeFastPayMin = $v; return $this; }

    public function getDeliveryTimeFastPayMax(): ?\DateTimeInterface { return $this->deliveryTimeFastPayMax; }
    public function setDeliveryTimeFastPayMax(?\DateTimeInterface $v): self { $this->deliveryTimeFastPayMax = $v; return $this; }

    public function getDeliveryOldTimeMin(): ?\DateTimeInterface { return $this->deliveryOldTimeMin; }
    public function setDeliveryOldTimeMin(?\DateTimeInterface $v): self { $this->deliveryOldTimeMin = $v; return $this; }

    public function getDeliveryOldTimeMax(): ?\DateTimeInterface { return $this->deliveryOldTimeMax; }
    public function setDeliveryOldTimeMax(?\DateTimeInterface $v): self { $this->deliveryOldTimeMax = $v; return $this; }

    public function getDeliveryIndex(): ?string { return $this->deliveryIndex; }
    public function setDeliveryIndex(?string $v): self { $this->deliveryIndex = $v; return $this; }

    public function getDeliveryCountry(): ?int { return $this->deliveryCountry; }
    public function setDeliveryCountry(?int $v): self { $this->deliveryCountry = $v; return $this; }

    public function getDeliveryRegion(): ?string { return $this->deliveryRegion; }
    public function setDeliveryRegion(?string $v): self { $this->deliveryRegion = $v; return $this; }

    public function getDeliveryCity(): ?string { return $this->deliveryCity; }
    public function setDeliveryCity(?string $v): self { $this->deliveryCity = $v; return $this; }

    public function getDeliveryAddress(): ?string { return $this->deliveryAddress; }
    public function setDeliveryAddress(?string $v): self { $this->deliveryAddress = $v; return $this; }

    public function getDeliveryBuilding(): ?string { return $this->deliveryBuilding; }
    public function setDeliveryBuilding(?string $v): self { $this->deliveryBuilding = $v; return $this; }

    public function getDeliveryPhoneCode(): ?string { return $this->deliveryPhoneCode; }
    public function setDeliveryPhoneCode(?string $v): self { $this->deliveryPhoneCode = $v; return $this; }

    public function getDeliveryPhone(): ?string { return $this->deliveryPhone; }
    public function setDeliveryPhone(?string $v): self { $this->deliveryPhone = $v; return $this; }

    public function getSex(): ?int { return $this->sex; }
    public function setSex(?int $sex): self { $this->sex = $sex; return $this; }

    public function getClientName(): ?string { return $this->clientName; }
    public function setClientName(?string $v): self { $this->clientName = $v; return $this; }

    public function getClientSurname(): ?string { return $this->clientSurname; }
    public function setClientSurname(?string $v): self { $this->clientSurname = $v; return $this; }

    public function getCompanyName(): ?string { return $this->companyName; }
    public function setCompanyName(?string $v): self { $this->companyName = $v; return $this; }

    public function getPayType(): int { return $this->payType; }
    public function setPayType(int $payType): self { $this->payType = $payType; return $this; }

    public function getPayDateExecution(): ?\DateTimeInterface { return $this->payDateExecution; }
    public function setPayDateExecution(?\DateTimeInterface $v): self { $this->payDateExecution = $v; return $this; }

    public function getOffsetDate(): ?\DateTimeInterface { return $this->offsetDate; }
    public function setOffsetDate(?\DateTimeInterface $v): self { $this->offsetDate = $v; return $this; }

    public function getOffsetReason(): ?int { return $this->offsetReason; }
    public function setOffsetReason(?int $v): self { $this->offsetReason = $v; return $this; }

    public function getProposedDate(): ?\DateTimeInterface { return $this->proposedDate; }
    public function setProposedDate(?\DateTimeInterface $v): self { $this->proposedDate = $v; return $this; }

    public function getShipDate(): ?\DateTimeInterface { return $this->shipDate; }
    public function setShipDate(?\DateTimeInterface $v): self { $this->shipDate = $v; return $this; }

    public function getTrackingNumber(): ?string { return $this->trackingNumber; }
    public function setTrackingNumber(?string $v): self { $this->trackingNumber = $v; return $this; }

    public function getManagerName(): ?string { return $this->managerName; }
    public function setManagerName(?string $v): self { $this->managerName = $v; return $this; }

    public function getManagerEmail(): ?string { return $this->managerEmail; }
    public function setManagerEmail(?string $v): self { $this->managerEmail = $v; return $this; }

    public function getManagerPhone(): ?string { return $this->managerPhone; }
    public function setManagerPhone(?string $v): self { $this->managerPhone = $v; return $this; }

    public function getCarrierName(): ?string { return $this->carrierName; }
    public function setCarrierName(?string $v): self { $this->carrierName = $v; return $this; }

    public function getCarrierContactData(): ?string { return $this->carrierContactData; }
    public function setCarrierContactData(?string $v): self { $this->carrierContactData = $v; return $this; }

    public function getLocale(): string { return $this->locale; }
    public function setLocale(string $locale): self { $this->locale = $locale; return $this; }

    public function getCurRate(): ?float { return $this->curRate; }
    public function setCurRate(?float $curRate): self { $this->curRate = $curRate; return $this; }

    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): self { $this->currency = $currency; return $this; }

    public function getMeasure(): string { return $this->measure; }
    public function setMeasure(string $measure): self { $this->measure = $measure; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): self { $this->description = $v; return $this; }

    public function getCreateDate(): \DateTimeInterface { return $this->createDate; }
    public function setCreateDate(\DateTimeInterface $v): self { $this->createDate = $v; return $this; }

    public function getUpdateDate(): ?\DateTimeInterface { return $this->updateDate; }
    public function setUpdateDate(?\DateTimeInterface $v): self { $this->updateDate = $v; return $this; }

    public function getWarehouseData(): ?string { return $this->warehouseData; }
    public function setWarehouseData(?string $v): self { $this->warehouseData = $v; return $this; }

    public function getStep(): int { return $this->step; }
    public function setStep(int $step): self { $this->step = $step; return $this; }

    public function isAddressEqual(): ?bool { return $this->addressEqual; }
    public function setAddressEqual(?bool $v): self { $this->addressEqual = $v; return $this; }

    public function isBankTransferRequested(): ?bool { return $this->bankTransferRequested; }
    public function setBankTransferRequested(?bool $v): self { $this->bankTransferRequested = $v; return $this; }

    public function isAcceptPay(): ?bool { return $this->acceptPay; }
    public function setAcceptPay(?bool $v): self { $this->acceptPay = $v; return $this; }

    public function getCancelDate(): ?\DateTimeInterface { return $this->cancelDate; }
    public function setCancelDate(?\DateTimeInterface $v): self { $this->cancelDate = $v; return $this; }

    public function getWeightGross(): ?float { return $this->weightGross; }
    public function setWeightGross(?float $v): self { $this->weightGross = $v; return $this; }

    public function isProductReview(): ?bool { return $this->productReview; }
    public function setProductReview(?bool $v): self { $this->productReview = $v; return $this; }

    public function getMirror(): ?int { return $this->mirror; }
    public function setMirror(?int $mirror): self { $this->mirror = $mirror; return $this; }

    public function isProcess(): ?bool { return $this->process; }
    public function setProcess(?bool $v): self { $this->process = $v; return $this; }

    public function getFactDate(): ?\DateTimeInterface { return $this->factDate; }
    public function setFactDate(?\DateTimeInterface $v): self { $this->factDate = $v; return $this; }

    public function getEntranceReview(): ?int { return $this->entranceReview; }
    public function setEntranceReview(?int $v): self { $this->entranceReview = $v; return $this; }

    public function isPaymentEuro(): ?bool { return $this->paymentEuro; }
    public function setPaymentEuro(?bool $v): self { $this->paymentEuro = $v; return $this; }

    public function isSpecPrice(): ?bool { return $this->specPrice; }
    public function setSpecPrice(?bool $v): self { $this->specPrice = $v; return $this; }

    public function isShowMsg(): ?bool { return $this->showMsg; }
    public function setShowMsg(?bool $v): self { $this->showMsg = $v; return $this; }

    public function getDeliveryPriceEuro(): ?float { return $this->deliveryPriceEuro; }
    public function setDeliveryPriceEuro(?float $v): self { $this->deliveryPriceEuro = $v; return $this; }

    public function getAddressPayer(): ?int { return $this->addressPayer; }
    public function setAddressPayer(?int $v): self { $this->addressPayer = $v; return $this; }

    public function getSendingDate(): ?\DateTimeInterface { return $this->sendingDate; }
    public function setSendingDate(?\DateTimeInterface $v): self { $this->sendingDate = $v; return $this; }

    public function getDeliveryCalculateType(): ?int { return $this->deliveryCalculateType; }
    public function setDeliveryCalculateType(?int $v): self { $this->deliveryCalculateType = $v; return $this; }

    public function getFullPaymentDate(): ?\DateTimeInterface { return $this->fullPaymentDate; }
    public function setFullPaymentDate(?\DateTimeInterface $v): self { $this->fullPaymentDate = $v; return $this; }

    public function getBankDetails(): ?string { return $this->bankDetails; }
    public function setBankDetails(?string $v): self { $this->bankDetails = $v; return $this; }

    public function getDeliveryApartmentOffice(): ?string { return $this->deliveryApartmentOffice; }
    public function setDeliveryApartmentOffice(?string $v): self { $this->deliveryApartmentOffice = $v; return $this; }

    /** @return Collection<int, OrderArticle> */
    public function getArticles(): Collection { return $this->articles; }

    public function addArticle(OrderArticle $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setOrder($this);
        }
        return $this;
    }

    public function removeArticle(OrderArticle $article): self
    {
        if ($this->articles->removeElement($article)) {
            if ($article->getOrder() === $this) {
                $article->setOrder(null);
            }
        }
        return $this;
    }
}
