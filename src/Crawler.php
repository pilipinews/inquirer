<?php

namespace Pilipinews\Website\Inquirer;

use Pilipinews\Common\Client;
use Pilipinews\Common\Crawler as DomCrawler;
use Pilipinews\Common\Interfaces\CrawlerInterface;

/**
 * Inquirer News Crawler
 *
 * @package Pilipinews
 * @author  Rougin Royce Gutib <rougingutib@gmail.com>
 */
class Crawler implements CrawlerInterface
{
    /**
     * @var string[]
     */
    protected $allowed = array('Headlines', 'Regions', 'Nation');

    /**
     * Returns an array of articles to scrape.
     *
     * @return string[]
     */
    public function crawl()
    {
        $link = 'https://newsinfo.inquirer.net/category/latest-stories';

        $response = Client::request($link);

        $allowed = (array) $this->allowed;

        $callback = function (DomCrawler $node) use ($allowed) {
            $category = $node->filter('#ch-cat')->first();

            if (! $category->count())
            {
                return null;
            }

            $allowed = in_array($category->text(), $allowed);

            $link = $node->filter('a')->first();

            return $allowed ? $link->attr('href') : null;
        };

        $crawler = new DomCrawler((string) $response);

        $news = $crawler->filter('#inq-channel-left > #ch-ls-box');

        return array_values(array_filter($news->each($callback)));
    }
}
