# Drupal Pokedex Application

A custom decoupled-style Drupal application that fetches live Pokémon data via the PokeAPI, and features a local database-driven Views roster.

## Local Setup Instructions

1. **Clone the repository and enter the directory:**
  ```bash
  git clone https://github.com/noel-pena/pokedex.git
  cd pokedex
  ```
2. **Run the setup command:**
  ```bash
  ddev start && ddev composer install && ddev import-db --file=starter-db.sql.gz && ddev drush cim -y && ddev drush poke:sync
  ```
3. Load the site:
  `https://pokedex.ddev.site/`