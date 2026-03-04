<?php

namespace Drupal\poke_fetcher\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\poke_fetcher\Service\PokedexService;
use Drupal\Core\Database\Connection;

class PokedexController extends ControllerBase {

    protected $pokedexService;
    protected $database;

    public function __construct(PokedexService $pokedex_service, Connection $database) {
        $this->pokedexService = $pokedex_service;

    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('poke_fetcher.pokedex_service'),
            $container->get('database'),
        );  
    }   

    public function content() {
        $pokemon_name = 'pikachu';
        $pokemon_data = $this->pokedexService->getPokemon($pokemon_name);

        if (!$pokemon_data) {
            return [
                '#markup' => $this ->t('Oh no! Could not fetch data for @name.', ['@name' => ucfirst($pokemon_name)]),
            ];
        }

        $query = $this->database->select('users_field_data', 'u')
            ->fields('u', ['name'])
            ->condition('u.uid', 1);
        $admin_name = $query->execute()->fetchField();

        $image_url = $pokemon_data['sprites']['front_default'] ?? '';

        $types = [];
        foreach ($pokemon_data['types'] as $type_info) {
            $types[] = $type_info['type']['name'];
        }
        return [
            '#theme' => 'pokedex_card',
            '#name' => ucfirst($pokemon_data['name']),
            '#id' => $pokemon_data['id'],
            '#image_url' => $image_url,
            '#types' => $types,
            '#admin_name' => $admin_name,
        ];
    }
}