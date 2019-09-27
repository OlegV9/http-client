<?php

namespace HttpClient;

class HttpClient {
	private $defaultOpts = [
		'type' => 'urlencoded',
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

	public function multiRequest($method, $requests, $data = [], $opts = []) {
		$opts = array_merge($this->defaultOpts, $opts);

		$multi = curl_multi_init();

		$headersMap = [];
		$channels = [];

		foreach ($requests as $index => $req) {
			if (is_array($req)) {
				$url = $req['url'];
				if (!empty($req['opts'])) {
					foreach ($req['opts'] as $key => $val) {
						$opts[$key] = $val;
					}
				}
			} else {
				$url = $req;
			}

			$headersMap[$index] = [];

			$ch = curl_init($url);
			$curlOpts = $this->getCurlOpts($method, $data, $opts, function($ch, $header) use (&$headersMap, $index) {
				return $this->readHeaders($ch, $header, $headersMap[$index]);
			});
			curl_setopt_array($ch, $curlOpts);
			curl_multi_add_handle($multi, $ch);
			$channels[$index] = $ch;
		}

		$active = null;
		do {
			$mrc = curl_multi_exec($multi, $active);
		} while ($mrc == CURLM_CALL_MULTI_PERFORM);

		while ($active && $mrc == CURLM_OK) {
			if (curl_multi_select($multi) == -1) continue;
			do {
				$mrc = curl_multi_exec($multi, $active);
			} while ($mrc == CURLM_CALL_MULTI_PERFORM);
		}

		$results = [];
		foreach ($channels as $index => $ch) {
			$response = curl_multi_getcontent($ch);
			curl_multi_remove_handle($multi, $ch);
			$error = curl_error($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);

			$result = new HttpClientResult($response, $headersMap[$index], $info, $error);
			$results[$index] = $result;
		}

		curl_multi_close($multi);

		return $results;
	}

	public function multiGet($requests, $opts = []) {
		return $this->multiRequest('GET', $requests, null, $opts);
	}

	private function readHeaders($ch, $header, &$headers) {
		$len = strlen($header);
		$parts = explode(':', $header, 2);

		if (trim($header) === '') return $len;

		if (count($parts) < 2) {
			$headers = [];
			return $len;
		}

		list($key, $val) = $parts;

		$headers[] = [
			'key' => mb_strtolower(trim($key)),
			'val' => trim($val)
		];

		return $len;
	}

	public function makeRequest($method, $url, $postData = null, $opts = []) {
		$opts = array_merge($this->defaultOpts, $opts);

		$headers = [];
		$readHeaders = function($ch, $header) use (&$headers) {
			return $this->readHeaders($ch, $header, $headers);
		};

		$reqHeaders = [];

		$ch = curl_init($url);

		$curlOpts = $this->getCurlOpts($method, $postData, $opts, $readHeaders);
		curl_setopt_array($ch, $curlOpts);

		$response = curl_exec($ch);
		$error = curl_error($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		return new HttpClientResult($response, $headers, $info, $error);
	}

	private function getCurlOpts($method, $postData, $opts, callable $readHeaders = null) {
		$curlOpts = [];

		$curlOpts[CURLOPT_CUSTOMREQUEST] = $method;
		$curlOpts[CURLOPT_RETURNTRANSFER] = true;
		if ($readHeaders) {
			$curlOpts[CURLOPT_HEADERFUNCTION] = $readHeaders;
		}

		if ($postData) {
			$postData = $this->transformPostData($postData, $opts['type']);
			$curlOpts[CURLOPT_POSTFIELDS] = $postData;

			if ($opts['type'] === 'json') {
				$reqHeaders['content-type'] = 'application/json';
			}
		}

		if (!empty($opts['ignoreSslErrors'])) {
			$curlOpts[CURLOPT_SSL_VERIFYHOST] = 0;
			$curlOpts[CURLOPT_SSL_VERIFYPEER] = 0;
		}
		if (isset($opts['maxRedirects'])) {
			$curlOpts[CURLOPT_MAXREDIRS] = intval($opts['maxRedirects']);
		}
		if (!empty($opts['followRedirects'])) {
			$curlOpts[CURLOPT_FOLLOWLOCATION] = true;
		}
		if (isset($opts['timeout'])) {
			$curlOpts[CURLOPT_TIMEOUT] = $opts['timeout'];
		}
		if (isset($opts['auth'])) {
			$curlOpts[CURLOPT_USERPWD] = $opts['auth'];
		}
		if (!empty($opts['headers'])) {
			foreach ($opts['headers'] as $key => $val) {
				$header = mb_strtolower($key);
				$reqHeaders[$header] = $val;
			}
		}

		if (!empty($reqHeaders)) {
			$headersList = [];
			foreach ($reqHeaders as $key => $val) {
				$headersList[] = $key.': '.$val;
			}
			$curlOpts[CURLOPT_HTTPHEADER] = $headersList;
		}

		if (!empty($opts['curlOpts'])) {
			foreach ($opts['curlOpts'] as $key => $val) {
				$curlOpts[$key] = $val;
			}
		}

		return $curlOpts;
	}

	private function transformPostData($data, $type) {
		switch ($type) {
			case 'json':
				return json_encode($data, JSON_UNESCAPED_UNICODE);

			case 'form':
			case 'urlencoded':
				return http_build_query($data);

			case 'multipart':
				return $data;
		}
		return $data;
	}
}
