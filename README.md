#  Ransack Eloquent

Pesquisa baseado em query string

#### Instalação
```
composer require nyl/ransack-eloquent @dev
```

#### Introdução

Laravel 5.5
 - Adicione ao sua Model a trait `RansackEloquentTrait`
```php
class Student extends Model
{
    use \RansackEloquent\RansackEloquentTrait;
    ...
```
 - No seu Controller
```php
class StudentController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $students = Student::ranFilter($request->all())->get();
    ...
```

#### Parametros de Pesquisa
 Na rota para a action index do `StudentController` deve ser passado query params

- Por ID `/student/index?id_eq=1` vai gerar a SQL `"select * from "students" where "students"."id" = 1"`