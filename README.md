# YaEP
Yet another Email Parser

There might be other email parsers for PHP around but I have taken the challenge to write a simple email parser that gives access to the different content types and extracts attachements.

## Usage
The usage is straight forward.
```php
$emailParser = new EmailParser(file_get_contents('test.eml'));
```
### E.g. get the plain text part
```php
$emailParser->getBody('text/plain');
```

### Get the attachements
```php
$attachements = $emailParser->getAttachements();
```

Store them somewhere if you like
```php
foreach ($attachements as $a) {
	echo 'Saving attachement ' . $a->getFilename() . ' (' . $a->getContentType() . ')';
	$a->storeTo('/tmp/');
}
```
