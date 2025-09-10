<?php declare(strict_types=1);

namespace App\GraphQL\Mutations;

use App\Models\Student;

final readonly class StudentMutations
{
    /** @param  array{}  $args */
    public function __invoke(null $_, array $args)
    {
        // TODO implement the resolver
    }

    public function create($root, array $args)
    {
        return Student::create($args['input']);
    }

    public function update($root, array $args)
    {
        $student = Student::findOrFail($args['input']['id']);
        $student->update($args['input']);
        return $student;
    }

    public function delete($root, array $args)
    {
        $student = Student::findOrFail($args['id']);
        $student->delete();
        return $student;
    }
}
