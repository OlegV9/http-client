<?php

namespace HttpClient;

class HttpClientResult {
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

	public function getText() : string {
		return $this->response;
	}

	public function fromJson($assoc = false) {
		return json_decode($this->response, $assoc);
	}

	public function toFile($path) : bool {
		if (file_exists($path)) {
			unlink($path);
		}
		$ok = !!file_put_contents($path, $this->response);
		return $ok;
	}

	public function getCode() : int {
		return $this->info['http_code'] ?? 0;
	}

	public function getError() : string {
		return $this->error ?? '';
	}

	public function getAllHeaders() : array {
		return $this->headers;
	}

	public function getHeader(string $key) : ?string {
		$key = mb_strtolower($key);
		foreach ($this->headers as $header) {
			if ($header['key'] === $key) return $header['val'];
		}

		return null;
	}

	public function getUrl() : ?string {
		return $this->info['url'];
	}

	public function getRedirectUrl() : ?string {
		return $this->info['redirect_url'];
	}

	public function getTime() : float {
		return $this->info['total_time'] ?? null;
	}

	/* aliases */

	public function text() : string {
		return $this->getText();
	}

	public function json($assoc = false) {
		return $this->fromJson($assoc);
	}

	public function save($path) : bool {
		return $this->toFile($path);
	}

	public function code() : int {
		return $this->getCode();
	}

	public function error() : string {
		return $this->getError();
	}

	public function time() : float {
		return $this->getTime();
	}
}
