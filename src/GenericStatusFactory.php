<?php

namespace SimpleSAML\Module\conformance;

use Symfony\Component\HttpFoundation\Request;

class GenericStatusFactory
{
    public function fromRequest(Request $request): GenericStatus
    {
        return new GenericStatus(
            $request->server->get(GenericStatus::KEY_STATUS) ??
                $request->query->get(GenericStatus::KEY_STATUS),
            $request->server->get(GenericStatus::KEY_MESSAGE) ??
                $request->query->get(GenericStatus::KEY_MESSAGE)
        );
    }
}
