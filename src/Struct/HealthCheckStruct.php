<?php declare(strict_types=1);

namespace Tadpole\OhDear\Struct;

use Shopware\Core\Framework\Struct\Struct;

class HealthCheckStruct extends Struct
{
    protected string $name;

    protected string $label;

    protected string $status;

    protected string $notificationMessage;

    protected string $shortSummary;

    protected ?array $meta;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getNotificationMessage(): string
    {
        return $this->notificationMessage;
    }

    /**
     * @param string $notificationMessage
     */
    public function setNotificationMessage(string $notificationMessage): void
    {
        $this->notificationMessage = $notificationMessage;
    }

    /**
     * @return string
     */
    public function getShortSummary(): string
    {
        return $this->shortSummary;
    }

    /**
     * @param string $shortSummary
     */
    public function setShortSummary(string $shortSummary): void
    {
        $this->shortSummary = $shortSummary;
    }

    /**
     * @return array
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * @param array $meta
     */
    public function setMeta(array $meta): void
    {
        $this->meta = $meta;
    }
}