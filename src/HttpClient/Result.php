<?php

namespace Semalt\HttpClient;

class Result {
	private $response = '';
	private $error = '';
	private $headers;
	private $info;

	public function __construct($response, $headers, $info, $error) {
		$this->response = $response;
		$this->error = $error;
		$this->headers = $headers;
		$this->info = $info;
	}
	
	public function getRawInfo() {
		return $this->info;
	}

	public function getText() {
		return $this->response;
	}

	public function fromJson($assoc = false) {
		return json_decode($this->response, $assoc);
	}

	public function toFile($path) {
		if (file_exists($path)) {
			unlink($path);
		}
		$ok = !!file_put_contents($path, $this->response);
		return $ok;
	}

	public function getCode() {
		return $this->info['http_code'];
	}

	public function getError() {
		return isset($this->error) ? $this->error : '';
	}

	public function getAllHeaders() {
		return $this->headers;
	}

	public function getHeader(string $key) {
		$key = strtolower($key);
		foreach ($this->headers as $header) {
			if ($header['key'] === $key) return $header['val'];
		}

		return null;
	}

	public function getUrl() {
		return $this->info['url'];
	}

	public function getRedirectUrl() {
		return $this->info['redirect_url'];
	}

	public function getTime() {
		return isset($this->info['total_time']) ? $this->info['total_time'] : null;
	}

	/* aliases */

	public function text() {
		return $this->getText();
	}

	public function json($assoc = false) {
		return $this->fromJson($assoc);
	}

	public function save($path) {
		return $this->toFile($path);
	}

	public function code() {
		return $this->getCode();
	}

	public function error() {
		return $this->getError();
	}

	public function time() {
		return $this->getTime();
	}
}
