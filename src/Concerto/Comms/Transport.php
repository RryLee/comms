<?php

	namespace Concerto\Comms;

	class Transport {
		/**
		 * Data being sent.
		 */
		protected $data;

		/**
		 * Process that transport was sent from.
		 */
		protected $pid;

		/**
		 * Time that transport was created.
		 */
		protected $time;

		static public function pack($data) {
			$transport = new static($data);

			return serialize($transport);
		}

		static public function unpack($data) {
			$transport = unserialize($data);

			return $transport;
		}

		public function __construct($data) {
			$this->data = $data;
			$this->pid = getmypid();
			$this->time = microtime(true);
		}

		public function getData() {
			return $this->data;
		}

		public function getPid() {
			return $this->pid;
		}

		public function getTime() {
			return $this->time;
		}
	}