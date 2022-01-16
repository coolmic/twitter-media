# Twitter media

Just a small app allowing to only show tweets with images from a user timeline. You can also download the image by double clicking on it.

Use Symfony, turbo ux and stimulus.


## Requirement

- php >= 8
- composer
- yarn


## Installation

- Copy `.env` to `.env.local`
- Create a twitter developer account, and generate a bearer token
- Edit `.env.local`, and use the bearer token as value of `TWITTER_BEARER_TOKEN`
- Type `composer install`, then `yarn dev` (or `yarn build`)


Then you either use nginx, or `symfony serve` to start.