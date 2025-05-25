<?php declare(strict_types=1);

namespace App\GraphQL\Queries;

use App\Models\Student;

final readonly class StudentQueries
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }

    public function all($root, array $args)
    {
        return Student::all();
    }

    public function find($root, array $args)
    {
        return Student::find($args['id']);
    }
}
