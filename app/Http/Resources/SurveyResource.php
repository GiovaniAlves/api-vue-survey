<?php

namespace App\Http\Resources;

use App\Models\SurveyQuestionAnswer;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class SurveyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $questionsAnswer = new SurveyQuestionAnswer;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'image_url' => $this->image ? URL::to($this->image) : null,
            'slug' => $this->slug,
            'status' => !!$this->status,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'expire_date' => $this->expire_date,
            'questions' => SurveyQuestionResource::collection($this->questions),
            //'answers' => SurveyQuestionAnswersResource::collection($questionsAnswer->answers_question)
            //'answers' => SurveyQuestionAnswersResource::collection($this->answers_question)
        ];
    }
}
