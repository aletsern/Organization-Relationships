# Organization Relationships
## How to use
1. First you need to clone the project into your idea
2. It is necessary to configure the connection between the database and the application:

This can be done in the .../OrganizationRelationships/.env file.
```angular2html
...
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=organizationrelationships
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
...
```
3. Then you need to create tables in the database:

In the project folder, write the command:
```angular2html
php artisan migrate
```
4. Launch a project:

```angular2html
php artisan serve
```

### Usage
You can check requests for API using the Postman program.

My [collection](https://www.postman.com/sanja1599/workspace/my-workspace/collection/18548710-cfd3c0e9-de85-40fd-8e02-1c946a4c1104?action=share&source=copy-link&creator=18548710) in Postman for checking API.
