-- ============================================================================
-- FULLY NORMALIZED DATABASE SCHEMA DUMP
-- Features:
-- 1. 3rd Normal Form (3NF) decomposition of the monolithic `orders` table.
-- 2. Precision DECIMAL types for all monetary values, exchange rates, and quantities.
-- 3. `is_deleted` flag for Soft Delete across all entities.
-- 4. Foreign Key Constraints with ON DELETE RESTRICT and ON UPDATE RESTRICT.
-- 5. UNIQUE Constraints on order hashes and tokens.
-- 6. Standalone Reference Tables for Managers and Carriers.
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS orders_article;
DROP TABLE IF EXISTS order_schedules;
DROP TABLE IF EXISTS order_deliveries;
DROP TABLE IF EXISTS order_customers;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS carriers;
DROP TABLE IF EXISTS managers;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- Table structure for managers (Справочник менеджеров)
-- ----------------------------------------------------------------------------
CREATE TABLE managers
(
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(50)              NOT NULL COMMENT 'Имя менеджера',
    email      VARCHAR(100)             NULL COMMENT 'Email менеджера',
    phone      VARCHAR(30)              NULL COMMENT 'Телефон менеджера',
    is_deleted TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг мягкого удаления',

    INDEX idx_managers_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Справочник менеджеров';

-- ----------------------------------------------------------------------------
-- Table structure for carriers (Справочник транспортных компаний)
-- ----------------------------------------------------------------------------
CREATE TABLE carriers
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)             NOT NULL COMMENT 'Название транспортной компании',
    contact_data VARCHAR(255)             NULL COMMENT 'Контактные данные ТК',
    is_deleted   TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг мягкого удаления',

    INDEX idx_carriers_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Справочник транспортных компаний';

-- ----------------------------------------------------------------------------
-- Table structure for orders (Основная таблица заказа)
-- ----------------------------------------------------------------------------
CREATE TABLE orders
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    hash          VARCHAR(32)              NOT NULL COMMENT 'Уникальный хеш заказа',
    token         VARCHAR(64)              NOT NULL COMMENT 'Уникальный токен сессии пользователя',
    number        VARCHAR(20)              NULL COMMENT 'Номер заказа',
    user_id       INT                      NULL COMMENT 'ID пользователя (если зарегистрирован)',
    manager_id    INT                      NULL COMMENT 'ID менеджера сопровождающего заказ',
    status        INT        DEFAULT 1     NOT NULL COMMENT 'Статус заказа',
    pay_type      SMALLINT                 NOT NULL COMMENT 'Выбранный тип оплаты',
    currency      VARCHAR(3) DEFAULT 'EUR' NOT NULL COMMENT 'Валюта заказа',
    cur_rate      DECIMAL(10, 4) DEFAULT 1 NOT NULL COMMENT 'Курс валюты на момент оплаты',
    measure       VARCHAR(10) DEFAULT 'm2' NOT NULL COMMENT 'Единица измерения заказа',
    name          VARCHAR(200)             NOT NULL COMMENT 'Название заказа',
    description   TEXT                     NULL COMMENT 'Дополнительное примечание',
    step          SMALLINT   DEFAULT 1     NOT NULL COMMENT 'Шаг оформления заказа',
    accept_pay    TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг отправки заказа в работу (1 - отправлен)',
    payment_euro  TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг расчета оплаты в Евро',
    spec_price    TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг установленной спец-цены',
    mirror        SMALLINT                 NULL COMMENT 'Метка зеркала сайта',
    process       TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Метка массовой обработки',
    locale        VARCHAR(5)               NOT NULL COMMENT 'Локаль оформления заказа',
    create_date   DATETIME                 NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Дата создания',
    update_date   DATETIME                 NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Дата изменения',
    is_deleted    TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг мягкого удаления',

    CONSTRAINT UNIQ_ORDERS_HASH UNIQUE (hash),
    CONSTRAINT UNIQ_ORDERS_TOKEN UNIQUE (token),
    CONSTRAINT FK_ORDERS_MANAGER 
        FOREIGN KEY (manager_id) 
        REFERENCES managers (id) 
        ON DELETE RESTRICT 
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ядро заказов (основные бизнес-параметры)';

CREATE INDEX idx_orders_user_id ON orders (user_id);
CREATE INDEX idx_orders_manager_id ON orders (manager_id);
CREATE INDEX idx_orders_create_date ON orders (create_date);
CREATE INDEX idx_orders_status ON orders (status);
CREATE INDEX idx_orders_is_deleted ON orders (is_deleted);

-- ----------------------------------------------------------------------------
-- Table structure for order_customers (Данные клиента и реквизиты плательщика)
-- ----------------------------------------------------------------------------
CREATE TABLE order_customers
(
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    orders_id               INT                      NOT NULL UNIQUE COMMENT 'ID заказа',
    email                   VARCHAR(100)             NULL COMMENT 'Контактный Email',
    sex                     SMALLINT                 NULL COMMENT 'Пол клиента (1 - муж, 2 - жен)',
    client_name             VARCHAR(255)             NULL COMMENT 'Имя клиента',
    client_surname          VARCHAR(255)             NULL COMMENT 'Фамилия клиента',
    company_name            VARCHAR(255)             NULL COMMENT 'Название компании',
    vat_type                INT        DEFAULT 0     NOT NULL COMMENT '0 - физлицо, 1 - плательщик НДС',
    vat_number              VARCHAR(100)             NULL COMMENT 'НДС номер',
    tax_number              VARCHAR(50)              NULL COMMENT 'ИНН плательщика',
    address_payer_id        INT                      NULL COMMENT 'ID адреса плательщика',
    bank_transfer_requested TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Запрос безналичного расчета (1 - да)',
    bank_details            TEXT                     NULL COMMENT 'Реквизиты банка',
    product_review          TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг оставленного отзыва',
    entrance_review         SMALLINT                 NULL COMMENT 'Фиксация захода на страницу отзыва',
    is_deleted              TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг мягкого удаления',

    CONSTRAINT FK_ORDER_CUSTOMERS_ORDERS
        FOREIGN KEY (orders_id) 
        REFERENCES orders (id) 
        ON DELETE RESTRICT 
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Данные клиента и плательщика заказа';

CREATE INDEX idx_order_customers_is_deleted ON order_customers (is_deleted);

-- ----------------------------------------------------------------------------
-- Table structure for order_deliveries (Сведения о доставке и адресе)
-- ----------------------------------------------------------------------------
CREATE TABLE order_deliveries
(
    id                      INT AUTO_INCREMENT PRIMARY KEY,
    orders_id               INT                      NOT NULL UNIQUE COMMENT 'ID заказа',
    carrier_id              INT                      NULL COMMENT 'ID транспортной компании',
    delivery_type           SMALLINT   DEFAULT 0     NULL COMMENT 'Тип доставки: 0 - клиент, 1 - склад',
    delivery_calculate_type SMALLINT   DEFAULT 0     NULL COMMENT 'Тип расчета: 0 - ручной, 1 - авто',
    price                   DECIMAL(10, 2)           NULL COMMENT 'Стоимость доставки в валюте заказа',
    price_euro              DECIMAL(10, 2)           NULL COMMENT 'Стоимость доставки в евро',
    weight_gross            DECIMAL(10, 3)           NULL COMMENT 'Общий вес брутто (кг)',
    country_id              INT                      NULL COMMENT 'ID страны доставки',
    region                  VARCHAR(50)              NULL COMMENT 'Регион доставки',
    city                    VARCHAR(200)             NULL COMMENT 'Город доставки',
    address                 VARCHAR(300)             NULL COMMENT 'Адрес (улица, дом)',
    building                VARCHAR(200)             NULL COMMENT 'Корпус / строение',
    apartment_office        VARCHAR(30)              NULL COMMENT 'Квартира / офис',
    index_code              VARCHAR(20)              NULL COMMENT 'Почтовый индекс',
    phone_code              VARCHAR(20)              NULL COMMENT 'Код страны телефона',
    phone                   VARCHAR(20)              NULL COMMENT 'Номер телефона',
    address_equal           TINYINT(1) DEFAULT 1     NOT NULL COMMENT 'Совпадение адреса плательщика и доставки',
    tracking_number         VARCHAR(50)              NULL COMMENT 'Трек-номер посылки',
    warehouse_data          TEXT                     NULL COMMENT 'Информация о складе самовывоза',
    is_deleted              TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг мягкого удаления',

    CONSTRAINT FK_ORDER_DELIVERIES_ORDERS
        FOREIGN KEY (orders_id) 
        REFERENCES orders (id) 
        ON DELETE RESTRICT 
        ON UPDATE RESTRICT,
    CONSTRAINT FK_ORDER_DELIVERIES_CARRIERS
        FOREIGN KEY (carrier_id) 
        REFERENCES carriers (id) 
        ON DELETE RESTRICT 
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Сведения о доставке заказа';

CREATE INDEX idx_order_deliveries_country ON order_deliveries (country_id);
CREATE INDEX idx_order_deliveries_is_deleted ON order_deliveries (is_deleted);

-- ----------------------------------------------------------------------------
-- Table structure for order_schedules (Сроки и временные метки заказа)
-- ----------------------------------------------------------------------------
CREATE TABLE order_schedules
(
    id                          INT AUTO_INCREMENT PRIMARY KEY,
    orders_id                   INT                      NOT NULL UNIQUE COMMENT 'ID заказа',
    delivery_time_min           DATE                     NULL COMMENT 'Мин. срок доставки',
    delivery_time_max           DATE                     NULL COMMENT 'Макс. срок доставки',
    delivery_time_confirm_min   DATE                     NULL COMMENT 'Мин. срок доставки (подтвержден)',
    delivery_time_confirm_max   DATE                     NULL COMMENT 'Макс. срок доставки (подтвержден)',
    delivery_time_fast_pay_min  DATE                     NULL COMMENT 'Мин. срок доставки (быстрая оплата)',
    delivery_time_fast_pay_max  DATE                     NULL COMMENT 'Макс. срок доставки (быстрая оплата)',
    delivery_old_time_min       DATE                     NULL COMMENT 'Прошлый мин. срок доставки',
    delivery_old_time_max       DATE                     NULL COMMENT 'Прошлый макс. срок доставки',
    pay_date_execution          DATETIME                 NULL COMMENT 'Срок действия цены',
    offset_date                 DATETIME                 NULL COMMENT 'Дата сдвига сроков',
    offset_reason               SMALLINT                 NULL COMMENT 'Причина сдвига сроков',
    proposed_date               DATETIME                 NULL COMMENT 'Предполагаемая дата поставки',
    ship_date                   DATETIME                 NULL COMMENT 'Предполагаемая дата отгрузки',
    sending_date                DATETIME                 NULL COMMENT 'Расчетная дата поставки',
    fact_date                   DATETIME                 NULL COMMENT 'Фактическая дата поставки',
    cancel_date                 DATETIME                 NULL COMMENT 'Дата отмены / крайний срок',
    full_payment_date           DATE                     NULL COMMENT 'Дата полной оплаты',
    is_deleted                  TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг мягкого удаления',

    CONSTRAINT FK_ORDER_SCHEDULES_ORDERS
        FOREIGN KEY (orders_id) 
        REFERENCES orders (id) 
        ON DELETE RESTRICT 
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='График и временные метки доставки заказа';

CREATE INDEX idx_order_schedules_is_deleted ON order_schedules (is_deleted);

-- ----------------------------------------------------------------------------
-- Table structure for orders_article (Товары / Артикулы заказа)
-- ----------------------------------------------------------------------------
CREATE TABLE orders_article
(
    id                INT AUTO_INCREMENT PRIMARY KEY,
    orders_id         INT                      NOT NULL COMMENT 'ID родительского заказа',
    article_id        INT                      NULL COMMENT 'ID товара / артикула',
    amount            DECIMAL(10, 3)           NOT NULL COMMENT 'Количество в ед. измерения',
    price             DECIMAL(10, 2)           NOT NULL COMMENT 'Цена позиции на момент заказа',
    price_eur         DECIMAL(10, 2)           NULL COMMENT 'Цена позиции в евро',
    currency          VARCHAR(3)               NULL COMMENT 'Валюта цены',
    measure           VARCHAR(10)              NULL COMMENT 'Единица измерения товара',
    delivery_time_min DATE                     NULL COMMENT 'Мин. срок доставки для позиции',
    delivery_time_max DATE                     NULL COMMENT 'Макс. срок доставки для позиции',
    weight            DECIMAL(10, 3)           NOT NULL DEFAULT 0.000 COMMENT 'Вес позиции (кг)',
    multiple_pallet   SMALLINT                 NULL COMMENT 'Кратность палете',
    packaging_count   DECIMAL(10, 3)           NOT NULL DEFAULT 1.000 COMMENT 'Кратность упаковки',
    pallet            DECIMAL(10, 3)           NOT NULL DEFAULT 0.000 COMMENT 'Кол-во в палете',
    packaging         DECIMAL(10, 3)           NOT NULL DEFAULT 0.000 COMMENT 'Кол-во в упаковке',
    swimming_pool     TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Плитка для бассейна (1 - да)',
    is_deleted        TINYINT(1) DEFAULT 0     NOT NULL COMMENT 'Флаг мягкого удаления',

    CONSTRAINT FK_ORDERS_ARTICLE_ORDERS
        FOREIGN KEY (orders_id) 
        REFERENCES orders (id) 
        ON DELETE RESTRICT 
        ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Артикулы / Товары в заказе';

CREATE INDEX idx_orders_article_orders_id ON orders_article (orders_id);
CREATE INDEX idx_orders_article_article_id ON orders_article (article_id);
CREATE INDEX idx_orders_article_is_deleted ON orders_article (is_deleted);
