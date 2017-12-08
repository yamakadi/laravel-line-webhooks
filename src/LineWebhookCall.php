<?php

namespace Yamakadi\LineWebhooks;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Yamakadi\LineBot\Events\Event;
use Yamakadi\LineWebhooks\Exceptions\WebhookFailed;

class LineWebhookCall extends Model
{
    public $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'exception' => 'array',
    ];

    public function process(Event $event)
    {
        $this->clearException();

        event("line-webhooks::{$this->type}", $event, $this);

        $jobClass = $this->determineJobClass($this->type);

        if ($jobClass === '') {
            return;
        }

        if (! class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this);
        }

        dispatch(new $jobClass($event, $this));
    }

    public function saveException(Exception $exception)
    {
        $this->exception = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ];

        $this->save();

        return $this;
    }

    protected function determineJobClass(string $eventType): string
    {
        $jobConfigKey = snake_case(class_basename($eventType));

        return config("line-webhooks.jobs.{$jobConfigKey}", '');
    }

    protected function clearException()
    {
        $this->exception = null;

        $this->save();

        return $this;
    }
}
