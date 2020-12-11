# Phpbot for AA News Agency in Turkey.

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

[EN] This package is created for crawling news from Anadolu Ajansi. You have to be subscribed to AA and obtain user credentials for being able to use this package.

[TR] Bu paket AA abonelerinin kullanıcı bilgileriyle haberleri taramaları için oluşturulmuştur. Aşağıdaki şekilde kullandığınızda son eklenen haberlerden istediğiniz adette haberi dizi olarak alabilirsiniz. Paketi kullanmak için AA abonesi olmalı ve kullanıcı bilgilerine sahip olmalısınız.





## Install

Via Composer

``` bash
$ composer require phpbot/aa-agency
```

## Usage

``` php
use \PhpBot\Aa\Agency\Crawler;
$crawler = new Crawler([
    'user_name' => 'your-username',
    'password' => 'your-password',
    'media_format' => 'web',
    'summary_length' => 150,
]);

$search = $crawler->search([
	'filter_type' => '1',
    'filter_language' => '1',
    'filter_category' => '',
    'limit' => '20',
]);
```
Calling `$crawler->getNewsList()` will return an array like this:

```php
foreach($search->data->result as $row){
    $news_detail = $crawler->getNewsDetail($row->id);
    $news_detail = json_decode($news_detail);
    print $news_detail->code.'<br>';
    print $news_detail->title.'<br>';
    print $news_detail->summary.'<br>';
    print $news_detail->content.'<br>';
    print $news_detail->created_at.'<br>';
    print $news_detail->category.'<br>';
    print $news_detail->city.'<br>';
    print_r($news_detail->images);//$news_detail->images array
    print_r($news_detail->videos);//$news_detail->videos array
    print_r($news_detail->texts);//$news_detail->texts array
}
```
## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email info@phpbot.net instead of using the issue tracker.

## Credits

- [Kod Arşivi][link-kodarsivi]
- [Php Bot][link-phpbot]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/phpbot/aa-agency.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/phpbot/aa-agency.svg?style=flat-square
[link-packagist]: https://packagist.org/packages/phpbot/aa-agency
[link-downloads]: https://packagist.org/packages/phpbot/aa-agency
[link-kodarsivi]: https://github.com/kodarsivi
[link-phpbot]: https://github.com/phpbot
[link-contributors]: ../../contributors
