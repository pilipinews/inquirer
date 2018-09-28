<?php

namespace Pilipinews\Website\Inquirer;

use Pilipinews\Common\Article;
use Pilipinews\Common\Crawler as DomCrawler;
use Pilipinews\Common\Interfaces\ScraperInterface;
use Pilipinews\Common\Scraper as AbstractScraper;

/**
 * Inquirer News Scraper
 *
 * @package Pilipinews
 * @author  Rougin Royce Gutib <rougingutib@gmail.com>
 */
class Scraper extends AbstractScraper implements ScraperInterface
{
    const TEXT_FOOTER = 'Subscribe to INQUIRER PLUS (http://www.inquirer.net/plus) to get access to The Philippine Daily Inquirer & other 70+ titles, share up to 5 gadgets, listen to the news, download as early as 4am & share articles on social media. Call 896 6000.';

    /**
     * @var string[]
     */
    protected $refresh = array('Refresh this page for updates.');

    /**
     * @var string[]
     */
    protected $removables = array(
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
    );

    /**
     * Returns the contents of an article.
     *
     * @param  string $link
     * @return \Pilipinews\Common\Article
     */
    public function scrape($link)
    {
        $this->prepare((string) mb_strtolower($link));

        $title = $this->title('.entry-title');

        $pattern = '/-(\d+)x(\d+).jpg/i';

        $this->remove((array) $this->removables);

        $body = $this->body('#article_content');

        $body = $this->caption($body);

        $body = $this->fbvideo($body);

        $body = $this->video($body)->html();

        $body = preg_replace($pattern, '.jpg', $body);

        $body = $this->html(new DomCrawler($body), $this->refresh);

        $body = str_replace(self::TEXT_FOOTER, '', trim($body));

        return new Article($title, (string) trim($body));
    }

    /**
     * Converts caption elements to readable string.
     *
     * @param  \Pilipinews\Common\Crawler $crawler
     * @return \Pilipinews\Common\Crawler
     */
    protected function caption(DomCrawler $crawler)
    {
        $callback = function (DomCrawler $crawler) {
            $image = $crawler->filter('img')->first()->attr('src');

            $format = (string) '<p>PHOTO: %s</p><p>%s</p>';

            $text = $crawler->filter('.wp-caption-text')->first();

            return sprintf($format, $image, $text->html());
        };

        return $this->replace($crawler, '.wp-caption', $callback);
    }

    /**
     * Converts fbvideo elements to readable string.
     *
     * @param  \Pilipinews\Common\Crawler $crawler
     * @return \Pilipinews\Common\Crawler
     */
    protected function fbvideo(DomCrawler $crawler)
    {
        $callback = function (DomCrawler $crawler) {
            $link = $crawler->attr('data-href');

            return '<p>VIDEO: ' . $link . '</p>';
        };

        return $this->replace($crawler, '.fb-video', $callback);
    }

    /**
     * Converts video elements to readable string.
     *
     * @param  \Pilipinews\Common\Crawler $crawler
     * @return \Pilipinews\Common\Crawler
     */
    protected function video(DomCrawler $crawler)
    {
        $callback = function (DomCrawler $crawler) {
            $link = (string) $crawler->attr('src');

            return '<p>VIDEO: ' . $link . '</p>';
        };

        $crawler = $this->replace($crawler, 'p > iframe', $callback);

        $callback = function (DomCrawler $crawler) {
            $text = '<p>VIDEO: ' . $crawler->attr('cite') . '</p>';

            $message = $crawler->filter('p > a')->first();

            return $text . '<p>' . $message->text() . '</p>';
        };

        return $this->replace($crawler, '.fb-xfbml-parse-ignore', $callback);
    }
}
