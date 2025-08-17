<div align="center">

<img src="art/logo.png" alt="Laraserve Logo" width="200">

# Reservation Package for Laravel

This package provides a comprehensive reservation management system for Laravel applications. It includes features for creating, managing, and validating reservations with providers and recipients.

</div>

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

Add Recipient and Provider traits in your User models.

```php
use Nazemi\Laraserve\Traits\IsRecipient;
use Nazemi\Laraserve\Traits\IsProvider;

class User extends Authenticatable
{
    use IsRecipient, IsProvider;
}
```

Or you can use these traits in your own models.

```php
use Nazemi\Laraserve\Traits\IsRecipient;

class Patient
{
    use IsRecipient;
}

use Nazemi\Laraserve\Traits\IsProvider;

class Doctor
{
    use IsProvider;
    
}
````

## Usage

The package provides a comprehensive reservation management system for Laravel applications. It includes features for creating, managing, and validating reservations with providers and recipients.

You can create reservations using the code below:

```php
use Nazemi\Laraserve\Facades\Laraserve;

$reservation = Laraserve::setProvider($provider)
    ->setRecipient($recipient)
    ->startTime('2023-10-01 10:00')
    ->endTime('2023-10-01 11:00')
    ->save();
```

This will create a new reservation with the specified provider, recipient, start time, and end time.

You can create multiple reservations using the code below:

```php
use Nazemi\Laraserve\Facades\Laraserve;

$reservations = Laraserve::setProvider($provider)
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

You can retrieve reservations for a specific provider and recipient using the code below:

```php
use App\Models\User;

$provider = User::find(1);
$recipient = User::find(2);

$provider->providedReservations;
$recipient->receivedReservations;
```

This will retrieve all reservations for the specified provider and recipient.

## Configuration

The package's configuration file is located at `config/laraserve.php`. You can modify this file to customize the package's behavior.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

