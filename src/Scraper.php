<?php

namespace Pilipinews\Website\Inquirer;

use Pilipinews\Common\Article;
use Pilipinews\Common\Client;
use Pilipinews\Common\Crawler as DomCrawler;
use Pilipinews\Common\Interfaces\ScraperInterface;
use Pilipinews\Common\Scraper as AbstractScraper;

/**
 * Inquirer News Scraper
 *
 * @package Pilipinews
 * @author  Rougin Gutib <rougingutib@gmail.com>
 */
class Scraper extends AbstractScraper implements ScraperInterface
{
    const TEXT_FOOTER = 'Subscribe to INQUIRER PLUS (https://www.inquirer.net/plus) to get access to The Philippine Daily Inquirer & other 70+ titles, share up to 5 gadgets, listen to the news, download as early as 4am & share articles on social media. Call 896 6000.';

    const VIDEO_STYLE = '#videoPlaylistPlugId ul li { color:#fff;}';

    /**
     * @var string[]
     */
    protected $refresh = array('Refresh this page for updates.');

    /**
     * @var string[]
     */
    protected $removables = array(
        '#ms-slider-wrap',
        '#mr-2018-wrap',
        'script',
        '#billboard_article',
        '.ventuno-vid',
        '#article_disclaimer',
        '.OUTBRAIN',
        '#ch-follow-us',
        '.view-comments',
        '#article_tags',
        '.adsbygoogle',
        '#article-new-featured',
        '#read-next-2018',
        '#rn-lbl',
        '#fb-root',
        '#lsmr-lbl',
        '#lsmr-box',
        '.bb_iawr',
    );

    /**
     * Returns the contents of an article.
     *
     * @param  string $link
     * @return \Pilipinews\Common\Article
     */
    public function scrape($link)
    {
        $this->prepare(mb_strtolower($link));

        $title = $this->title('.entry-title');

        $pattern = '/-(\d+)x(\d+).jpg/i';

        $this->remove((array) $this->removables);

        $body = $this->body('#article_content');

        $body = $this->caption($body);

        $body = $this->fbvideo($body);

        $body = $this->fbpost($body)->html();

        $body = preg_replace($pattern, '.jpg', $body);

        $body = $this->html(new DomCrawler($body), $this->refresh);

        $body = str_replace(self::TEXT_FOOTER, '', trim($body));

        $body = str_replace('#videoPlaylistPlugId ul li { color:#fff;}', '', $body);

        $body = str_replace(self::VIDEO_STYLE, '', $body);

        return new Article($title, trim($body), (string) $link);
    }

    /**
     * Converts caption elements to readable string.
     *
     * @param  \Pilipinews\Common\Crawler $crawler
     * @return \Pilipinews\Common\Crawler
     */
    protected function caption(DomCrawler $crawler)
    {
        $callback = function (DomCrawler $crawler)
        {
            $image = $crawler->filter('img')->first()->attr('src');

            $format = (string) '<p>PHOTO: %s - %s</p>';

            $text = $crawler->filter('.wp-caption-text')->first();

            return sprintf($format, $image, $text->html());
        };

        return $this->replace($crawler, '.wp-caption', $callback);
    }

    /**
     * Converts Facebook embedded posts to readable string.
     *
     * @param  \Pilipinews\Common\Crawler $crawler
     * @return \Pilipinews\Common\Crawler
     */
    protected function fbpost(DomCrawler $crawler)
    {
        $callback = function (DomCrawler $crawler)
        {
            $link = $crawler->attr('cite');

            $text = '<p>POST: ' . $crawler->attr('cite') . '</p>';

            $message = $crawler->filter('p > a')->first();

            return $text . '<p>' . $message->text() . '</p>';
        };

        return $this->replace($crawler, '.fb-xfbml-parse-ignore', $callback);
    }

    /**
     * Converts fbvideo elements to readable string.
     *
     * @param  \Pilipinews\Common\Crawler $crawler
     * @return \Pilipinews\Common\Crawler
     */
    protected function fbvideo(DomCrawler $crawler)
    {
        $callback = function (DomCrawler $crawler)
        {
            $link = $crawler->attr('data-href');

            return '<p>VIDEO: ' . $link . '</p>';
        };

        return $this->replace($crawler, '.fb-video', $callback);
    }

    /**
     * Initializes the crawler instance.
     *
     * @param  string $link
     * @return void
     */
    protected function prepare($link)
    {
        $response = Client::request((string) $link);

        $response = str_replace('<p>Click <a href="https://www.inquirer.net/philippine-typhoon-news">here</a> for more weather related news."</p>', '', $response);

        $response = str_replace('<p>Click <a href="https://www.inquirer.net/philippine-typhoon-news">here</a> for more weather related news.</p>', '', $response);

        $response = str_replace('<strong> </strong>', ' ', $response);

        $this->crawler = new DomCrawler($response);
    }
}
