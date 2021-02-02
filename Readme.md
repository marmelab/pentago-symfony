# pentago-symfony

[Pentago](https://en.wikipedia.org/wiki/Pentago) game implementation in **Symfony 5**.


Definition and rules from Wikipedia :

> Pentago is a two-player abstract strategy game invented by Tomas Flodén. The Swedish company Mindtwister has the rights of developing and commercializing the product. The game is played on a 6×6 board divided into four 3×3 sub-boards (or quadrants). Taking turns, the two players place a marble of their color (either black or white) onto an unoccupied space on the board, and then rotate one of the sub-boards by 90 degrees either clockwise or anti-clockwise. A player wins by getting five of their marbles in a vertical, horizontal or diagonal row (either before or after the sub-board rotation in their move). If all 36 spaces on the board are occupied without a row of five being formed then the game is a draw."


## Prerequisite

- [Docker](https://www.docker.com/) & [docker-compose](https://docs.docker.com/compose/) are installed on your machine.

## Installation

- Clone this repo
- Run `make install` to install dependencies. It will also create a `.env.local` file.
- Fill `.env.local` file with your credentials.
- Run `make start` to start the server
- Run `make create-db` to create & initialize the database
- Run `make migrate` to run migrations
- Play on [localhost:8080](http://localhost:8080)
  
> Type `make help` to list all commands available

## Database persistance

We are using a postgres image to run locally our database.
You can access to Adminer on [localhost:8081](http://localhost:8081) and entering credentials from the `.env.local` file.

> Do not using .env to store your personal credentials because it's git tracked.

### Migrations

- `make create-migration` for creating a doctrine migration if entities has been changed.
- `make migrate` to run pending migrations.
