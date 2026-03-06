<?php

namespace Drupal\poke_fetcher\Commands;

use Drush\Commands\DrushCommands;
use Drupal\poke_fetcher\Service\PokedexService;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class PokeSyncCommands extends DrushCommands {

  protected $pokedexService;
  protected $entityTypeManager;

  public function __construct(
    PokedexService $pokedex_service, 
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->pokedexService = $pokedex_service;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Fetches Pokemon and saves them as nodes.
   *
   * @command poke:sync
   * @aliases psync
   */
  public function sync() {
    $this->output()->writeln('Starting Pokemon sync...');

    $starters = [
      'bulbasaur', 'charmander', 'squirtle', 'pikachu', 'meowth', 'psyduck', 'snorlax', 'mewtwo', 'eevee'
    ];

    $node_storage = $this->entityTypeManager->getStorage('node');
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    for ($name = 1; $name <= 50; $name++) {
      $this->output()->write("Fetching " . ucfirst($name) . "... ");
      
      $data = $this->pokedexService->getPokemon($name);

      // If $name api call fails, log it and skip the rest of the loop for that pokemon. Move on to the next one.
      if (!$data) {
        $this->output()->writeln("<error>Failed to fetch " . ucfirst($name) . "</error>");
        continue;
      }

      // Save types as taxonomy terms and get their IDs to reference in the node.
      // Since pokemon can have multiple types, this loops through them and create a term for each one if it doesn't already exist.
      $pokemon_type_ids = [];
      foreach ($data['types'] as $type_info) {
        $type_name = ucfirst($type_info['type']['name']);

        $existing_terms = 
          $term_storage->loadByProperties(['name' => $type_name, 'vid' => 'pokemon_type']);

        // If the term doesn't exist, create it. Otherwise, just get the ID of the existing term.
        if (empty($existing_terms)) {
          $term = $term_storage->create([
            'name' => $type_name,
            'vid' => 'pokemon_type'
          ]);
          $term->save();
          $pokemon_type_ids[] = $term->id();
        } else {
          $pokemon_type_ids[] = reset($existing_terms)->id();
        }
      }

      // With bucket of type IDs, create the node referencing those types.
      $existing_nodes = 
        $node_storage->loadByProperties(['title' => ucfirst($data['name']), 'type' => 'pokemon']);
      if (empty($existing_nodes)) {
        $node = $node_storage->create([
          'type' => 'pokemon',
          'title' => ucfirst($data['name']),
          'field_pokedex_id' => $data['id'],
          'field_pokemon_types' => $pokemon_type_ids,
        ]);
        $node->save();
        $this->output()->writeln("<info>Saved Node ID: " . $node->id() . "</info>");
      } else {
          $this->output()->writeln("<comment>Already exists, skipping.</comment>");
      }
    }

    $this->output()->writeln('Sync complete.');
  }
}