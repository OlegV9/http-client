## Установка

Чтобы подключить либу в свой проект, нужно прописать в composer.json

```json
{
    "repositories": [
        {
            "type": "git",
            "url": "git@git.semalt.com:libs/HttpClient.git"
        }
    ],
    "require": {
        "semalt/http-client": "2.2.0"
    }
}

```

и потом сделать `composer update semalt/http-client`

## Использование

### Подключение:

```php
use Semalt\HttpClient;

$http = new HttpClient();
```

### HTTP-методы:

```php
$url = 'https://example.com';

$response = $http->get($url)->text();
$response = $http->post($url, ['some' => 'data'])->text();
$response = $http->put($url, ['some' => 'data'])->text();
$response = $http->delete($url, ['some' => 'data'])->text();
```

### Обработка ответа:

```php
$text	= $http->get($url)->text();	//сырой текст
$object	= $http->get($url)->json();	//применяется json_decode()
$array	= $http->get($url)->json(true);	//применяется json_decode(true)
```

### Методы:

```php
$res = $http->get($url);

$res->text();	//см.выше
$res->json();	//см.выше

//статус код (200, 403 и т.д.)
$httpCode = $res->code();

//ошибка если не удалось послать запрос (нет инета, невалидный адрес и т.д.)
$error = $res->error();

//последний URL после редиректов (CURLINFO_EFFECTIVE_URL)
$lastUrl = $res->getUrl();

//Заголовок ответа
$header = $res->getHeader('Content-Type');

//Массив всех заголовков ответа
$allHeaders = $res->getAllHeaders();

//время запроса
$time = $res->time();
```

### Опции

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
