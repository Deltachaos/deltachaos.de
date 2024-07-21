<?php

namespace App;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FetchDiscogsCollectionCommand extends Command
{
    private readonly HttpClientInterface $client;

    public function __construct()
    {
        $this->client = HttpClient::create();
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure() : void
    {
        $this->setName('fetch-discogs');
    }

    protected function fetchCollection() : array
    {
        $url = 'https://api.discogs.com/users/deltachaos/collection/folders/0/releases?token='.getenv('DISCOGS_TOKEN');
        $result = [];

        do {
            $releases = $this->client->request('GET', $url)->toArray();

            if (isset($releases['releases'])) {
                $result = array_merge(
                    $result,
                    $releases['releases']
                );
            }

            if (!isset($releases['pagination']) ||
                !isset($releases['pagination']['urls']) ||
                empty($releases['pagination']['urls']['next'])) {
                break;
            }

            $url = $releases['pagination']['urls']['next'];
        } while(true);

        usort($result, function ($a, $b) {
            $aDate = \DateTime::createFromFormat(\DateTime::W3C, $a['date_added']);
            $bDate = \DateTime::createFromFormat(\DateTime::W3C, $b['date_added']);

            if ($aDate->getTimestamp() == $bDate->getTimestamp()) {
                return 0;
            }

            return $aDate < $bDate ? 1 : -1;
        });

        $clean = [];

        foreach ($result as $item) {
            $artistName = '';

            foreach ($item['basic_information']['artists'] as $artist) {
                $artistName .= trim(preg_replace('/\([0-9]+\)$/', '', $artist['name'])) . ', ';
            }

            $artistName = rtrim($artistName, ', ');
            $hash = md5($artistName . $item['basic_information']['title'] . $item['basic_information']['year']);

            $url = $item['basic_information']['cover_image'];
            $prefix  = __DIR__ . '/..';
            $fileUrl = '/assets/data/discogs/' . $item['basic_information']['master_id'] . '.jpg';

            try {
                file_put_contents($prefix . $fileUrl, $this->client->request('GET', $url)->getContent());
                $url = $fileUrl;
            } catch (\Exception $e) {
                echo "Failed to download " . $url . ": ".$e->getMessage()."\n";
            }

            $clean[$item['basic_information']['master_id']] = [
                'artist' => $artistName,
                'title' => $item['basic_information']['title'],
                'year' => $item['basic_information']['year'],
                'date_added' => $item['date_added'],
                'cover_image' => $url,
                'master_id' => $item['basic_information']['master_id'],
                'id' => $item['id']
            ];
        }

        return $clean;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $output->write('Fetch collection... ');
        $collection = $this->fetchCollection();
        $output->writeln(' [DONE]');

        $output->writeln(sprintf('Found %d items.', count($collection)));

        $output->write('Write to data... ');
        file_put_contents(
            __DIR__ . '/../_data/collection.yaml',
            Yaml::dump($collection, 3)
        );
        $output->writeln(' [DONE]');

        return 0;
    }
}
