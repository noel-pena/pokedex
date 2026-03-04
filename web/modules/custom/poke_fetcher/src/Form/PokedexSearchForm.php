<?php

namespace Drupal\poke_fetcher\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\poke_fetcher\Service\PokedexService;
use Drupal\Core\Database\Connection;

class PokedexSearchForm extends FormBase {

  protected $pokedexService;
  protected $database;

  public function __construct(PokedexService $pokedex_service, Connection $database) {
      $this->pokedexService = $pokedex_service;
      $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
      return new static(
          $container->get('poke_fetcher.pokedex_service'),
          $container->get('database'),
      );  
  }   

  public function getFormId() {
      return 'pokedex_search_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['pokemon_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pokemon Name'),
      '#description' => $this->t('Enter the name of the Pokemon you want to search for.'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#button_type' => 'primary',
    ];

    // Display results if available
    $pokedex_data = $form_state->get('pokedex_data');
    if ($pokedex_data) {
      $form['result'] = $pokedex_data;
    }

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $searchTerm = $form_state->getValue('pokemon_name');
    $pokedex_data = $this->pokedexService->getPokemon($searchTerm);

    if ($pokedex_data) {
      $adminName = $this->database->select('users_field_data', 'u')
        ->fields('u', ['name'])
        ->condition('u.uid', 1)
        ->execute()
        ->fetchField();

      $types = [];
      foreach ($pokedex_data['types'] as $type_info) {
        $types[] = $type_info['type']['name'];
      }

      $renderArray = [
        '#theme' => 'pokedex_card',
        '#name' => ucfirst($pokedex_data['name']),
        '#id' => $pokedex_data['id'],
        '#image_url' => $pokedex_data['sprites']['front_default'],
        '#types' => $types,
        '#admin_name' => $adminName,
      ];

      $form_state->set('pokedex_data', $renderArray);
    } else {
      \Drupal::messenger()->addError($this->t('No data found for "@name". Please try another Pokemon.', ['@name' => $searchTerm]));
    }

    // Rebuild the form
    $form_state->setRebuild(TRUE);
  }
}