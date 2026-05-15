<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return view('dashboard.admin', [
                'totalUsers'       => User::count(),
                'totalTeachers'    => User::where('role_id', 2)->count(),
                'totalStudents'    => User::where('role_id', 3)->count(),
                'totalMaterials'   => Material::count(),
                'totalSubmissions' => Submission::count(),
                'recentChangelog'  => Changelog::orderByDesc('released_at')->orderByDesc('id')->take(3)->get(),
            ]);
        }

        if ($user->isTeacher()) {
            $materials = Material::with('questions')->where('teacher_id', $user->id)->latest()->take(6)->get();
            return view('dashboard.teacher', [
                'totalMaterials'  => Material::where('teacher_id', $user->id)->count(),
                'totalQuestions'  => \App\Models\Question::whereIn('material_id',
                                        Material::where('teacher_id', $user->id)->pluck('id'))->count(),
                'totalSubmissions'=> Submission::whereIn('question_id',
                                        \App\Models\Question::whereIn('material_id',
                                            Material::where('teacher_id', $user->id)->pluck('id'))->pluck('id'))->count(),
                'recentMaterials' => $materials,
            ]);
        }

        if ($user->isStudent()) {
            $mySubs = Submission::with('question.material')->where('user_id', $user->id)->latest()->take(5)->get();
            return view('dashboard.student', [
                'availableMaterials' => Material::where('is_published', true)->count(),
                'myAnswered'         => Submission::where('user_id', $user->id)->where('status', 'graded')->count(),
                'avgScore'           => round((float) Submission::where('user_id', $user->id)->avg('score') ?? 0, 1),
                'recentSubmissions'  => $mySubs,
            ]);
        }

        return view('dashboard');
    }
}
