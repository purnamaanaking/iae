<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Http\Resources\studentResource;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::all();
        return new studentResource($students, 'Success', 'List of students');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nim' => 'required',
            'name' => 'required',
            'email' => 'required',
            'address' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return new studentResource(null, 'Failed', $validator->errors());
        }

        $student = Student::create($request->all());
        return new studentResource($student, 'Success', 'Student created successfully');
    }

    public function show($id)
    {
        $student = Student::find($id);
        if ($student) {
            return new studentResource($student, 'Success', 'Student found');
        } else {
            return new studentResource(null, 'Failed', 'Student not found');
        }
    }

    public function update(Request $request, $id)
    {
        $student = Student::find($id);
        if ($student) {
            $student->update($request->all());
            return new studentResource($student, 'Success', 'Student updated successfully');
        } else {
            return new studentResource(null, 'Failed', 'Student not found');
        }
    }

    public function destroy($id)
    {
        $student = Student::find($id);
        if ($student) {
            $student->delete();
            return new studentResource($student, 'Success', 'Student deleted successfully');
        } else {
            return new studentResource(null, 'Failed', 'Student not found');
        }
    }
}
