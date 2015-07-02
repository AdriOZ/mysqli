# mysqli
##Simple library to connect to mysql in PHP
The library abstracts the connection to MySQL in PHP. It offers some simple
functions that makes easier to execute statements. Furthermore, it parses strings
to avoid SQL and HTML injection.
###Configuration
Example of configuration:
```PHP
private static $_HOST = 'localhost';

private static $_DB = 'myDB';

private static $_USER = 'root';

private static $_PASS = 'root';
```
###Code Examples
####Insert
```PHP
$example = new DB();
$example->insert(
    'people',               # Table
    array(                  # Insert
        'name' => 'Mike',
        'age' => 30
    )
);
```
The insert clause generated is:
```SQL
INSERT INTO people (name,age) VALUES ('Mike',30)
```
####Update
```PHP
$example = new DB();
$example->update(
    'people',               # Table
    array(                  # Update
        'name' => 'Jimmy',
        'email' => 'jimmyjimmy@email.com'
    ),
    array(                  # Filters
        'id' => 4
    )
);
```
The update clause generated is:
```SQL
UPDATE people SET name='Jimmy',email='jimmyjimmy@email.com' WHERE id=4
```
####Delete
```PHP
$example = new DB();
$example->delete(
    'people',                   # Table
    array( 'name' => 'Jimmy' )  # Filters
);
```
The delete clause generated is:
```SQL
DELETE FROM people WHERE name='Jimmy'
```
####Query
```PHP
$example = new DB();
$example->simpleQuery(
    'people',               # Table
    array( '*' ),           # Fields
    array(                  # Filters
        'name' => 'Mike',
        'age' => 30
    )
);
```
The delete clause generated is:
```SQL
SELECT * FROM people WHERE name='Mike' AND age=30
```
####Custom Statement
There are two methods to execute custom statements:
* **customQuery**
* **customStatement**

Tha main difference between them is that **customQuery** is designed to execute
queries, because it makes an array with the result of the query. On the other hand,
**customStatement** return directly the result of executing the statement; so,
it can be used for insert, update, delete and query clauses.
