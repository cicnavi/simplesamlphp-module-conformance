<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Factories;

use SimpleSAML\Module\conformance\GenericStatus;
use Symfony\Component\HttpFoundation\Request;

class GenericStatusFactory
{
    public function fromRequest(Request $request): GenericStatus
    {
        /** @var mixed $status */
        $status = $request->server->get(GenericStatus::KEY_STATUS) ??
            $request->query->get(GenericStatus::KEY_STATUS);
        $status = empty($status) ? null : (string)$status;

        /** @var mixed $message */
        $message = $request->server->get(GenericStatus::KEY_MESSAGE) ??
            $request->query->get(GenericStatus::KEY_MESSAGE);
        $message = empty($message) ? null : (string)$message;

        return new GenericStatus($status, $message);
    }
}
