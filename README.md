#  Ransack Eloquent

Pesquisa baseado em query string

## Introdução

Você precisa realizar uma busca em ...

`/users?name=er&last_name=&company_id=2&roles[]=1&roles[]=4&roles[]=7&industry=5`

`$request->all()` will return:

```php
[
    'name'       => 'er',
    'last_name'  => '',
    'company_id' => '2',
    'roles'      => ['1','4','7'],
    'industry'   => '5'
]
```
## Instalação
```
composer require nyl/ransack-eloquent
```

## Usando