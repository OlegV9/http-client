## Installation

```
composer require olegv9/http-client
```

## Usage

### Setup:

```php
use HttpClient\HttpClient;

$http = new HttpClient();
```

### HTTP-methods:

```php
$url = 'https://example.com';

$response = $http->get($url)->text();
$response = $http->post($url, ['some' => 'data'])->text();
$response = $http->put($url, ['some' => 'data'])->text();
$response = $http->delete($url, ['some' => 'data'])->text();
```

### Response handling:

```php
$text	= $http->get($url)->text();	//raw text
$object	= $http->get($url)->json();	//json_decode() applied
$array	= $http->get($url)->json(true);	//json_decode(true) applied
```

### Methods:

```php
$res = $http->get($url);

$res->text();	//see above
$res->json();	//see above

//status code (200, 403, etc)
$httpCode = $res->code();

//sending request error (network failure, invalid URL, etc)
$error = $res->error();

//last URL after redirect chain (CURLINFO_EFFECTIVE_URL)
$lastUrl = $res->getUrl();

//response header
$header = $res->getHeader('Content-Type');

//all response headers list
$allHeaders = $res->getAllHeaders();

//request time
$time = $res->time();
```

### Options

Опции запроса передаются в виде ассоциативного массива во 2-м параметре в GET-запросе и в 3-м - в остальных

Доступные опции:

 - **type** - тип запроса ("json", "urlencoded", "multipart"). По умолчанию "urlencoded"
 - **followRedirects** - нужно ли делать перенаправления. По умолчанию - true
 - **maxRedirects** - макс. количество перенаправлений. По умолчанию - 10
 - **ignoreSslErrors** - игнорить поломаный SSL. По умолчанию - false
 - **timeout** - таймаут в сек. По умолчанию - 40
 - **auth** - basic-авторизация в формате "login:password"
 - **headers** - ассоц. массив заголовков (см. пример ниже)
 - **curlOpts** - сырые опции для curl'а

Примеры:

```php
//авторизация + заголовки
$opts = [
    'auth' => 'admin:NgjrP4n',
    'headers' => [
        'X-Some-Header' => 'OK'
    ]
];
$http->get($url, $opts);

//Отправка JSON'а
$data = [
    'site' => 'somesite.com',
    'se' => 197,
    'prim' => true
];
$opts = [
    'timeout' => 10,
    'type' => 'json'
];
$http->post($url, $data, $opts);
```

### Multicurl

Пример:

```php
$urls = [
    'http://example.com',
    'http://example.com/page',
    'http://example.org'
];

$opts = ['timeout' => 10];
$results = $http->multiGet($urls, $opts);
//или
//$results = $http->multiRequest('POST', $urls, $postData, $opts);

foreach ($results as $res) {
    echo $res->text();	//текст ответа. Доступные также все методы, описанные выше
}
```

Массив может состоять как из URL'ов, так и из ассоциативного массива с настройками:

```php
$urls = [
    'http://example.com',
    'http://example.com/send',
    [
        'method' => 'POST',
        'url' => 'http://example.net',
        'data' => ['a' => 123],
        'opts' => ['timeout' => 20]
    ],
    'http://example.org'
];
$http->multiRequest('GET', $urls);
```

### License

[MIT](https://opensource.org/licenses/MIT)