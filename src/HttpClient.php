<?php

namespace HttpClient;

class HttpClient {
	private $defaultOpts = [
		'type' => 'form',
		'followRedirects' => true,
		'maxRedirects' => 10,
		'ignoreSslErrors' => false,
		'timeout' => 40
	];

	public function __construct($defOpts = null) {
		if (is_array($defOpts)) {
			$this->defaultOpts = array_merge($this->defaultOpts, $defOpts);
		}
	}

	public function get($url, $opts = []) {
		return $this->makeRequest('GET', $url, null, $opts);
	}

	public function post($url, $data = [], $opts = []) {
		return $this->makeRequest('POST', $url, $data, $opts);
	}

	public function put($url, $data = [], $opts = []) {
		return $this->makeRequest('PUT', $url, $data, $opts);
	}

	public function delete($url, $data = [], $opts = []) {
		return $this->makeRequest('DELETE', $url, $data, $opts);
	}

	public function set($key, $val) {
		$this->defaultOpts[$key] = $val;
	}

	private function makeRequest($method, $url, $postData = null, $opts = []) {
		$opts = array_merge($this->defaultOpts, $opts);
		$headers = [];

		$readHeaders = function($ch, $header) use (&$headers) {
			$len = strlen($header);
			$parts = explode(':', $header, 2);
			if (count($parts) < 2) return $len;

			list($key, $val) = $parts;

			$headers[] = [
				'key' => mb_strtolower(trim($key)),
				'val' => trim($val)
			];

			return $len;
		};

		$reqHeaders = [];

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, $readHeaders);

		if ($postData) {
			$postData = $this->transformPostData($postData, $opts['type']);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

			if ($opts['type'] === 'json') {
				$reqHeaders['content-type'] = 'application/json';
			}
		}

		if (!empty($opts['ignoreSslErrors'])) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		if (isset($opts['maxRedirects'])) {
			curl_setopt($ch, CURLOPT_MAXREDIRS, intval($opts['maxRedirects']));
		}
		if (!empty($opts['followRedirects'])) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		}
		if (isset($opts['timeout'])) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $opts['timeout']);
		}
		if (isset($opts['auth'])) {
			curl_setopt($ch, CURLOPT_USERPWD, $opts['auth']);
		}
		if (!empty($opts['headers'])) {
			foreach ($opts['headers'] as $key => $val) {
				$header = strtolower($key);
				$reqHeaders[$header] = $val;
			}
		}

		if (!empty($reqHeaders)) {
			$headersList = [];
			foreach ($reqHeaders as $key => $val) {
				$headersList[] = $key.': '.$val;
			}
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headersList);
		}

		if (!empty($opts['curlOpts'])) {
			curl_setopt_array($ch, $opts['curlOpts']);
		}

		$response = curl_exec($ch);
		$error = curl_error($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		return new HttpClientResult($response, $headers, $info, $error);
	}

	private function transformPostData($data, $type) {
		if ($type === 'json') return json_encode($data, JSON_UNESCAPED_UNICODE);
		if ($type === 'form') return http_build_query($data);
		return $data;
	}
}
