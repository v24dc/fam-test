<?php

declare(strict_types=1);

use Bitrix24\SDK\Core\Exceptions\InvalidArgumentException;
use Bitrix24\SDK\Services\ServiceBuilderFactory;
use App\LoggerWrapper;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new LoggerWrapper('App');

try {
    $logger->info('Creating service...');
    // берем URI вебхука из переменной среды или первого аргумента
    $webhookUri = getenv('BITRIX24_PHP_SDK_INCOMING_WEBHOOK_URL') ?? (count($argv) >= 2 ? $argv[1] : null);

    if (empty($webhookUri)) {
        print('Не указан URL входящего вебхука' . PHP_EOL);
        print('Установите переменную среды BITRIX24_PHP_SDK_INCOMING_WEBHOOK_URL или передайте URI параметром при запуске' . PHP_EOL);
        print('Пример запуска:' . PHP_EOL);
        print('php src/execute.php https://my-incoming-webhook-url.lan' . PHP_EOL);
        return;
    }

    $b24 = ServiceBuilderFactory::createServiceBuilderFromWebhook($webhookUri, null, $logger->getLogger());

    $addContactRes = $b24->getCRMScope()->contact()->add([
        'NAME' => 'Иван',
        'LAST_NAME' => 'Иванов'
    ]);
    $contactId = $addContactRes->getId();

    $addDealRes = $b24->getCRMScope()->deal()->add([
        'CONTACT_ID' => strval($contactId),
        'TITLE' => 'Тестовая сделка с контактом ID=' . $contactId,
        'OPPORTUNITY' => strval(115000)
    ]);
    $dealId = $addDealRes->getId();

    $deal = $b24->getCRMScope()->deal()->get($dealId);

    // добавить в сделку товары из каталога
    $res = $b24->getCRMScope()->dealProductRows()->set($dealId, [
        ['PRODUCT_NAME' => 'Ноутбук Dell', 'PRICE' => 55000, 'QUANTITY' => 2],
        //['PRODUCT_NAME' => 'Ноутбук Hp', 'PRICE' => 66000, 'QUANTITY' => 3]
    ]);

    $logger->info('Complete!');

    echo(json_encode(['contactId' => $contactId, 'dealId' => $dealId]));

} catch (InvalidArgumentException $exception) {
    $logger->logInvalidArgumentException($exception);
} catch (Throwable $throwable) {
    $logger->logThrowable($throwable);
}