<?php

namespace SimpleSAML\Module\conformance\Helpers;

use Exception;

class StateHelper
{
	/**
	 * @throws Exception
	 */
	public function resolveSpEntityId(array $state): string
	{
		$spEntityId = $state['Destination']['entityid'] ?? null;

		if (is_null($spEntityId)) {
			// TODO mvianci Move to custom exception
			throw new Exception('No SP ID.');
		}
		return (string)$spEntityId;
	}
}