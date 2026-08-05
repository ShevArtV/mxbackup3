# mxBackup

`mxBackup` — бесплатный пакет резервного копирования для MODX Revolution 3. Он
создаёт архив файлов сайта и SQL-дамп базы данных из CLI, cron или менеджера,
сохраняет manifest и историю запусков, а при необходимости восстанавливает сайт
из ранее созданного архива.

Пакет поддерживает два готовых профиля:

- `prod` — полная аварийная копия без изменения данных;
- `dev` — обезличенная копия для разработки: сессии удаляются, персональные
  данные MODX и miniShop2 заменяются стабильными тестовыми значениями.

Требования: MODX 3.0+, PHP 8.1+, MySQL/MariaDB и PDO MySQL. Для ZIP нужен
`ext-zip`, для `tar.gz` — `Phar` и `zlib`. Для AES-256 нужен `ext-zip`,
собранный с libzip 1.2 или новее. Интерфейс менеджера требует пакет
[VueTools](https://github.com/modx-pro/vuetools) — общий стек Vue 3 + PrimeVue.

## Установка

Установите transport через «Пакеты → Установщик». После установки в меню
«Компоненты» появится mxBackup, а в каталоге конфигураций будут созданы файловые
профили `prod` и `dev`.

По умолчанию архивы сохраняются в каталоге `backups` рядом с webroot. Путь
внутри webroot, `core`, кэша или корня файловой системы отклоняется до запуска.

## Управление в менеджере

Компонент — одна страница с вкладками:

- **Профили** — режим `prod`/`dev`, формат архива, AES-256 шифрование ZIP и
  include/exclude для файлов;
- **Таблицы БД** — включение и исключение реальных таблиц сайта для каждого
  профиля, поиск по любой части имени, число строк, размер и серверная
  пагинация. «Включить все» и «Исключить все» применяются только к результатам
  текущего поиска; с пустым поиском — ко всем таблицам;
- **Обезличивание** — выбор таблицы и действия для каждой её колонки: маска,
  скрытие, хеш или фиксированная замена. Встроенные правила видны отдельно от
  пользовательских, а содержимое служебной таблицы можно полностью очистить;
- **История** — тип запуска, локализованный статус, архив, размер и
  длительность; двойной клик открывает подробный отчёт. Для успешного backup
  доступно восстановление после обязательной проверки архива и ввода кода
  подтверждения; доступ управляется правом `mxbackup_restore`;
- **Настройки** — системные настройки пакета без перехода в общий раздел.

Кнопки запуска, dry-run и проверки конфигурации живут в шапке страницы и
работают с выбранным профилем.

В профиле `dev` стандартное обезличивание обязательно и не может быть отключено.
Пользовательские правила имеют приоритет над встроенными.

## CLI и cron

```bash
php core/components/mxbackup/cli/mxbackup.php validate-config
php core/components/mxbackup/cli/mxbackup.php dry-run --profile=dev
php core/components/mxbackup/cli/mxbackup.php backup --profile=prod
php core/components/mxbackup/cli/mxbackup.php cleanup
php core/components/mxbackup/cli/mxbackup.php restore-check --archive=/path/backup.zip
php core/components/mxbackup/cli/mxbackup.php restore --archive=/path/backup.zip --scope=all --confirm=TOKEN
```

Большие копии и восстановление запускайте из CLI: веб-запуск ограничен
таймаутами PHP и reverse proxy.

## Программный доступ

```php
$service = $modx->services->get('mxbackup'); // либо фасад $modx->mxbackup
$config = $service->config(['profile' => 'prod']);
$result = $service->runner()->run($config);
```

## Права доступа

Установка создаёт политику `mxbackupDefault` с правами `mxbackup_view`,
`mxbackup_manage`, `mxbackup_run`, `mxbackup_restore` и `load`. Группам политика
автоматически не назначается — доступ выдаётся через Access Controls.

## Разработка

```bash
composer install                                   # phpunit
vendor/bin/phpunit                                 # тесты ядра и архитектуры
cd assets/components/mxbackup && npm install && npm run build   # бандл админки
modxapp build mxbackup --no-check                  # transport-пакет
```

Сборщик — [shevartv/modx-builder](https://github.com/shevartv/modx-builder)
(CLI `modxapp`), устанавливается глобально; его конфигурация лежит в репозитории
(`modxapp.json` + `package_builder/packages/mxbackup/`).

Ядро в `core/components/mxbackup/src/Core/` не зависит от MODX: платформа
скрыта за интерфейсами, реализация тройки — в `src/Platform/Modx3/`. Это
проверяется архитектурным тестом.

## Лицензия

GPL-2.0-or-later, см. [LICENSE](LICENSE).
