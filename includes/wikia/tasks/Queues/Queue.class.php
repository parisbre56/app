<?php
/**
 * BaseQueue
 *
 * <insert description here>
 *
 * @author Nelson Monterroso <nelson@wikia-inc.com>
 */

namespace Wikia\Tasks\Queues;


class Queue {
	const NAME = 'Queue';

	protected $name;
	protected $routingKey;

	public function __construct() {
		$this->name = 'mediawiki';
		$this->routingKey = 'mediawiki';
	}

	public function name() {
		return $this->name;
	}

	public function routingKey() {
		return $this->routingKey;
	}
}