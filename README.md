
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

## Лицензия

Proprietary - Balík PRO MVP © 2026
