# Reservation Package for Laravel

This package provides a comprehensive reservation management system for Laravel applications. It includes features for creating, managing, and validating reservations with agents and clients.

## Installation

You can install the package via Composer:

```bash
composer require nazemi/laraserve
```

The package will automatically register itself.

You can publish the package's assets using the following command:

```bash
php artisan vendor:publish --provider="Nazemi\Laraserve\LaraserveServiceProvider"
```

This will publish the package's configuration file, migration files, and views.

You can run the package's migrations using the following command:

```bash
php artisan migrate
```

This will create the necessary tables in your database.

Add Clientable and Agentable traits in your User models.

```php
use Nazemi\Laraserve\Traits\Clientable;
use Nazemi\Laraserve\Traits\Agentable;

class User extends Authenticatable
{
    use Clientable, Agentable;
}
```

Or you can use these traits in your own models.

```php
use Nazemi\Laraserve\Traits\Clientable;

class Patient
{
    use Clientable;
}

use Nazemi\Laraserve\Traits\Agentable;

class Doctor
{
    use Agentable;
    
}
````

## Usage

The package provides a comprehensive reservation management system for Laravel applications. It includes features for creating, managing, and validating reservations with agents and clients.

You can create reservations using the code below:

```php
use Nazemi\Laraserve\Facades\ReservationFacade;

$reservation = ReservationFacade::setAgent($agent)
    ->setClient($client)
    ->startTime('2023-10-01 10:00')
    ->endTime('2023-10-01 11:00')
    ->save();
```

This will create a new reservation with the specified agent, client, start time, and end time.

You can create multiple reservations using the code below:

```php
use Nazemi\Laraserve\Facades\ReservationFacade;

$reservations = ReservationFacade::setAgent($agent)
    ->startTime('2023-10-01 10:00')
    ->count(5)
    ->duration(30)
    ->save();
````

You can use all Eloquent methods to work with Reservation Model.

For example, you can retrieve reservations using the code below:

```php
use Nazemi\Laraserve\Models\Reservation;

$reservations = Reservation::all();
``` 

This will retrieve all reservations from the database.

You can retrieve reservations for a specific agent and client using the code below:

```php
use App\Models\User;

$agent = User::find(1);
$client = User::find(2);

$agent->agentReservations;
$client->clientReservations;
```

This will retrieve all reservations for the specified agent and client.

## Configuration

The package's configuration file is located at `config/laraserve.php`. You can modify this file to customize the package's behavior.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

