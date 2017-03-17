# aliyun-mq

### INSTALLATION
**Install via Composer**
If you do not have Composer, you may install it by following the instructions at getcomposer.org.

You can then install this project template using the following command:
```
php composer.phar require aiershou/aliyun-mq
```


or add

```
"aiershou/aliyun-mq": "*"
```

to your `composer.json` file.

## Usage

```php
$url = "http://publictest-rest.ons.aliyun.com";
$accessKey = "...";
$secretKey = "...";
$mq = new \aiershou\aliyunmq\AliyunMQ($url, $accessKey, $secretKey);

//produce message
$mq->produce($topic, $producerId, $message);

//consume messages
$messages = $mq->consume($topic, $consumerId);
var_dump($messages);
//delete messages
foreach ($messages as $message) {
    $mq->delete($topic, $consumerId, $message->msgHandle);
}
```

## License

**aiershou/aliyun-mq** is released under the MIT License.