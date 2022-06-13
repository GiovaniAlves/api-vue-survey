<?php

namespace App\Http\Controllers;

use App\Http\Resources\SurveyAnswerResource;
use App\Http\Resources\SurveyResource;
use App\Http\Resources\SurveyResourceDashboard;
use App\Models\Survey;
use App\Models\SurveyAnswer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function reports(Request $request)
    {
        $user = $request->user();

        //Get the total number of surveys
        $totalSurveys = Survey::where(['user_id' => $user->id])->count();

        //Get the latest survey
        $latestSurvey = Survey::where(['user_id' => $user->id])->latest()->first();

        //Get the total number of answers
        $totalAnswers = SurveyAnswer::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.user_id', $user->id)
            ->count();

        //Get the latest 5 answers
        $latestFiveAnswers = SurveyAnswer::query()
            ->join('surveys', 'survey_answers.survey_id', '=', 'surveys.id')
            ->where('surveys.user_id', $user->id)
            ->orderBy('survey_answers.id', 'DESC')
            ->limit(5)
            ->get();

        return response([
            'totalSurveys' => $totalSurveys,
            'latestSurvey' => $latestSurvey ? new SurveyResourceDashboard($latestSurvey) : null,
            'totalAnswers' => $totalAnswers,
            'latestFiveAnswers' => SurveyAnswerResource::collection($latestFiveAnswers)
        ], 200);
    }
}
