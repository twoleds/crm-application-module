<?php

namespace Crm\ApplicationModule\Api;

use Crm\ApiModule\Api\ApiHandler;
use Crm\ApiModule\Api\JsonResponse;
use Crm\ApiModule\Response\ApiResponseInterface;
use Crm\ApplicationModule\Event\EventsStorage;
use Nette\Http\Response;

class EventGeneratorsListApiHandler extends ApiHandler
{
    private $eventsStorage;

    public function __construct(
        EventsStorage $eventsStorage
    ) {
        $this->eventsStorage = $eventsStorage;
    }

    public function params(): array
    {
        return [];
    }

    public function handle(array $params): ApiResponseInterface
    {
        $events = $this->eventsStorage->getEvents();
        $eventGenerators = $this->eventsStorage->getEventGenerators();

        $result = [];
        foreach ($eventGenerators as $code => $eventGenerator) {
            $result[] = [
                'code' => $code,
                'name' => $events[$code]['name'],
            ];
        }

        $response = new JsonResponse(['status' => 'ok', 'events' => $result]);
        $response->setHttpCode(Response::S200_OK);

        return $response;
    }
}
