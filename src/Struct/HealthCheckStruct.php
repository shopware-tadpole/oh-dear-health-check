<?php

declare(strict_types=1);

namespace Tadpole\OhDear\Struct;

use Shopware\Core\Framework\Struct\Struct;
use Tadpole\OhDear\Enum\HealthStatus;

class HealthCheckStruct extends Struct
{
    protected string $name;

    protected string $label;

    protected HealthStatus $status = HealthStatus::FAILED;

    protected string $notificationMessage = '';

    protected string $shortSummary = '';

    protected ?array $meta = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    public function getStatus(): HealthStatus
    {
        return $this->status;
    }

    public function setStatus(HealthStatus $status): void
    {
        $this->status = $status;
    }

    public function getNotificationMessage(): string
    {
        return $this->notificationMessage;
    }

    public function setNotificationMessage(string $notificationMessage): void
    {
        $this->notificationMessage = $notificationMessage;
    }

    public function getShortSummary(): string
    {
        return $this->shortSummary;
    }

    public function setShortSummary(string $shortSummary): void
    {
        $this->shortSummary = $shortSummary;
    }

    /**
     * @return array<string, string>
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param  array<string, string>  $meta
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }
}
