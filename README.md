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
        "semalt/http-client": "dev-master#v1.3"
    }
}

```

и потом сделать `composer update`
