
# Balík PRO MVP

Словацкий портал продажи услуг с купонами и бонусами.

## Описание

Balík PRO - это MVP платформа, которая позволяет:
- Продавать пакеты услуг (1 основная + N бонусных)
- Обрабатывать платежи через мок-версию Revolut Pay
- Генерировать купоны с QR-кодами в PDF формате
- Управлять активацией купонов через PIN аутентификацию партнеров
- Предоставлять партнерскую панель с статистикой и экспортом данных
- **Premium UI/UX**: Современный Dark Mode дизайн с Glassmorphism эффектами
- Административная панель на базе Textolite 2.12e

## Технологический стек

- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+ (InnoDB, utf8mb4)
- **Authentication**: JWT для партнеров, bcrypt для паролей
- **PDF Generation**: mPDF (мок-версия для разработки)
- **QR Codes**: phpqrcode (мок-версия для разработки)
- **Payments**: Revolut Pay (мок-версия для разработки)
- **Email**: Mailgun (мок-версия для разработки)
- **Frontend**: Vanilla JS, CSS Variables (Dark Mode/Glassmorphism), HTML5
- **Design**: Balík PRO Premium Design System (Custom CSS)
- **Admin Panel**: Textolite 2.12e

## Структура проекта

```
balik_pro_mvp/
├── admin/                  # Textolite админ-панель
├── config/                 # Конфигурационные файлы
├── data/                   # SQL схемы и данные
├── public/                 # Публичные файлы (веб-корень)
│   ├── api/               # REST API endpoints
│   ├── assets/            # CSS, JS, изображения
│   ├── partner/           # Партнерская панель
│   ├── checkout/          # Страницы результатов платежа
│   └── mock-payment/      # Мок-платежная система
├── scripts/               # Скрипты установки и обслуживания
├── src/                   # Исходный код приложения
│   ├── Controllers/       # Контроллеры API
│   ├── Models/           # Модели данных
│   ├── Services/         # Бизнес-логика и интеграции
│   └── Utils/            # Вспомогательные классы
├── storage/              # Хранилище файлов
│   ├── logs/            # Логи
│   ├── pdfs/            # Сгенерированные PDF
│   └── temp/            # Временные файлы
└── vendor/              # Зависимости (мок-версии)
```

## Установка и настройка

### Требования

- PHP 7.4+ с расширениями: pdo, pdo_mysql, gd, curl, json
- MySQL 8.0+
- Apache/Nginx с mod_rewrite
- SSL сертификат для продакшена

### Шаг 1: Подготовка окружения

```bash
# Клонирование/копирование проекта
cp -r /home/ubuntu/balik_pro_mvp /var/www/balikpro.sk/

# Установка прав доступа
chown -R www-data:www-data /var/www/balikpro.sk/
chmod -R 755 /var/www/balikpro.sk/
chmod -R 777 /var/www/balikpro.sk/storage/
chmod -R 777 /var/www/balikpro.sk/public/uploads/
```

### Шаг 2: Настройка базы данных

```bash
# Создание базы данных MySQL
mysql -u root -p -e "CREATE DATABASE balikpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Настройка пользователя БД (опционально)
mysql -u root -p -e "CREATE USER 'balikpro'@'localhost' IDENTIFIED BY 'secure_password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON balikpro.* TO 'balikpro'@'localhost';"
mysql -u root -p -e "FLUSH PRIVILEGES;"
```

### Шаг 3: Конфигурация приложения

```bash
# Редактирование переменных окружения
vi /var/www/balikpro.sk/.env

# Основные настройки
DB_HOST=localhost
DB_NAME=balikpro
DB_USERNAME=balikpro
DB_PASSWORD=secure_password

# Для продакшена
APP_DEBUG=false
JWT_SECRET=your-secure-jwt-secret-key
REVOLUT_IS_MOCK=false
MAILGUN_IS_MOCK=false
```

### Шаг 4: Инициализация базы данных

```bash
cd /var/www/balikpro.sk/
php scripts/setup_database.php
```

### Шаг 5: Настройка веб-сервера

#### Apache Virtual Host

```apache
<VirtualHost *:443>
    ServerName balikpro.sk
    DocumentRoot /var/www/balikpro.sk/public
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    # Directory settings
    <Directory /var/www/balikpro.sk/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/balikpro_error.log
    CustomLog ${APACHE_LOG_DIR}/balikpro_access.log combined
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName balikpro.sk
    Redirect permanent / https://balikpro.sk/
</VirtualHost>
```

#### Nginx Configuration

```nginx
server {
    listen 443 ssl http2;
    server_name balikpro.sk;
    root /var/www/balikpro.sk/public;
    
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # Security headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains";
    
    # API routes
    location ~ ^/api/(.*)$ {
        try_files $uri /api/index.php$is_args$args;
    }
    
    # Webhook routes
    location ~ ^/webhook/(.*)$ {
        try_files $uri /api/webhook.php$is_args$args;
    }
    
    # Coupon redemption
    location ~ ^/redeem/([0-9]+)/([a-f0-9]{64})$ {
        try_files $uri /redeem.php?coupon_id=$1&qr_hash=$2;
    }
    
    # Admin panel
    location /admin/ {
        try_files $uri /admin/textolite.php$is_args$args;
    }
    
    # PHP processing
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static files
    location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name balikpro.sk;
    return 301 https://$server_name$request_uri;
}
```

## Использование

### Учетные записи по умолчанию

После установки создаются следующие учетные записи:

**Superadmin:**
- Email: `roccreate@gmail.com`
- Пароль: `admin123`

**Тестовые партнеры:**
- Partner ID: `1-8`
- PIN: `pin1`, `pin2`, ..., `pin8`

### Основные URL

- **Главная страница**: `https://balikpro.sk/`
- **Партнерская панель**: `https://balikpro.sk/partner/`
- **Админ-панель**: `https://balikpro.sk/admin/`
- **API документация**: согласно `openapi.yaml`

### Тестирование функционала

1. **Публичный каталог**: Откройте главную страницу и просмотрите доступные пакеты
2. **Процесс заказа**: Выберите пакет и пройдите процесс оформления заказа
3. **Мок-платеж**: Используйте симулятор платежей для тестирования
4. **Активация купонов**: Используйте QR-коды или прямые ссылки
5. **Партнерская панель**: Войдите как партнер и просмотрите статистику

## API Endpoints

### Публичные

- `GET /api/bundles` - Список активных пакетов
- `GET /api/bundles/{id}` - Детали пакета
- `POST /api/checkout` - Создание заказа
- `GET /redeem/{coupon_id}/{qr_hash}` - Страница активации купона
- `POST /api/coupons/{coupon_id}/redeem` - Активация купона

### Партнерские

- `POST /api/partner/auth` - Аутентификация партнера
- `GET /api/partner/{id}/dashboard` - Статистика партнера
- `GET /api/partner/{id}/coupons` - Список купонов партнера

### Webhooks

- `POST /webhook/revolut` - Обработка платежных уведомлений

## Безопасность

### Аутентификация

- **Админы**: email + bcrypt пароль
- **Партнеры**: partner_id + bcrypt PIN
- **JWT токены** с TTL 8 часов для API сессий

### Защита данных

- Хеширование всех паролей и PIN-кодов
- Webhook signature verification (для продакшена)
- SQL injection защита через prepared statements
- XSS защита через htmlspecialchars
- CSRF защита для форм

### GDPR Compliance

- Минимальные персональные данные клиентов
- Возможность анонимизации/удаления данных клиента
- Логирование доступа к персональным данным

## Мониторинг и поддержка

### Логирование

Все логи сохраняются в `storage/logs/`:

- `app.log` - общие логи приложения
- `api.log` - логи API запросов
- `models.log` - логи работы с БД
- `revolut.log` - логи платежей
- `email.log` - логи отправки email
- `partner.log` - логи партнерской панели

### Мониторинг производительности

Рекомендуется настроить мониторинг:
- Время отклика API endpoints
- Использование памяти и CPU
- Размер логов и базы данных
- Успешность платежных транзакций

### Резервное копирование

```bash
# Бэкап базы данных
mysqldump -u root -p balikpro > backup_$(date +%Y%m%d_%H%M%S).sql

# Бэкап файлов
tar -czf balikpro_files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/balikpro.sk/storage/
```

## Развитие и масштабирование

### Переход на продакшен

1. **Установка реальных зависимостей:**
   ```bash
   composer require firebase/php-jwt mpdf/mpdf
   # Установка настоящего phpqrcode
   ```

2. **Настройка реальных интеграций:**
   - Revolut Pay API ключи
   - Mailgun API ключи
   - SSL сертификаты

3. **Оптимизация производительности:**
   - Redis/Memcached для кеширования
   - CDN для статических файлов
   - Оптимизация запросов БД

### Добавление новых партнеров

```sql
INSERT INTO partners (uuid, name, contact_person, email, phone, pin_hash, monthly_limit) 
VALUES (UUID(), 'Partner Name', 'Contact Person', 'email@domain.sk', '+421XXXXXXXXX', 'bcrypt_hash', 1000);
```

## Поддержка и контакты

- **Email**: support@balikpro.sk
- **Техническая документация**: `/docs/`
- **API документация**: `openapi.yaml`

## Лицензия

Proprietary - Balík PRO MVP © 2025
