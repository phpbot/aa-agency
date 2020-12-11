<?php


namespace PhpBot\Aa\Agency;

use Carbon\Carbon;
use GuzzleHttp;
use PhpBot\Aa\Agency\Exceptions\AuthenticationException;
use PhpBot\Aa\Agency\Exceptions\NoDataFoundException;

class Crawler
{

    const API_BASE_URL = 'https://api.aa.com.tr';

    protected $user_name = '';

    protected $password = '';

    protected $summary_length = 150;

    protected $media_format = 'web';

    protected $attributes = [
        'filter_type' => '1',
        'filter_language' => '1',
        'filter_category' => '',
        'limit' => '5',
    ];


    protected $auth = ['', ''];


    public function __construct($config)
    {
        $this->setParameters($config);
    }
    public function search($attributes = [])
    {
        $this->setAttributes($attributes);
        $res = $this->fetchUrl(self::API_BASE_URL . '/abone/search', 'POST', [
            'auth' => $this->auth,
            'form_params' => $this->attributes
        ]);

        $search = json_decode($res);

        switch ($search->response->code) {
            case 200:
                break;
            case 401:
                throw new AuthenticationException;
            default:
                throw new NoDataFoundException;
        }
        return $search;
    }

    public function getNewsDetail($id)
    {
        $newsml = simplexml_load_string($this->document($id, 'newsml29'));
        usleep(500000);
        $news = $this->newsmlToNews($newsml);
        $result = $news;
        usleep(500000);
        return json_encode($result);
    }


    protected function newsmlToNews($xml)
    {
        $news = new \stdClass();
        $xml->registerXPathNamespace("n", "http://iptc.org/std/nar/2006-10-01/");
        $news->code = (string)($xml->itemSet->newsItem['guid']);
        $news->title = (string)$xml->itemSet->newsItem->contentMeta->headline;
        $news->summary = (string)trim($xml->itemSet->newsItem->contentSet->inlineXML->nitf->body->{'body.head'}->abstract);
        if (empty(trim($news->summary))) {
            $news->summary = (string)$this->createSummary($xml->itemSet->newsItem->contentSet->inlineXML->nitf->body->{'body.content'});
        }
        $text = '';
        if (strpos($xml->itemSet->newsItem->contentSet->inlineXML->nitf->body->{'body.content'}, '(AA)') > 0) {
            $split = explode('(AA)', $xml->itemSet->newsItem->contentSet->inlineXML->nitf->body->{'body.content'});
            if (count($split) > 1) {
                $text = $split[1];
                $text = trim($text, ' \t\n\r\0\x0B-');
            }
        }
        $news->content = (string)$text;
        $news->created_at = (new Carbon($xml->itemSet->newsItem->itemMeta->versionCreated))
            ->addHours(3)->format('d.m.Y H:i:s');
        $news->category = (string)$xml->xpath('//n:subject/n:name[@xml:lang="tr"]')[0];
        $news->city = '';
        if (isset($xml->xpath('//n:contentMeta/n:located[@type="cptype:city"]/n:name[@xml:lang="tr"]')[0])) {
            $news->city = (string)$xml
                ->xpath('//n:contentMeta/n:located[@type="cptype:city"]/n:name[@xml:lang="tr"]')[0];
        }
        $news->images = [];
        $news->videos = [];
        $news->texts = [];
        $medias = $xml->xpath('//n:newsItem/n:itemMeta/n:link');
        foreach ($medias as $row) {
            $qcode = (string)$row->itemClass['qcode'];
            if ($qcode == 'ninat:picture') {
                $image = (string)$row['residref'];
                $qcode = (string)$row->itemClass['qcode'];
                $news->images[] = ["id" => $image, "qcode" => $qcode, "link" => $this->getDocumentLink($image, $this->media_format)];
            }
            if ($qcode == 'ninat:video') {
                $video = (string)$row['residref'];
                $qcode = (string)$row->itemClass['qcode'];
                $news->videos[] = ["id" => $video, "qcode" => $qcode, "link" => $this->getDocumentLink($video, $this->media_format)];
            }
            if ($qcode == 'ninat:text') {
                $text = (string)$row['residref'];
                $qcode = (string)$row->itemClass['qcode'];
                $news->texts[] = ["id" => $text, "qcode" => $qcode, "link" => $this->getDocumentLink($text, 'newsml29')];
            }
        }
        return $news;
    }


    protected function getDocumentLink($id, $format)
    {
        return self::API_BASE_URL . '/abone/document/' . $id . '/' . $format;
    }


    public function document($id, $format)
    {
        $url = self::API_BASE_URL . '/abone/document/' . $id . '/' . $format;
        $data = $this->fetchUrl($url, 'GET', ['auth' => $this->auth]);
        return $data;
    }

    public function saveNews($id, $saveLocation, $format = 'web')
    {
        $data = $this->document($id, $format);
        file_put_contents($saveLocation, $data);
        return $saveLocation;
    }

    protected function createSummary($text)
    {
        if (strpos($text, '(AA)') > 0) {
            $split = explode('(AA)', $text);
            if (count($split) > 1) {
                $text = $split[1];
                $text = trim($text, ' \t\n\r\0\x0B-');
            }
        }
        $summary = (string)$this->shortenString(strip_tags($text), $this->summary_length);

        return $summary;
    }


    protected function setParameters($config)
    {
        if (!is_array($config)) {
            throw new \InvalidArgumentException('$config variable must be an array.');
        }
        if (array_key_exists('user_name', $config)) {
            $this->user_name = $config['user_name'];
        }
        if (array_key_exists('password', $config)) {
            $this->password = $config['password'];
        }
        if (array_key_exists('media_format', $config)) {
            $this->media_format = $config['media_format'];
        }
        if (array_key_exists('summary_length', $config)) {
            $this->summary_length = $config['summary_length'];
        }
        $this->auth = [$this->user_name, $this->password];
    }


    protected function setAttributes($attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }


    protected function fetchUrl($url, $method = 'GET', $options = [])
    {
        $client = new GuzzleHttp\Client();
        $res = $client->request($method, $url, $options);
        if ($res->getStatusCode() == 200) {
            return (string)$res->getBody();
        }
        return '';
    }


    protected function shortenString($str, $len)
    {
        if (strlen($str) > $len) {
            $str = rtrim(mb_substr($str, 0, $len, 'UTF-8'));
            $str = substr($str, 0, strrpos($str, ' '));
            $str .= '...';
            $str = str_replace(',...', '...', $str);
        }
        return $str;
    }

    protected function titleCase($str)
    {
        $str = mb_convert_case($str, MB_CASE_TITLE, 'UTF-8');
        return $str;
    }
}
