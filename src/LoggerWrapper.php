<?php

declare(strict_types=1);

namespace App;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Bitrix24\SDK\Core\Exceptions\InvalidArgumentException;

final readonly class LoggerWrapper
{
    private LoggerInterface $logger;

    public function __construct(?string $loggerName = null)
    {
        // для локальной отладки
        // логи на windows пишем в каталог проекта
        $logDirectory = PHP_OS_FAMILY === 'Windows' ? 'var/logs/' : '/var/logs/';
        $this->logger = new Logger($loggerName ?? 'App');
        $rotatingFileHandler = new RotatingFileHandler($logDirectory . 'b24-php-sdk.log', 30, Level::Debug);
        $rotatingFileHandler->setFilenameFormat('{filename}-{date}', 'Y-m-d');
        $this->logger->pushHandler($rotatingFileHandler);
        $this->logger->pushProcessor(new MemoryUsageProcessor(true, true));
        $this->logger->pushProcessor(new UidProcessor());
        return $this->logger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function info(string $message): void
    {
        $this->logger->info($message);
    }

    public function logInvalidArgumentException(InvalidArgumentException $exception): void
    {
        $this->logger->critical('app.configuration.problem', ['exception' => $exception]);
        print(sprintf('ERROR IN CONFIGURATION OR CALL ARGS: %s', $exception->getMessage()) . PHP_EOL);
        print($exception::class . PHP_EOL);
        print($exception->getTraceAsString());
    }

    public function logThrowable(\Throwable $exception): void
    {
        $this->logger->critical('app.failure', ['exception' => $exception,]);
        print(sprintf('FATAL ERROR: %s', $exception->getMessage()) . PHP_EOL);
        print($exception::class . PHP_EOL);
        print($exception->getTraceAsString());
    }
}
