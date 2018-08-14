## Prerequisites

Before installing, check and make sure you have a `HOME` env variable set. If not, then use the below to set them in either Apache or Nginx.

#### Apache:

Open up your http.conf and add this to the bottom.
```
SetEnv HOME "/Users/{your-user-name}/"
```

#### Nginx:

Open up your nginx.conf and add this in the http section.
```
fastcgi_param HOME '/Users/{your-user-name}/';
```

## Installation

First cd into the .composer directory in your home directory.
```
cd ~/.composer
```
Run this command to add the repository to your config.
```
composer config repositories.bigfishdesign/sushi-composer-plugin vcs https://github.com/bigfishdesign/sushi-composer-plugin
```
Then run this command to require the package globally.
```
composer global require bigfishdesign/sushi-composer-plugin:dev-master
```
