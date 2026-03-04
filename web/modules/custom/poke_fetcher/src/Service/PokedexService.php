<?php

declare(strict_types=1);

namespace Drupal\poke_fetcher\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;

class PokedexService {
	/**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
	private $httpClient;

	/**
	* Logging service, set to 'pokedex' channel.
	*
	* @var \Psr\Log\LoggerInterface
	*/
	private $logger;

	public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory) {
		$this->httpClient = $http_client;
		$this->logger = $logger_factory->get('pokedex');
	}

	/**
   * Fetches Pokemon data from the API.
   *
   * @param string $pokemon_name
   * The name of the pokemon to fetch.
   *
   * @return array
   * The pokemon data array.
   */
  public function getPokemon(string $pokemon_name) {
		$url = 'https://pokeapi.co/api/v2/pokemon/' . strtolower($pokemon_name);

		try {
			$response = $this->httpClient->request('GET', $url);
			$data = $response->getBody()->getContents();

			return Json::decode($data);
		} catch (RequestException $e) {
			$this->logger->log(RfcLogLevel::WARNING, $e->getMessage());

			return null;
		}
  }	
}