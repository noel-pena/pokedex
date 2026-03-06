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
    $form['#attached']['library'][] = 'poke_fetcher/card_style';

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
      '#ajax' => [
        'callback' => '::ajaxUpdateCallback',
        'wrapper' => 'pokedex-result-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Throwing Pokeball...'),
        ],
      ],
    ];

    $form['result'] = [
      '#type' => 'container',
      '#prefix' => '<div id="pokedex-result-wrapper">',
      '#suffix' => '</div>',
    ];

    $pokemon_data = $form_state->get('pokemon_data');
    if ($pokemon_data) {
      $form['result']['card'] = $pokemon_data;
    }

    $form['pokedex_roster_view'] = [
      '#type' => 'view',
      '#name' => 'pokedex_roster',
      '#display_id' => 'page_1',
      '#weight' => 100,
    ];

    return $form;
  }

  public function ajaxUpdateCallback(array &$form, FormStateInterface $form_state) {
    return $form['result'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $search_term = $form_state->getValue('pokemon_name');
    $data = $this->pokedexService->getPokemon($search_term);

    if ($data) {
      $admin_name = $this->database->select('users_field_data', 'u')
        ->fields('u', ['name'])
        ->condition('u.uid', 1)
        ->execute()
        ->fetchField();

      $types = [];
      foreach ($data['types'] as $type_info) {
        $types[] = $type_info['type']['name'];
      }

      $render_array = [
        '#theme' => 'pokedex_card',
        '#name' => ucfirst($data['name']),
        '#id' => $data['id'],
        '#image_url' => $data['sprites']['front_default'] ?? '',
        '#types' => $types,
        '#admin_name' => $admin_name,
      ];

      $form_state->set('pokemon_data', $render_array);
    } else {
      $form_state->set('pokemon_data', [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Oh no! Could not find a Pokemon named "@name".', ['@name' => $search_term]),
        '#attributes' => [
          'style' => 'color: red; font-weight: bold; margin-top: 20px;',
        ],
      ]);
    }

    $form_state->setRebuild(TRUE);
  }
}