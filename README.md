# DB

I made this class back in 2015 when I started programming in my first job, because back then, the only similar utility that was ever used in the company was a one method class that returned raw `mysqli_query` results.

This class started as a modification of that one method class and saved me countless hours of work.

I have been modifying the class through the years, to the point that now nothing is left from that first class I found when I started working.

Of course, there are better implementations out there that accomplish the same; but I always use it whenever I need something easy and reliable.

Sometimes, an Entity Manager is too much and you just need one class: no composer, no namespaces, no dependency inyection... just one class.

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
