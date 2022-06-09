<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    use HasFactory;

    const TYPE_TEXT = 'text';
    const TYPE_SELECT = 'select';
    const TYPE_RADIO = 'radio';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_TEXTAREA = 'textarea';

    protected $fillable = ['survey_id', 'type', 'question', 'description', 'data'];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
