# Software Engineer Test Task
## Requirements
Test Assignment: API Implementation

Developing an API with the following set of functionalities:
* User registration and authentication based on tokens.
* A method for sending funds to another user within the system.
* A method for sending funds to multiple system users.
* A method for retrieving a list of financial transactions for a user's account.
  Optional:
* The system should include a history of users' balance changes.
* Scheduled automatic debiting of funds from a user's account (subscription fees).
* It is crucial to consider race condition cases where transactions may be executed "simultaneously".

## Setup
Run `make compile` on first run

Next time you can run app via `make run`.

To stop containers use `make stop`.

### Config
It's better to check/add few values to .env file
* `JWT_SECRET=boLKuYdO...` - Secret for JWT-Token
* `AUTH_GUARD=api` - setup of AUTH_GUARD

* `MINIMUM_BALANCE_LIMIT=-500` - limit of minimum balance/ability of credits
(For credit you should add value with negative sign)
* `SUBSCRIPTION_FEE=5` - fee of monthly subscription

To get simple ability to check database you can use Adminer
### Adminer
- URL: http://localhost:9090
- Server: `db`
- Username: `refactorian`
- Password: `refactorian`
- Database: `refactorian`

### Routes
You can use next routes:
#### without JWT auth
* `/api/user-register`
* `/api/user-login`
#### only with JWT Bearer auth
* `/api/user-info`
* `/api/send-funds`
* `/api/bulk-send-funds`
* `/api/transaction-history`

#### You can use Postman collection `postman_collection.json` to test API
### Commands
You can use command
* `php artisan subscription:charge` to charge subscription fee

### Image
To get more info about this image you can visit [Pack-Readme](Pack-readme.md)
