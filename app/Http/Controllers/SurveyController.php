<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSurveyAnswersRequest;
use App\Http\Resources\SurveyResource;
use App\Models\Survey;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Models\SurveyAnswer;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SurveyController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\ $request
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $surveys = Survey::where('user_id', $user->id)->paginate(10);

        return SurveyResource::collection($surveys);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreSurveyRequest $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function store(StoreSurveyRequest $request)
    {
        $data = $request->all();

        // Check if image was given and after is saved on file system
        if (isset($data['image'])) {
            $relativePath = $this->saveImage($data['image']);
            $data['image'] = $relativePath;
        }

        $survey = Survey::create($data);

        //Create new question
        foreach ($data['questions'] as $question) {

            $question['survey_id'] = $survey->id;
            $this->createQuestion($question);
        }

        return response(new SurveyResource($survey), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Survey $survey
     * @param \App\Http\Requests\ $request
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function show(Survey $survey, Request $request)
    {
        $user = $request->user();

        if (!$user->id === $survey->user_id) {
            return abort('403', 'Unauthorized action');
        }

        return response(new SurveyResource($survey), 200);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Survey $survey
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function showForGuest(Survey $survey)
    {
        return response(new SurveyResource($survey), 200);
    }

    /**
     * @param Survey $survey
     * @param StoreSurveyAnswersRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function storeAnswers(Survey $survey, StoreSurveyAnswersRequest $request)
    {
        $dataValidated = $request->validated();

        $surveyAnswer = SurveyAnswer::create([
            "survey_id" => $survey->id,
            "start_date" => date('Y-m-d H:i:s'),
            "end_date" => date('Y-m-d H:i:s'),
        ]);

        foreach ($dataValidated['answers'] as $questionId => $answer) {
            $surveyQuestion = SurveyQuestion::where(['id' => $questionId, 'survey_id' => $survey->id])->get();

            if (!$surveyQuestion) {
                return response("This question not belong this survey!", 400);
            }

            $data = [
                "answer" => is_array($answer) ? json_encode($answer) : $answer,
                "survey_question_id" => $questionId,
                "survey_answer_id" => $surveyAnswer->id
            ];

            SurveyQuestionAnswer::create($data);
        }

        return response('', 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateSurveyRequest $request
     * @param \App\Models\Survey $survey
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function update(UpdateSurveyRequest $request, Survey $survey)
    {
        $data = $request->all();

        if (isset($data['image'])) {
            $relativePath = $this->saveImage($data['image']);
            $data['image'] = $relativePath;

            //if there is an old image, delete it
            if ($survey->image) {
                $absolutePath = public_path($survey->image);
                File::delete($absolutePath);
            }
        }

        //Update survey
        $survey->update($data);

        //Get Ids as array of existing ('old') questions
        $existingIds = $survey->questions()->pluck('id')->toArray();

        //Gets Ids as array of new questions - (by default is add uuid in front end)
        $newIds = Arr::pluck($data['questions'], 'id');

        //Find questions to delete (in other words - have at the 'old' and there (is not) at the 'new') - Return array of questions
        $toDelete = array_diff($existingIds, $newIds);

        //Find questions to add (in other words - have at the 'new' and there (is not) at the 'old') - Return array of questions
        $toAdd = array_diff($newIds, $existingIds);

        //Delete questions by $toDelete array
        SurveyQuestion::destroy($toDelete);

        //Create new questions by $toAdd array
        foreach ($data['questions'] as $question) {

            //Checking if questions received they are at the array the question for add - because the few will be updated only.
            if (in_array($question['id'], $toAdd)) {
                $question['survey_id'] = $survey->id;
                $this->createQuestion($question);
            }
        }

        //LEFT OVER THE QUESTIONS FOR EDITING
        //The method collect will set key at the array informed no method keyBy() -- example bellow
        /*
            $collection = collect([
                ['product_id' => 'prod-100', 'name' => 'Desk'],
                ['product_id' => 'prod-200', 'name' => 'Chair'],
            ]);

            $keyed = $collection->keyBy('product_id');

            $keyed->all();

            [
                'prod-100' => ['product_id' => 'prod-100', 'name' => 'Desk'],
                'prod-200' => ['product_id' => 'prod-200', 'name' => 'Chair'],
            ]
        * */
        $questionMap = collect($data['questions'])->keyBy('id');

        //Updating questions
        foreach ($survey->questions as $question) {

            //Checking if has value with id specified because the Ids the questions created above will also appear in the array here
            if (isset($questionMap[$question->id])) {

                //The parameters are the ($question Model 'for update') and the question who may or may not the options
                $this->updateQuestion($question, $questionMap[$question->id]);
            }
        }

        return response(new SurveyResource($survey), 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Survey $survey
     * @param \App\Http\Requests\ $request
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Survey $survey, Request $request)
    {
        $user = $request->user();

        if (!$user->id === $survey->user_id) {
            return abort('403', 'Unauthorized action');
        }

        $survey->delete();

        //if there is an old image, delete it
        if ($survey->image) {
            $absolutePath = public_path($survey->image);
            File::delete($absolutePath);
        }

        return response('', 204);
    }

    /**
     * @param $image
     * @return string
     * @throws \Exception
     */
    private function saveImage($image): string
    {
        // Check if image is valid base64 string
        if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {

            // Take out the base64 encoded text without mime type
            $image = substr($image, strpos($image, ',') + 1);
            // Get file extension
            $type = strtolower($type[1]); // jpg, png, gif

            // Check if file is an image
            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception('invalid image type');
            }
            $image = str_replace(' ', '+', $image);
            $image = base64_decode($image);

            if ($image === false) {
                throw new \Exception('base64_decode failed');
            }
        } else {
            throw new \Exception('did not match data URI with image data');
        }

        $dir = 'images/';
        $file = Str::random() . '.' . $type;
        $absolutePath = public_path($dir);
        $relativePath = $dir . $file;
        if (!File::exists($absolutePath)) {
            File::makeDirectory($absolutePath, 0755, true);
        }
        file_put_contents($relativePath, $image);

        return $relativePath;
    }

    /**
     * @param mixed $question
     * @return mixed
     * @throws \Illuminate\Validation\ValidationException
     */
    private function createQuestion(mixed $question)
    {
        if (is_array($question['data'])) {
            // If really have array in field ['data']. Has to be converted in json for be save in database. Can't save array in database.
            $question['data'] = json_encode($question['data']);
        }

        $validator = Validator::make($question, [
            'question' => 'required|string',
            'type' => ['required', Rule::in([
                SurveyQuestion::TYPE_TEXT,
                SurveyQuestion::TYPE_SELECT,
                SurveyQuestion::TYPE_RADIO,
                SurveyQuestion::TYPE_CHECKBOX,
                SurveyQuestion::TYPE_TEXTAREA,
            ])],
            'description' => 'nullable|string',
            'data' => 'present',
            'survey_id' => 'exists:APP\Models\Survey,id'
        ]);

        return SurveyQuestion::create($validator->validated());
    }

    /**
     * @param SurveyQuestion $question
     * @param mixed $data
     * @return bool
     * @throws \Illuminate\Validation\ValidationException
     */
    private function updateQuestion(SurveyQuestion $question, mixed $data)
    {
        if (is_array($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }

        /*
         *
         * To check if you really need to update I could compare the json received with the one from the database.
         *
         *
        */

        $validator = Validator::make($data, [
            'id' => 'exists:App\Models\SurveyQuestion,id',
            'question' => 'required|string',
            'type' => ['required', Rule::in([
                SurveyQuestion::TYPE_TEXT,
                SurveyQuestion::TYPE_SELECT,
                SurveyQuestion::TYPE_RADIO,
                SurveyQuestion::TYPE_CHECKBOX,
                SurveyQuestion::TYPE_TEXTAREA,
            ])],
            'description' => 'nullable|string',
            'data' => 'present'
        ]);

        return $question->update($validator->validated());
    }
}
