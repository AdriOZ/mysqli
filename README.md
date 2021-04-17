# DB

I made this class when I started in my first job. I have been modifying through the years, and I think that I will continue doing so.

I always use it whenever I need something easy and reliable. Sometimes, an Entity Manager is too much and you just need: one Class, no composer, zero namespaces, zero dependency inyection... just one Class.

The usage is pretty straightforward:

```php

include 'DB.php';

$db = new DB('localhost', 'user', 'password', 'database')

/* Insert */
$db->Insert('person', [
    'name' => 'John'
]);

$db->Bulk('person', ['name', 'lastname'], [
    ['Bob', 'Anderson'],
    ['Kirk', 'Jefferson']
]);

/* Update */
$id = $db->LastId();
$db->Update('person', ['name' => 'Paul'], ['id' => $id]);

/* Delete*/
$db->Delete('person', ['name' => 'John']);

/* Transactions */
$db->Begin();

$result = $db->Bulk('person', ['name', 'lastname'], [
    ['Bob', 'Anderson'],
    ['Kirk', 'Jefferson']
]);

$result
    ? $db->Commit()
    : $db->Rollback();

/* Plain Statement */
$db->Statement("SET FOREIGN_KEY_CHECKS=0");

/* Simple Select */
$persons = $db->Select(
    table: 'person',
    fields: ['name', 'lastname'],
    where: ['lastname' => 'Anderson'],
    orderBy: 'name',
    orderType: 'ASC',
    limit: 10
);

foreach ($persons as $person) {
    echo $person->name . ' ' . $person->lastname;
}

/* Even easier */
$persons = $db->Where('person', ['name' => 'John']);

foreach ($persons as $person) {
    echo $person->name . ' ' . $person->lastname;
}

/* Just one record */
$person = $db->Find('person', ['name' => 'John']);
echo $person->name . ' ' . $person->lastname;

/* Complex Queries */
$min = 18;
$max = 35;

$persons = $db->Query(
    "SELECT
        p.name,
        p.lastname,
        c.name AS city
    FROM
        person p
    JOIN
        city c
    ON
        p.city_id = c.id
    WHERE
        p.age > '%d' AND p.age < '%d'",
    $min,
    $max
);

foreach ($persons as $person) {
    echo $person->name . ' ' . $person->lastname . ' / ' . $p->city;
}
```
