<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        
        $courses = Course::select('title', 'description')->get();

        return response()->json([
            'courses' => $courses
        ]);

    }

    public function show(Course $course)
    {
        // Csak a szükséges mezők a kapcsolt usereknél, valamint a teljesítési státusz
        $students = $course->users()->select('name', 'email')->withPivot('completed_at')->get()->map(function ($user) {
            return [
                'name' => $user->name,
                'email' => $user->email,
                'completed' => !is_null($user->pivot->completed_at)
            ];
        });

        return response()->json([
            'course' => [
                'title' => $course->title,
                'description' => $course->description
            ],
            'students' => $students
        ]);
    }

    public function enroll(Course $course, Request $request)
    {
        $user = $request->user();

        if ($user->courses()->where('course_id', $course->id)->exists()) {
            return response()->json(['message' => 'Already enrolled in this course'], 409);
        }

        $user->courses()->attach($course->id, ['enrolled_at' => now()]);

        return response()->json(['message' => 'Successfully enrolled in course']);
    }

    public function complete(Course $course, Request $request)
    {
        $user = $request->user();
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'Not enrolled in this course'], 403);
        }

        if ($enrollment->completed_at) {
            return response()->json(['message' => 'Course already completed'], 409);
        }

        $enrollment->update(['completed_at' => now()]);

        return response()->json(['message' => 'Course completed']);
    }
}